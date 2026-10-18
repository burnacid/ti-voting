<?php

use App\Events\TriggerRefresh;
use App\Livewire\Admin\GameDetail;
use App\Livewire\Admin\Games;
use App\Livewire\Admin\Login;
use App\Livewire\Admin\Logs;
use App\Livewire\GameDashboard;
use App\Models\Agenda;
use App\Models\Game;
use App\Models\LogEntry;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

function makeAdminGame(string $name = 'Admin Test Game', ?string $createdAt = null): Game
{
    $game = Game::create(['code' => Game::generateCode(), 'name' => $name, 'status' => 'waiting']);

    if ($createdAt) {
        $game->forceFill(['created_at' => $createdAt])->save();
    }

    Player::create([
        'game_id' => $game->id,
        'name' => 'Alice',
        'session_token' => Player::generateSessionToken(),
        'is_speaker' => true,
    ]);

    return $game;
}

test('admin pages require a login', function (string $url) {
    $this->get($url)->assertRedirect(route('admin.login'));
})->with(['/admin', '/admin/games', '/admin/logs']);

test('an admin can log in with the right password only', function () {
    $user = User::factory()->create(['password' => 'correct horse battery']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong password')
        ->call('login')
        ->assertHasErrors('email');
    $this->assertGuest();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct horse battery')
        ->call('login')
        ->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('logins are throttled after five failed attempts', function () {
    $user = User::factory()->create(['password' => 'correct horse battery']);

    foreach (range(1, 5) as $attempt) {
        Livewire::test(Login::class)->set('email', $user->email)->set('password', 'wrong')->call('login');
    }

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'correct horse battery')
        ->call('login')
        ->assertHasErrors('email');
    $this->assertGuest();
});

test('the dashboard shows stats', function () {
    $game = makeAdminGame();
    Agenda::create([
        'game_id' => $game->id,
        'title' => 'Mutiny',
        'description' => 'Test',
        'options' => ['For', 'Against', 'Abstain'],
        'status' => 'voting',
    ]);

    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertOk()
        ->assertSee('Games created per day')
        ->assertSee('Mutiny');
});

test('an admin can search and delete games', function () {
    $this->actingAs(User::factory()->create());
    $kept = makeAdminGame('Kept Game');
    $deleted = makeAdminGame('Deleted Game');

    Livewire::test(Games::class)
        ->set('search', 'Kept')
        ->assertSee('Kept Game')
        ->assertDontSee('Deleted Game')
        ->call('deleteGame', $deleted->id);

    expect(Game::find($deleted->id))->toBeNull()
        ->and(Game::find($kept->id))->not->toBeNull()
        ->and(Player::where('game_id', $deleted->id)->exists())->toBeFalse();
});

test('an admin can clean up old games', function () {
    $this->actingAs(User::factory()->create());
    $old = makeAdminGame('Old Game', now()->subDays(10)->toDateTimeString());
    $recent = makeAdminGame('Recent Game');

    Livewire::test(Games::class)
        ->set('cleanupDays', 7)
        ->assertSee('Delete 1 game')
        ->call('runCleanup')
        ->assertSee('Games deleted: 1');

    expect(Game::find($old->id))->toBeNull()
        ->and(Game::find($recent->id))->not->toBeNull();
});

test('the game detail page shows players and can delete the game', function () {
    $this->actingAs(User::factory()->create());
    $game = makeAdminGame();

    $this->get(route('admin.games.show', $game))->assertOk()->assertSee('Alice');

    Livewire::test(GameDetail::class, ['game' => $game])
        ->call('deleteGame')
        ->assertRedirect(route('admin.games'));
    expect(Game::find($game->id))->toBeNull();
});

test('the log viewer shows, filters and deletes log entries', function () {
    $this->actingAs(User::factory()->create());
    LogEntry::create(['level' => 'info', 'message' => 'Something normal happened', 'created_at' => now()]);
    LogEntry::create(['level' => 'error', 'message' => 'Something broke', 'game' => 'ABC123', 'trace' => '#0 /var/www/app/Broken.php(12): explode()', 'created_at' => now()]);

    Livewire::test(Logs::class)
        ->assertSee('Something normal happened')
        ->assertSee('Broken.php')
        ->set('level', 'error')
        ->assertDontSee('Something normal happened')
        ->assertSee('Something broke')
        ->set('level', '')
        ->set('game', 'abc123')
        ->assertSee('Something broke')
        ->assertDontSee('Something normal happened')
        ->call('deleteMatching');

    expect(LogEntry::where('message', 'Something broke')->exists())->toBeFalse()
        ->and(LogEntry::where('message', 'Something normal happened')->exists())->toBeTrue()
        ->and(LogEntry::where('message', 'Admin deleted log entries')->exists())->toBeTrue();
});

test('game actions are logged to the database with the game code', function () {
    Event::fake([TriggerRefresh::class]);
    $this->actingAs(User::factory()->create());
    $game = makeAdminGame('Logged Game');
    Agenda::create([
        'game_id' => $game->id,
        'title' => 'Mutiny',
        'description' => 'Test',
        'options' => ['For', 'Against', 'Abstain'],
        'status' => 'voting',
        'voting_started_at' => now(),
    ]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $game->players()->first()])
        ->set('selectedOption', 'For')
        ->set('influenceSpent', 4)
        ->call('submitVote');

    $entry = LogEntry::where('message', 'Vote cast')->first();
    expect($entry)
        ->level->toBe('info')
        ->game->toBe($game->code)
        ->context->toBe(['player' => 'Alice', 'agenda' => 'Mutiny', 'option' => 'For', 'influence' => 4]);

    Livewire::test(Logs::class, ['game' => $game->code])
        ->set('game', $game->code)
        ->assertSee('Vote cast');

    $this->get(route('admin.games.show', $game))->assertSee(route('admin.logs', ['game' => $game->code]));
});

test('exceptions are logged with their class, location and trace', function () {
    Log::shareContext(['game' => 'ABC123']);
    report(new RuntimeException('Boom {weird} "q"'));

    $entry = LogEntry::where('level', 'error')->first();
    expect($entry)
        ->message->toBe('Boom {weird} "q"')
        ->game->toBe('ABC123')
        ->context->toBeNull()
        ->and($entry->exception['class'])->toBe('RuntimeException')
        ->and($entry->exception['location'])->toContain('AdminTest.php')
        ->and($entry->trace)->toContain('#0 ');
});

test('debug entries are not written to the database by default', function () {
    Log::debug('Broadcasting refresh');

    expect(LogEntry::count())->toBe(0);
});

test('log entries older than the retention period are pruned', function () {
    LogEntry::create(['level' => 'info', 'message' => 'Old', 'created_at' => now()->subDays(31)]);
    LogEntry::create(['level' => 'info', 'message' => 'Recent', 'created_at' => now()->subDays(29)]);

    $this->artisan('model:prune', ['--model' => [LogEntry::class]])->assertSuccessful();

    expect(LogEntry::pluck('message')->all())->toBe(['Recent']);
});

test('the admin command creates an account', function () {
    $this->artisan('admin:create', ['email' => 'admin@example.com'])
        ->expectsQuestion('Password (at least 12 characters)', 'a long enough password')
        ->assertSuccessful();

    expect(User::where('email', 'admin@example.com')->exists())->toBeTrue();
});
