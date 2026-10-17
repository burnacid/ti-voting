<?php

use App\Events\TriggerRefresh;
use App\Livewire\CreateGame;
use App\Livewire\GameDashboard;
use App\Livewire\UpdateMiltyUrl;
use App\Models\Agenda;
use App\Models\Game;
use App\Models\Player;
use App\Models\Vote;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Event::fake([TriggerRefresh::class]);
});

function makeGame(array $attributes = []): array
{
    $game = Game::create(array_merge([
        'code' => Game::generateCode(),
        'name' => 'Test Game',
        'status' => 'waiting',
    ], $attributes));

    $speaker = Player::create([
        'game_id' => $game->id,
        'name' => 'Speaker',
        'session_token' => Player::generateSessionToken(),
        'is_speaker' => true,
    ]);

    $other = Player::create([
        'game_id' => $game->id,
        'name' => 'Other',
        'session_token' => Player::generateSessionToken(),
        'is_speaker' => false,
    ]);

    $game->update(['speaker_id' => $speaker->id]);

    return [$game, $speaker, $other];
}

function makeAgenda(Game $game): Agenda
{
    return Agenda::create([
        'game_id' => $game->id,
        'title' => 'Test Agenda',
        'description' => 'Test',
        'options' => ['For', 'Against', 'Abstain'],
        'status' => 'voting',
        'voting_started_at' => now(),
    ]);
}

test('non-speaker cannot update the milty url', function () {
    [$game, , $other] = makeGame();
    Http::fake();

    Livewire::test(UpdateMiltyUrl::class, ['game' => $game, 'player' => $other])
        ->set('miltyUrl', 'https://milty.shenanigans.be/d/abc123')
        ->call('updateMiltyUrl');

    expect($game->fresh()->milty_url)->toBeNull();
    Http::assertNothingSent();
});

test('speaker can update the milty url', function () {
    [$game, $speaker] = makeGame();
    Http::fake(['milty.shenanigans.be/*' => Http::response(['draft' => ['players' => []]])]);

    Livewire::test(UpdateMiltyUrl::class, ['game' => $game, 'player' => $speaker])
        ->set('miltyUrl', 'https://milty.shenanigans.be/d/abc123')
        ->call('updateMiltyUrl');

    expect($game->fresh())
        ->milty_url->toBe('https://milty.shenanigans.be/d/abc123')
        ->milty_draft_id->toBe('abc123');
});

test('game page only shows the milty button to the speaker', function () {
    [$game, $speaker, $other] = makeGame();

    $this->withSession(['player_token' => $other->session_token])
        ->get(route('game.show', $game->code))
        ->assertOk()
        ->assertDontSee('Change Milty URL');

    $this->withSession(['player_token' => $speaker->session_token])
        ->get(route('game.show', $game->code))
        ->assertOk()
        ->assertSee('Change Milty URL');
});

test('vote for an option not on the agenda is rejected', function () {
    [$game, , $other] = makeGame();
    makeAgenda($game);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->set('selectedOption', 'Something Else')
        ->set('influenceSpent', 3)
        ->call('submitVote')
        ->assertHasErrors(['selectedOption' => 'in']);

    expect(Vote::count())->toBe(0);
});

test('valid vote is recorded', function () {
    [$game, , $other] = makeGame();
    $agenda = makeAgenda($game);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->set('selectedOption', 'For')
        ->set('influenceSpent', 3)
        ->call('submitVote')
        ->assertHasNoErrors();

    expect($other->hasVotedOn($agenda))->toBeTrue();
});

test('duplicate vote insert does not error', function () {
    [$game, , $other] = makeGame();
    $agenda = makeAgenda($game);

    $component = Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other]);

    // Simulate a concurrent request winning the race after the hasVotedOn() check.
    Vote::creating(function () use ($agenda, $other) {
        Vote::withoutEvents(fn () => Vote::create([
            'agenda_id' => $agenda->id,
            'player_id' => $other->id,
            'option' => 'Against',
            'influence_spent' => 1,
        ]));
    });

    $component->set('selectedOption', 'For')
        ->set('influenceSpent', 3)
        ->call('submitVote')
        ->assertDispatched('flashMessage', ['You have already voted on this agenda.', 'error']);

    expect(Vote::count())->toBe(1);
});

