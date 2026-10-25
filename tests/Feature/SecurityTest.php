<?php

use App\Events\TriggerRefresh;
use App\Livewire\CreateGame;
use App\Livewire\GameDashboard;
use App\Models\Game;
use App\Services\DraftImporter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    Event::fake([TriggerRefresh::class]);
});

test('a draft url that is not on the milty host is rejected', function () {
    Http::fake(['milty.shenanigans.be/*' => Http::response(['draft' => ['players' => []], 'slices' => []])]);

    expect(fn () => (new DraftImporter())->import('javascript://milty.shenanigans.be/d/abc123%0aalert(1)'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => (new DraftImporter())->import('https://evil.example/d/abc123'))
        ->toThrow(InvalidArgumentException::class);
});

test('the stored draft url is built from the draft id, not the input', function () {
    Http::fake(['milty.shenanigans.be/*' => Http::response(['draft' => ['players' => []], 'slices' => []])]);

    $draft = (new DraftImporter())->import('https://milty.shenanigans.be/d/abc123?x="><script>');

    expect($draft['milty_url'])->toBe('https://milty.shenanigans.be/d/abc123');
});

test('a game never links a draft url that is not https', function () {
    $game = new Game(['milty_url' => 'javascript:alert(1)']);
    expect($game->safe_milty_url)->toBeNull();

    $game->milty_url = 'https://milty.shenanigans.be/d/abc123';
    expect($game->safe_milty_url)->toBe('https://milty.shenanigans.be/d/abc123');
});

test('the draft data of a new game cannot be set by the client', function () {
    Livewire::test(CreateGame::class)->set('miltyDraftUrl', 'javascript:alert(1)');
})->throws(CannotUpdateLockedPropertyException::class);

test('an agenda source that is not a known deck is rejected', function () {
    [$game, $speaker] = makeGame();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->set('selectedPresetAgenda', 'Publicize Weapon Schematics')
        ->set('newAgendaSource', 'evil')
        ->call('createAgenda')
        ->assertHasErrors('newAgendaSource');

    expect($game->currentAgenda())->toBeNull();
});