test('planet preset agenda elects any planet when milty data exists', function () {
    [$game, $speaker] = makeGame(['milty_draft_data' => ['draft' => ['players' => []], 'slices' => []]]);

    $component = Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker]);
    $planetAgenda = collect($component->get('availableAgendas'))
        ->first(fn ($a) => strtolower($a['Elect'] ?? '') === 'planet');

    expect($planetAgenda)->not->toBeNull();

    $component->set('selectedPresetAgenda', $planetAgenda['Name'])
        ->assertSet('agendaType', 'elect_planet_any');
});

test('thunders edge games include the fracture planets', function () {
    $draft = ['draft' => ['players' => []], 'slices' => []];
    [$teGame] = makeGame(['milty_draft_data' => $draft + ['config' => ['include_te_tiles' => true]]]);
    [$baseGame] = makeGame(['milty_draft_data' => $draft + ['config' => ['include_te_tiles' => false]]]);

    expect($teGame->isThundersEdgeGame())->toBeTrue()
        ->and($teGame->getPlanets()->pluck('name'))->toContain('Styx', 'Cocytus', 'Lethe', 'Phlegethon')
        ->and($teGame->getPlanets('non_home')->pluck('name'))->toContain('Styx')
        ->and($teGame->getPlanets('cultural')->pluck('name'))->not->toContain('Styx')
        ->and($baseGame->isThundersEdgeGame())->toBeFalse()
        ->and($baseGame->getPlanets()->pluck('name'))->not->toContain('Styx');
});

test('planets with multiple traits are electable for each trait', function () {
    // Tile 101 holds Olergodt, which is both cultural and hazardous
    [$game] = makeGame(['milty_draft_data' => [
        'draft' => ['players' => [['slice' => 0, 'faction' => null]]],
        'slices' => [['tiles' => [101]]],
    ]]);

    expect($game->getPlanets('cultural')->pluck('name'))->toContain('Olergodt')
        ->and($game->getPlanets('hazardous')->pluck('name'))->toContain('Olergodt')
        ->and($game->getPlanets('industrial')->pluck('name'))->not->toContain('Olergodt');
});

test('elect planet includes mecatol rex but non-home excludes it', function () {
    [$game] = makeGame(['milty_draft_data' => ['draft' => ['players' => []], 'slices' => []]]);

    expect($game->getPlanets()->pluck('name'))->toContain('Mecatol Rex')
        ->and($game->getPlanets('non_home')->pluck('name'))->not->toContain('Mecatol Rex');
});

test('after voting ends only the speaker still sees the results', function () {
    [$game, $speaker, $other] = makeGame();
    makeAgenda($game);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('endVoting')
        ->assertSee('Voting Results')
        ->assertSee('Test Agenda');

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertDontSee('Voting Results')
        ->assertDontSee('Test Agenda');
});

test('creating a game with an unreachable milty url shows an error', function () {
    Http::fake(['milty.shenanigans.be/*' => Http::response(null, 500)]);

    Livewire::test(CreateGame::class)
        ->set('gameName', 'My Game')
        ->set('playerName', 'Me')
        ->set('miltyUrl', 'https://milty.shenanigans.be/d/abc123')
        ->call('createGame')
        ->assertHasErrors('miltyUrl');

    expect(Game::count())->toBe(0);
});

test('creating a game without a milty url works', function () {
    Http::fake();

    Livewire::test(CreateGame::class)
        ->set('gameName', 'My Game')
        ->set('playerName', 'Me')
        ->call('createGame')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Game::count())->toBe(1);
    Http::assertNothingSent();
});
