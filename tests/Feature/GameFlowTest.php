<?php

use App\Events\TriggerRefresh;
use App\Livewire\CreateGame;
use App\Livewire\GameDashboard;
use App\Livewire\JoinGameForm;
use App\Livewire\UpdateMiltyUrl;
use App\Models\Agenda;
use App\Models\Game;
use App\Models\Player;
use App\Models\Vote;
use App\Support\PlayerGames;
use Illuminate\Support\Facades\Cookie;
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

// The games this browser was told to remember in the response, code => session token
function rememberedGames(): array
{
    return json_decode(Cookie::queued(PlayerGames::COOKIE)?->getValue() ?? '[]', true);
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
        ->assertDontSee('Change Map');

    $this->withSession(['player_token' => $speaker->session_token])
        ->get(route('game.show', $game->code))
        ->assertOk()
        ->assertSee('Change Map');
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

test('thunders edge games include the fracture planets once it is in play', function () {
    $draft = ['draft' => ['players' => []], 'slices' => []];
    [$teGame] = makeGame(['milty_draft_data' => $draft + ['config' => ['include_te_tiles' => true]], 'fracture_in_play' => true]);
    [$baseGame] = makeGame(['milty_draft_data' => $draft + ['config' => ['include_te_tiles' => false]], 'fracture_in_play' => true]);

    expect($teGame->isThundersEdgeGame())->toBeTrue()
        ->and($teGame->getPlanets()->pluck('name'))->toContain('Styx', 'Cocytus', 'Lethe', 'Phlegethon')
        ->and($teGame->getPlanets('non_home')->pluck('name'))->toContain('Styx')
        ->and($teGame->getPlanets('cultural')->pluck('name'))->not->toContain('Styx')
        ->and($baseGame->isThundersEdgeGame())->toBeFalse()
        ->and($baseGame->getPlanets()->pluck('name'))->not->toContain('Styx');
});

test('speaker controls whether the fracture is in play', function () {
    $draft = ['draft' => ['players' => []], 'slices' => [], 'config' => ['include_te_factions' => true]];
    [$game, $speaker, $other] = makeGame(['milty_draft_data' => $draft]);

    // The Fracture starts out of play
    expect($game->getPlanets()->pluck('name'))->not->toContain('Styx');

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertDontSee('The Fracture is in play')
        ->call('toggleFracture');
    expect($game->fresh()->fracture_in_play)->toBeFalse();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->assertSee('The Fracture is in play')
        ->call('toggleFracture');
    expect($game->fresh()->getPlanets()->pluck('name'))->toContain('Styx');
});

test('enabling the fracture adds its planets to the open planet agenda', function () {
    $draft = ['draft' => ['players' => []], 'slices' => [], 'config' => ['include_te_tiles' => true]];
    [$game, $speaker] = makeGame(['milty_draft_data' => $draft]);
    $planetAgenda = makeAgenda($game);
    $planetAgenda->update(['elect' => 'planet', 'options' => ['Mecatol Rex', 'Abstain']]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('toggleFracture')
        ->assertSee('Styx');

    expect($planetAgenda->fresh()->options)
        ->toBe(['Cocytus', 'Lethe', 'Mecatol Rex', 'Phlegethon', 'Styx', 'Abstain']);
});

test('disabling the fracture removes its planets from the open agenda unless voted for', function () {
    $draft = ['draft' => ['players' => []], 'slices' => [], 'config' => ['include_te_tiles' => true]];
    [$game, $speaker, $other] = makeGame(['milty_draft_data' => $draft, 'fracture_in_play' => true]);
    $planetAgenda = makeAgenda($game);
    $planetAgenda->update([
        'elect' => 'planet',
        'options' => ['Cocytus', 'Lethe', 'Mecatol Rex', 'Phlegethon', 'Styx', 'Abstain'],
    ]);
    Vote::create(['agenda_id' => $planetAgenda->id, 'player_id' => $other->id, 'option' => 'Styx', 'influence_spent' => 2]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('toggleFracture')
        ->assertDispatched('flashMessage', fn ($event, $params) => str_contains($params[0][0], 'Styx stayed on the current agenda'));

    expect($game->fresh()->fracture_in_play)->toBeFalse()
        ->and($planetAgenda->fresh()->options)->toBe(['Mecatol Rex', 'Styx', 'Abstain']);
});

test('enabling the fracture leaves trait and for against agendas alone', function () {
    $draft = ['draft' => ['players' => []], 'slices' => [], 'config' => ['include_te_tiles' => true]];
    [$game, $speaker] = makeGame(['milty_draft_data' => $draft]);
    $agenda = makeAgenda($game);
    $agenda->update(['elect' => 'cultural_planet', 'options' => ['Olergodt', 'Abstain']]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('toggleFracture');

    expect($agenda->fresh()->options)->toBe(['Olergodt', 'Abstain']);
});

test('the fracture toggle is hidden in games without thunders edge', function () {
    [$game, $speaker] = makeGame(['milty_draft_data' => ['draft' => ['players' => []], 'slices' => []]]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->assertDontSee('The Fracture is in play')
        ->call('toggleFracture');

    expect($game->fresh()->fracture_in_play)->toBeFalse();
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

test('preset agendas are shown as a card with their type', function () {
    [$game, $speaker] = makeGame();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->set('selectedPresetAgenda', 'Publicize Weapon Schematics')
        ->call('createAgenda')
        ->assertSeeHtml('<div class="agenda-card-type">')
        ->assertSeeHtml('<strong class="agenda-card-outcome">For:</strong>')
        ->assertSeeHtml('<span class="agenda-card-keyword">sustain damage</span>');

    expect($game->currentAgenda()->type)->toBe('law');
});

test('elect agendas show what is elected on the card', function () {
    [$game, $speaker] = makeGame();

    // Preset agenda that elects a law, voted on with custom options
    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->set('selectedPresetAgenda', 'Judicial Abolishment')
        ->call('createAgenda')
        ->assertSeeHtml('<p class="agenda-card-elect">Elect Law</p>');
});

test('custom elect agendas show what is elected and for against agendas do not', function () {
    [$game, $speaker] = makeGame();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->set('newAgendaTitle', 'Custom')
        ->set('newAgendaDescription', 'Something')
        ->set('newAgendaCardType', 'directive')
        ->set('agendaType', 'elect_player')
        ->call('createAgenda')
        ->assertSeeHtml('<p class="agenda-card-elect">Elect Player</p>')
        ->assertSeeHtml('<span>directive</span>');

    $game->currentAgenda()->update(['status' => 'completed']);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->set('selectedPresetAgenda', 'Publicize Weapon Schematics')
        ->call('createAgenda')
        ->assertDontSeeHtml('agenda-card-elect');
});

test('speaker can create a new agenda after ending voting', function () {
    [$game, $speaker] = makeGame();
    makeAgenda($game);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->assertSee('End Voting')
        ->call('endVoting')
        ->assertDontSee('End Voting')
        ->assertSee('Create New Agenda')
        ->call('toggleCreateAgenda')
        ->set('newAgendaTitle', 'Next Agenda')
        ->set('newAgendaDescription', 'Something')
        ->set('newAgendaCardType', 'law')
        ->call('createAgenda');

    expect($game->currentAgenda()->title)->toBe('Next Agenda');
});

test('custom agendas must be a law or a directive', function () {
    [$game, $speaker] = makeGame();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->set('newAgendaTitle', 'Custom')
        ->set('newAgendaDescription', 'Something')
        ->call('createAgenda')
        ->assertHasErrors('newAgendaCardType');

    expect($game->currentAgenda())->toBeNull();
});

test('voting opens only after the speaker checked when and after revealed cards', function () {
    [$game, $speaker, $other] = makeGame();

    $speakerView = Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->set('selectedPresetAgenda', 'Publicize Weapon Schematics')
        ->call('createAgenda')
        ->assertSee('Done, continue to "After" cards')
        ->assertDontSee('End Voting');

    expect($game->currentAgenda()->reveal_step)->toBe(Agenda::STEP_WHEN_REVEALED);

    // Players cannot vote yet
    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertSee('Voting has not started yet')
        ->assertDontSee('Submit Vote')
        ->set('selectedOption', 'For')
        ->set('influenceSpent', 3)
        ->call('submitVote');
    expect(Vote::count())->toBe(0);

    $speakerView->call('completeRevealStep')->assertSee('Done, start voting');
    expect($game->currentAgenda()->reveal_step)->toBe(Agenda::STEP_AFTER_REVEALED);

    $speakerView->call('completeRevealStep')->assertSee('End Voting');
    expect($game->currentAgenda()->isVotingOpen())->toBeTrue();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->set('selectedOption', 'For')
        ->set('influenceSpent', 3)
        ->call('submitVote');
    expect(Vote::count())->toBe(1);
});

test('only the speaker can continue or discard an agenda before voting', function () {
    [$game, , $other] = makeGame();
    $agenda = makeAgenda($game);
    $agenda->update(['reveal_step' => Agenda::STEP_WHEN_REVEALED]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->call('completeRevealStep')
        ->call('discardAgenda')
        ->call('endVoting');

    expect($agenda->fresh())
        ->reveal_step->toBe(Agenda::STEP_WHEN_REVEALED)
        ->status->toBe('voting');
});

test('speaker can discard a vetoed agenda and reveal another', function () {
    [$game, $speaker] = makeGame();
    $agenda = makeAgenda($game);
    $agenda->update(['reveal_step' => Agenda::STEP_WHEN_REVEALED]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('endVoting')
        ->call('discardAgenda')
        ->assertSee('Create New Agenda');

    expect(Agenda::find($agenda->id))->toBeNull();
});

test('speaker can block a player from voting during the when and after steps', function () {
    [$game, $speaker, $other] = makeGame();
    $agenda = makeAgenda($game);
    $agenda->update(['reveal_step' => Agenda::STEP_AFTER_REVEALED]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('toggleVotingBlock', $other->id)
        ->assertSee('Cannot vote');

    expect($agenda->fresh()->isPlayerBlocked($other))->toBeTrue();

    $agenda->update(['reveal_step' => null]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertSee('You cannot vote on this agenda')
        ->assertDontSee('Submit Vote')
        ->set('selectedOption', 'For')
        ->set('influenceSpent', 3)
        ->call('submitVote');
    expect(Vote::count())->toBe(0);

    // Only the Speaker has to vote now, after which all eligible players voted
    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->assertSee('0 / 1 players voted')
        ->set('selectedOption', 'For')
        ->set('influenceSpent', 3)
        ->call('submitVote')
        ->assertSee('Voting Results');
    expect($agenda->fresh()->allPlayersVoted())->toBeTrue();
});

test('voting blocks can be lifted but not changed once voting is open', function () {
    [$game, $speaker, $other] = makeGame();
    $agenda = makeAgenda($game);
    $agenda->update(['reveal_step' => Agenda::STEP_WHEN_REVEALED]);

    $speakerView = Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('toggleVotingBlock', $other->id)
        ->call('toggleVotingBlock', $other->id);
    expect($agenda->fresh()->isPlayerBlocked($other))->toBeFalse();

    $agenda->update(['reveal_step' => null]);
    $speakerView->call('toggleVotingBlock', $other->id);
    expect($agenda->fresh()->isPlayerBlocked($other))->toBeFalse();
});

test('only the speaker can block votes or remove players', function () {
    [$game, $speaker, $other] = makeGame();
    $agenda = makeAgenda($game);
    $agenda->update(['reveal_step' => Agenda::STEP_WHEN_REVEALED]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertDontSee('Block vote')
        ->assertDontSee('Remove')
        ->call('toggleVotingBlock', $speaker->id)
        ->call('removePlayer', $speaker->id);

    expect($agenda->fresh()->isPlayerBlocked($speaker))->toBeFalse()
        ->and(Player::find($speaker->id))->not->toBeNull();
});

test('speaker can remove another player but not themselves', function () {
    [$game, $speaker, $other] = makeGame();
    $agenda = makeAgenda($game);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $other->id, 'option' => 'For', 'influence_spent' => 2]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('removePlayer', $speaker->id)
        ->call('removePlayer', $other->id)
        ->assertDontSee('Other');

    expect(Player::find($other->id))->toBeNull()
        ->and(Player::find($speaker->id))->not->toBeNull()
        ->and(Vote::count())->toBe(0);
});

test('a removed player is sent back to the join page', function () {
    [$game, , $other] = makeGame();

    $otherView = Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other]);
    $other->delete();

    $otherView->call('refreshData')->assertRedirect(route('join-group', $game->code));
    $otherView->call('submitVote')->assertRedirect(route('join-group', $game->code));
});

test('pages show the app version', function () {
    $this->get('/')->assertSee('v'.config('app.version'));
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

test('every player sees the game rejoin link and no session tokens', function () {
    [$game, $speaker, $other] = makeGame();

    foreach ([[$speaker, $other], [$other, $speaker]] as [$viewer, $otherPlayer]) {
        Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $viewer])
            ->assertSee($game->rejoinUrl())
            ->assertDontSee($otherPlayer->session_token);
    }

    $this->get($game->rejoinUrl())->assertOk()->assertSee('Rejoin Game')->assertSee('Who are you?');
});

test('a player who lost access rejoins once another player confirms it', function () {
    Event::fake([TriggerRefresh::class]);
    [$game, $speaker, $other] = makeGame();

    // The Speaker lost their session and asks to rejoin from a new browser
    $request = Livewire::test(JoinGameForm::class, ['gameCode' => $game->code, 'rejoin' => true])
        ->assertSee('Speaker')
        ->set('playerName', 'Speaker')
        ->call('requestRejoin')
        ->assertSet('rejoinStatus', 'waiting')
        ->assertSee('Waiting for another player');

    // Nothing happens until someone confirms
    $request->call('checkRejoin')->assertSet('rejoinStatus', 'waiting');
    expect(rememberedGames())->toBe([]);

    // The requested player cannot confirm themselves, another player can
    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker->fresh()])
        ->assertDontSee('Someone wants to rejoin as Speaker')
        ->assertSee('Someone is asking to rejoin as you')
        ->call('approveRejoin', $speaker->id);
    expect($speaker->fresh()->rejoin_approved_at)->toBeNull();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertSee('Someone wants to rejoin as Speaker')
        ->assertSee('including the Speaker controls')
        ->call('approveRejoin', $speaker->id);

    $request->call('checkRejoin')->assertRedirect(route('game.show', $game->code));
    expect(rememberedGames())->toBe([$game->code => $speaker->session_token])
        ->and($speaker->fresh()->rejoin_request_token)->toBeNull();
});

test('a declined rejoin request does not let the browser in', function () {
    Event::fake([TriggerRefresh::class]);
    [$game, $speaker, $other] = makeGame();

    $request = Livewire::test(JoinGameForm::class, ['gameCode' => $game->code, 'rejoin' => true])
        ->set('playerName', 'Other')
        ->call('requestRejoin');

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('declineRejoin', $other->id);

    $request->call('checkRejoin')
        ->assertSet('rejoinStatus', 'declined')
        ->assertSee('Your request to rejoin was declined');
    expect(rememberedGames())->toBe([]);
});

test('a rejoin request expires when nobody confirms it', function () {
    Event::fake([TriggerRefresh::class]);
    [$game, , $other] = makeGame();

    $request = Livewire::test(JoinGameForm::class, ['gameCode' => $game->code, 'rejoin' => true])
        ->set('playerName', 'Other')
        ->call('requestRejoin');

    $this->travel(Player::REJOIN_REQUEST_MINUTES + 1)->minutes();

    $request->call('checkRejoin')->assertSet('rejoinStatus', 'expired');
    expect($other->fresh()->rejoin_request_token)->toBeNull();
});

test('joining with a taken name offers to ask to rejoin', function () {
    Event::fake([TriggerRefresh::class]);
    [$game, , $other] = makeGame();

    Livewire::test(JoinGameForm::class, ['gameCode' => $game->code])
        ->set('playerName', 'Other')
        ->call('joinGame')
        ->assertHasErrors('playerName')
        ->assertSee('Ask to rejoin as Other')
        ->call('requestRejoin')
        ->assertSet('rejoinStatus', 'waiting');

    expect($other->fresh()->hasPendingRejoinRequest())->toBeTrue();
});

test('a removed player is dropped from an open elect player agenda unless voted for', function () {
    [$game, $speaker, $other] = makeGame();
    $third = Player::create(['game_id' => $game->id, 'name' => 'Third', 'session_token' => Player::generateSessionToken()]);
    $agenda = makeAgenda($game);
    $agenda->update(['elect' => 'player', 'options' => ['Speaker', 'Other', 'Third', 'Abstain']]);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $speaker->id, 'option' => 'Third', 'influence_spent' => 2]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('removePlayer', $other->id)
        ->call('removePlayer', $third->id);

    expect($agenda->fresh()->options)->toBe(['Speaker', 'Third', 'Abstain']);
});

test('the milty url control of a removed player does not crash', function () {
    [$game, $speaker, $other] = makeGame();
    Http::fake();

    $control = Livewire::test(UpdateMiltyUrl::class, ['game' => $game, 'player' => $speaker]);
    $game->setSpeaker($other);
    $game->removePlayer($speaker->fresh());

    $control->set('miltyUrl', 'https://milty.shenanigans.be/d/abc123')
        ->call('updateMiltyUrl')
        ->assertDispatched('flashMessage');

    expect($game->fresh()->milty_url)->toBeNull();
    Http::assertNothingSent();
});

test('creating games is rate limited', function () {
    Http::fake();

    foreach (range(1, 10) as $attempt) {
        Livewire::test(CreateGame::class)->set('gameName', "Game {$attempt}")->set('playerName', 'Me')->call('createGame');
    }

    Livewire::test(CreateGame::class)
        ->set('gameName', 'One too many')
        ->set('playerName', 'Me')
        ->call('createGame')
        ->assertHasErrors('gameName');

    expect(Game::count())->toBe(10);
});

test('joining games is rate limited', function () {
    [$game] = makeGame();

    foreach (range(1, 30) as $attempt) {
        Livewire::test(JoinGameForm::class, ['gameCode' => 'NOPE00'])->set('playerName', 'X')->call('joinGame');
    }

    Livewire::test(JoinGameForm::class, ['gameCode' => $game->code])
        ->set('playerName', 'Late')
        ->call('joinGame')
        ->assertHasErrors('gameCode');

    expect($game->players()->where('name', 'Late')->exists())->toBeFalse();
});

test('preset planet agendas pick the matching planet type', function (string $preset, string $agendaType) {
    [$miltyGame, $miltySpeaker] = makeGame(['milty_draft_data' => ['draft' => ['players' => []], 'slices' => []]]);
    [$plainGame, $plainSpeaker] = makeGame();

    Livewire::test(GameDashboard::class, ['game' => $miltyGame, 'player' => $miltySpeaker])
        ->set('selectedPresetAgenda', $preset)
        ->assertSet('agendaType', $agendaType);

    // Without Milty data there are no planets to elect
    Livewire::test(GameDashboard::class, ['game' => $plainGame, 'player' => $plainSpeaker])
        ->set('selectedPresetAgenda', $preset)
        ->assertSet('agendaType', 'custom');
})->with([
    ['Compensated Disarmament', 'elect_planet_any'],
    ['Research Team: Biotic', 'elect_planet_industrial'],
    ['Demilitarized Zone', 'elect_planet_cultural'],
    ['Core Mining', 'elect_planet_hazardous'],
    ['Colonial Redistribution', 'elect_planet_non_home'],
]);

test('other preset agendas keep their voting type', function (string $preset, string $agendaType) {
    [$game, $speaker] = makeGame();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->set('selectedPresetAgenda', $preset)
        ->assertSet('agendaType', $agendaType);
})->with([
    ['Anti-Intellectual Revolution', 'for_against'],
    ['Committee Formation', 'elect_player'],
    ['Judicial Abolishment', 'custom'],
    ['Galactic Crisis Pact', 'for_against'],
]);

test('planet agendas list the planets once with abstain last', function () {
    [$game, $speaker] = makeGame(['milty_draft_data' => ['draft' => ['players' => []], 'slices' => []]]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->set('selectedPresetAgenda', 'Research Team: Biotic')
        ->set('customTileNumbers', '18')
        ->call('createAgenda');

    $options = $game->currentAgenda()->options;
    expect(array_is_list($options))->toBeTrue()
        ->and(end($options))->toBe('Abstain')
        ->and($options)->toContain('Mecatol Rex')
        ->and(count($options))->toBe(count(array_unique($options)));
});

test('with the argent flight in the milty draft the speaker gets an extra step before voting', function () {
    $draft = ['draft' => ['players' => [
        ['name' => 'Alice', 'faction' => 'The Argent Flight'],
        ['name' => 'Bob', 'faction' => 'The Federation of Sol'],
    ]], 'slices' => []];
    [$game, $speaker, $other] = makeGame(['milty_draft_data' => $draft]);
    $agenda = makeAgenda($game);
    $agenda->update(['reveal_step' => Agenda::STEP_AFTER_REVEALED]);

    $speakerView = Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->assertSee('Step 2 of 3')
        ->assertSee('Done, continue to The Argent Flight')
        ->call('completeRevealStep')
        ->assertSee('The Argent Flight votes first')
        ->assertSee('Alice votes before everyone else')
        ->assertSee('Done, start voting');

    expect($agenda->fresh()->reveal_step)->toBe(Agenda::STEP_ARGENT_VOTES);

    // Voting is still closed while the Argent Flight announces their votes
    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertSee('The Argent Flight votes first and announces their votes')
        ->set('selectedOption', 'For')
        ->set('influenceSpent', 3)
        ->call('submitVote');
    expect(Vote::count())->toBe(0);

    $speakerView->call('completeRevealStep');
    expect($agenda->fresh()->isVotingOpen())->toBeTrue();
});

test('the argent flight is also recognised from a player faction', function () {
    [$game, $speaker, $other] = makeGame();
    $other->update(['faction' => 'Argent Flight']);
    $agenda = makeAgenda($game);
    $agenda->update(['reveal_step' => Agenda::STEP_ARGENT_VOTES]);

    expect($game->hasArgentFlight())->toBeTrue()
        ->and($game->argentFlightPlayerName())->toBe('Other');

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertSee('You play The Argent Flight, so you vote first');
});

test('without the argent flight voting opens after the after step', function () {
    [$game, $speaker] = makeGame();
    $agenda = makeAgenda($game);
    $agenda->update(['reveal_step' => Agenda::STEP_AFTER_REVEALED]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->assertSee('Step 2 of 2')
        ->call('completeRevealStep');

    expect($agenda->fresh()->isVotingOpen())->toBeTrue();
});

test('representative government passing for gives every player 1 vote', function () {
    [$game, $speaker, $other] = makeGame();
    $agenda = makeAgenda($game);
    $agenda->update(['title' => 'Representative Government']);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $speaker->id, 'option' => 'For', 'influence_spent' => 5]);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $other->id, 'option' => 'Against', 'influence_spent' => 3]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('endVoting')
        ->assertDispatched('flashMessage');

    expect($game->fresh()->representative_government)->toBeTrue();

    // The next agenda counts 1 vote per player, whatever influence is entered
    makeAgenda($game);
    Livewire::test(GameDashboard::class, ['game' => $game->fresh(), 'player' => $other])
        ->assertSee('your vote counts as 1 vote')
        ->assertDontSee('Influence to spend')
        ->set('selectedOption', 'For')
        ->set('influenceSpent', 12)
        ->call('submitVote')
        ->assertHasNoErrors();

    expect(Vote::where('player_id', $other->id)->latest('id')->first()->influence_spent)->toBe(1);
});

test('representative government voted against or tied is not put in effect', function (int $for, int $against) {
    [$game, $speaker, $other] = makeGame();
    $agenda = makeAgenda($game);
    $agenda->update(['title' => 'Representative Government']);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $speaker->id, 'option' => 'For', 'influence_spent' => $for]);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $other->id, 'option' => 'Against', 'influence_spent' => $against]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])->call('endVoting');

    expect($game->fresh()->representative_government)->toBeFalse();
})->with([[2, 6], [4, 4]]);

function makeMutiny(Game $game): Agenda
{
    $agenda = makeAgenda($game);
    $agenda->update([
        'title' => 'Mutiny',
        'description' => "FOR : Each player who voted \"For\" gains 1 victory point.\nAGAINST : Each player who voted \"For\" loses 1 victory point.",
    ]);

    return $agenda;
}

test('after an agenda that affects voters the speaker can show who is affected', function () {
    [$game, $speaker, $other] = makeGame();
    $agenda = makeMutiny($game);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $speaker->id, 'option' => 'For', 'influence_spent' => 2]);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $other->id, 'option' => 'Against', 'influence_spent' => 5]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('endVoting')
        ->assertSee('Against wins')
        ->assertDontSee('Players who voted')
        ->call('toggleAffectedPlayers')
        ->assertSee('Each player who voted &quot;For&quot; loses 1 victory point.', false)
        ->assertSeeInOrder(['Players who voted', 'For', 'Speaker']);

    expect($agenda->fresh()->playersAffectedBy('Against'))->toBe(['For' => ['Speaker']])
        ->and($agenda->fresh()->playersAffectedBy('For'))->toBe(['For' => ['Speaker']]);
});

test('agendas that do not depend on how players voted have no affected players', function () {
    [$game, $speaker] = makeGame();
    $agenda = makeAgenda($game);
    $agenda->update(['description' => "FOR : Players cannot use BOMBARDMENT against units that are on cultural planets.\nAGAINST : Each player that voted \"Against\" discards all of their action cards."]);

    expect(makeAgenda($game)->affectsVoters())->toBeFalse()
        ->and($agenda->fresh()->affectsVoters())->toBeTrue()
        ->and($agenda->fresh()->playersAffectedBy('For'))->toBe([])
        ->and($agenda->fresh()->playersAffectedBy('Against'))->toBe(['Against' => []]);
});

test('on a tie the speaker breaks it before seeing who is affected', function () {
    [$game, $speaker, $other] = makeGame();
    $agenda = makeMutiny($game);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $speaker->id, 'option' => 'For', 'influence_spent' => 4]);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $other->id, 'option' => 'Against', 'influence_spent' => 4]);

    $view = Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('endVoting')
        ->assertSee('The vote is tied')
        ->assertDontSee('Show who is affected');

    // Only the tied options can win, and only the Speaker decides
    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])->call('breakTie', 'For');
    $view->call('breakTie', 'Abstain');
    expect($agenda->fresh()->tiebreak_option)->toBeNull();

    $view->call('breakTie', 'For')
        ->assertSee('For wins (tie broken by the Speaker)')
        ->call('toggleAffectedPlayers')
        ->assertSee('gains 1 victory point');

    expect($agenda->fresh()->outcome())->toBe('For');
});

test('representative government is put in effect when the speaker breaks a tie for it', function () {
    [$game, $speaker, $other] = makeGame();
    $agenda = makeAgenda($game);
    $agenda->update([
        'title' => 'Representative Government',
        'description' => "FOR : Each player may cast 1 vote on each agenda instead.\nAGAINST : Each player that voted \"Against\" exhausts all of their cultural planets.",
    ]);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $speaker->id, 'option' => 'For', 'influence_spent' => 3]);
    Vote::create(['agenda_id' => $agenda->id, 'player_id' => $other->id, 'option' => 'Against', 'influence_spent' => 3]);

    $view = Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])->call('endVoting');
    expect($game->fresh()->representative_government)->toBeFalse();

    $view->call('breakTie', 'For');
    expect($game->fresh()->representative_government)->toBeTrue();
});

test('only the speaker can turn representative government on or off', function () {
    [$game, $speaker, $other] = makeGame();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertDontSee('Representative Government is in effect')
        ->call('toggleRepresentativeGovernment');
    expect($game->fresh()->representative_government)->toBeFalse();

    $speakerView = Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('toggleRepresentativeGovernment');
    expect($game->fresh()->representative_government)->toBeTrue();

    // Removed again, e.g. by Repeal Law
    $speakerView->call('toggleRepresentativeGovernment');
    expect($game->fresh()->representative_government)->toBeFalse();
});

test('the preset agenda search lists every preset and fills in the form', function () {
    [$game, $speaker] = makeGame();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('toggleCreateAgenda')
        ->assertSee('Search agendas')
        ->assertSee('role="combobox"', false)
        ->assertSee('Representative Government')
        ->set('selectedPresetAgenda', 'Mutiny')
        ->assertSet('newAgendaTitle', 'Mutiny')
        ->assertSet('newAgendaCardType', 'directive');
});

test('every player sees when representative government is in effect', function () {
    [$game, $speaker, $other] = makeGame();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertDontSee('Players cannot exhaust planets to vote');

    $game->update(['representative_government' => true]);

    foreach ([$speaker, $other] as $viewer) {
        Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $viewer])
            ->assertSee('Representative Government is in effect')
            ->assertSee('Players cannot exhaust planets to vote');
    }
});

test('the site is an installable web app', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)
        ->name->toBe('TI4 Hidden Agenda')
        ->display->toBe('standalone')
        ->start_url->toBe('/');

    foreach ($manifest['icons'] as $icon) {
        expect(file_exists(public_path(ltrim($icon['src'], '/'))))->toBeTrue("Missing icon {$icon['src']}");
    }
    expect(collect($manifest['icons'])->pluck('purpose'))->toContain('maskable');

    expect(file_exists(public_path('sw.js')))->toBeTrue()
        ->and(file_exists(public_path('offline.html')))->toBeTrue();

    $this->get('/')
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.json">', false)
        ->assertSee('apple-mobile-web-app-capable', false)
        ->assertSee('Add it to your home screen');
});

test('the homepage offers to continue the game this browser plays', function () {
    [$game, , $other] = makeGame();

    $this->get('/')->assertDontSee('Continue your game');

    $this->withSession(['player_token' => $other->session_token])
        ->get('/')
        ->assertSee('Continue your game')
        ->assertSee('Playing as Other')
        ->assertSee(route('game.show', $game->code));
});

test('the game page offers to install the web app', function () {
    [$game, , $other] = makeGame();

    $this->withSession(['player_token' => $other->session_token])
        ->get(route('game.show', $game->code))
        ->assertOk()
        ->assertSee('Add it to your home screen')
        ->assertSee('Install app');
});

test('a browser can play in several games and continue each of them', function () {
    [$first, , $firstPlayer] = makeGame(['name' => 'Friday Game']);
    [$second, $secondPlayer] = makeGame(['name' => 'Sunday Game']);
    $cookie = json_encode([$second->code => $secondPlayer->session_token, $first->code => $firstPlayer->session_token]);

    $this->withCookie(PlayerGames::COOKIE, $cookie)
        ->get('/')
        ->assertSeeInOrder(['Continue one of your games', 'Sunday Game', 'Playing as Speaker (Speaker)', 'Friday Game', 'Playing as Other']);

    // Both game pages open without a session, e.g. after it expired between agenda phases
    $this->withCookie(PlayerGames::COOKIE, $cookie)->get(route('game.show', $first->code))->assertOk()->assertSee('Friday Game');
    $this->withCookie(PlayerGames::COOKIE, $cookie)->get(route('game.show', $second->code))->assertOk()->assertSee('Sunday Game');
});

test('joining a game adds it to the games this browser remembers', function () {
    Event::fake([TriggerRefresh::class]);
    [$first, , $firstPlayer] = makeGame();
    [$second] = makeGame();

    Livewire::withCookie(PlayerGames::COOKIE, json_encode([$first->code => $firstPlayer->session_token]))
        ->test(JoinGameForm::class, ['gameCode' => $second->code])
        ->set('playerName', 'Newcomer')
        ->call('joinGame')
        ->assertRedirect(route('game.show', $second->code));

    $newcomer = $second->players()->where('name', 'Newcomer')->first();
    expect(rememberedGames())->toBe([$second->code => $newcomer->session_token, $first->code => $firstPlayer->session_token]);
});

test('a player from before the games cookie keeps their game', function () {
    [$game, , $other] = makeGame();

    $this->withSession(['player_token' => $other->session_token])
        ->get(route('game.show', $game->code))
        ->assertOk();

    expect(rememberedGames())->toBe([$game->code => $other->session_token]);
});

test('deleted games and removed players drop off the list', function () {
    [$kept, , $keptPlayer] = makeGame(['name' => 'Kept Game']);
    [$deleted, $deletedPlayer] = makeGame(['name' => 'Deleted Game']);
    [$removedFrom, , $removedPlayer] = makeGame(['name' => 'Removed From Game']);
    $cookie = json_encode([
        $kept->code => $keptPlayer->session_token,
        $deleted->code => $deletedPlayer->session_token,
        $removedFrom->code => $removedPlayer->session_token,
    ]);
    $deleted->delete();
    $removedPlayer->delete();

    $this->withCookie(PlayerGames::COOKIE, $cookie)
        ->get('/')
        ->assertSee('Continue your game')
        ->assertSee('Kept Game')
        ->assertDontSee('Deleted Game')
        ->assertDontSee('Removed From Game');
});

function draftWithFactions(): array
{
    return ['draft' => ['players' => [
        'p_1' => ['name' => 'Alice', 'faction' => 'The Argent Flight', 'slice' => '0'],
        'p_2' => ['name' => 'Bob', 'faction' => 'The Federation of Sol', 'slice' => '1'],
        'p_3' => ['name' => 'Carol', 'faction' => 'The Emirates of Hacan', 'slice' => '2'],
    ]], 'slices' => [['tiles' => []], ['tiles' => []], ['tiles' => []]], 'config' => []];
}

test('creating a game with a milty draft asks for a drafted faction', function () {
    Http::fake(['milty.shenanigans.be/*' => Http::response(draftWithFactions())]);

    $form = Livewire::test(CreateGame::class)
        ->set('gameName', 'Drafted Game')
        ->set('playerName', 'Alice')
        ->assertDontSee('Your Faction')
        ->set('miltyUrl', 'https://milty.shenanigans.be/d/abc123')
        ->assertSee('Draft loaded with 3 factions')
        ->assertSee('The Argent Flight (Alice)')
        ->call('createGame')
        ->assertHasErrors(['faction' => 'required']);

    $form->set('faction', 'Not A Drafted Faction')->call('createGame')->assertHasErrors('faction');

    $form->set('faction', 'The Argent Flight')->call('createGame')->assertHasNoErrors()->assertRedirect();
    expect(Player::where('name', 'Alice')->first()->faction)->toBe('The Argent Flight');
});

test('creating a game without milty has no faction', function () {
    Livewire::test(CreateGame::class)
        ->assertDontSee('Your Faction')
        ->set('gameName', 'Plain Game')
        ->set('playerName', 'Me')
        ->set('faction', 'Typed Faction')
        ->call('createGame')
        ->assertRedirect();

    expect(Player::where('name', 'Me')->first()->faction)->toBeNull();
});

test('joining a milty game offers the drafted factions nobody chose yet', function () {
    Event::fake([TriggerRefresh::class]);
    [$game, $speaker] = makeGame(['milty_draft_data' => draftWithFactions()]);
    $speaker->update(['faction' => 'The Argent Flight']);

    $form = Livewire::test(JoinGameForm::class, ['gameCode' => $game->code])
        ->assertSee('The Federation of Sol (Bob)')
        ->assertDontSee('The Argent Flight (Alice)')
        ->set('playerName', 'Bob')
        ->call('joinGame')
        ->assertHasErrors(['faction' => 'required']);

    $form->set('faction', 'The Argent Flight')->call('joinGame')->assertHasErrors('faction');
    $form->set('faction', 'The Federation of Sol')->call('joinGame')->assertRedirect();

    expect($game->players()->where('name', 'Bob')->first()->faction)->toBe('The Federation of Sol');
});

test('joining a game without milty has no faction', function () {
    Event::fake([TriggerRefresh::class]);
    [$game] = makeGame();

    Livewire::test(JoinGameForm::class, ['gameCode' => $game->code])
        ->assertDontSee('Your Faction')
        ->set('playerName', 'Plain')
        ->call('joinGame')
        ->assertRedirect();

    expect($game->players()->where('name', 'Plain')->first()->faction)->toBeNull();
});

test('players without a drafted faction are asked to choose one in the game', function () {
    Event::fake([TriggerRefresh::class]);
    [$game, $speaker, $other] = makeGame(['milty_draft_data' => draftWithFactions()]);
    $speaker->update(['faction' => 'The Emirates of Hacan']);

    // The Speaker already has a drafted faction
    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker->fresh()])
        ->assertDontSee('Choose your faction');

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertSee('Choose your faction')
        ->assertDontSee('The Emirates of Hacan (Carol)')
        ->set('chosenFaction', 'The Emirates of Hacan')
        ->call('chooseFaction')
        ->assertHasErrors('chosenFaction')
        ->set('chosenFaction', 'The Federation of Sol')
        ->call('chooseFaction')
        ->assertHasNoErrors()
        ->assertDontSee('Choose your faction');

    expect($other->fresh()->faction)->toBe('The Federation of Sol');
});

test('the game page links back home', function () {
    [$game, , $other] = makeGame();

    $this->withCookie(PlayerGames::COOKIE, json_encode([$game->code => $other->session_token]))
        ->get(route('game.show', $game->code))
        ->assertOk()
        ->assertSee('href="' . route('home') . '"', false);
});

test('a game can be removed from this browser list without leaving it', function () {
    [$kept, , $keptPlayer] = makeGame(['name' => 'Kept Game']);
    [$removed, , $removedPlayer] = makeGame(['name' => 'Removed Game']);
    $cookie = json_encode([$removed->code => $removedPlayer->session_token, $kept->code => $keptPlayer->session_token]);

    $this->withCookie(PlayerGames::COOKIE, $cookie)
        ->get('/')
        ->assertSee(route('my-games.forget', $removed->code));

    $this->withCookie(PlayerGames::COOKIE, $cookie)
        ->post(route('my-games.forget', $removed->code))
        ->assertRedirect(route('home'))
        ->assertCookie(PlayerGames::COOKIE, json_encode([$kept->code => $keptPlayer->session_token]));

    // Still a player in the game
    expect(Player::find($removedPlayer->id))->not->toBeNull();
});

test('removing a game also clears it from the old session', function () {
    [$game, , $other] = makeGame(['name' => 'Session Game']);

    $this->withSession(['player_token' => $other->session_token])
        ->post(route('my-games.forget', $game->code));

    expect(session('player_token'))->toBeNull();
    $this->get('/')->assertDontSee('Session Game');
});

function starChartDraft(array $config = []): array
{
    return [
        'config' => array_merge(['include_pok' => false, 'include_te_tiles' => false, 'include_ds_tiles' => false], $config),
        'draft' => ['players' => [
            'p_1' => ['name' => 'Alice', 'faction' => 'The Federation of Sol', 'slice' => '0'],
            'p_2' => ['name' => 'Bob', 'faction' => null, 'slice' => null],
        ]],
        // Slice 1 was not picked, so its tiles are not on the map
        'slices' => [['tiles' => [19, 20, 21, 39]], ['tiles' => [22, 23]]],
    ];
}

test('the star chart pool holds unused blue tiles of the expansions in the draft', function () {
    [$base] = makeGame(['milty_draft_data' => starChartDraft()]);
    [$expanded] = makeGame(['milty_draft_data' => starChartDraft(['include_pok' => true, 'include_te_tiles' => true, 'include_ds_tiles' => true])]);

    $pool = $base->starChartPool();
    expect($pool->all())->toBe(array_map('strval', range(22, 38)))
        ->and($pool)->not->toContain('18');

    expect($expanded->starChartPool())
        ->toContain('22', '59', '97', '4253')
        ->not->toContain('18', '19', '82', '112', '39');
});

test('any player can draw a star chart tile and everyone sees it', function () {
    Event::fake([TriggerRefresh::class]);
    [$game, $speaker, $other] = makeGame(['milty_draft_data' => starChartDraft()]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertSee('Star Chart')
        ->assertSee('17 tiles left')
        ->call('drawStarChart');

    $draw = $game->fresh()->star_chart_tiles[0];
    expect($draw['player'])->toBe('Other')
        ->and(range(22, 38))->toContain((int) $draw['tile'])
        ->and($game->fresh()->starChartPool())->not->toContain($draw['tile']);

    $planet = app(\App\Services\PlanetService::class)->getTile($draw['tile'])['planets'][0]['name'];
    Livewire::test(GameDashboard::class, ['game' => $game->fresh(), 'player' => $speaker])
        ->assertSee('16 tiles left')
        ->assertSee($planet)
        ->assertSee('Drawn by Other');

    // A drawn system is on the map, so its planets can be elected
    expect($game->fresh()->getPlanets()->pluck('name'))->toContain($planet);
});

test('star chart stops when no tiles are left', function () {
    Event::fake([TriggerRefresh::class]);
    [$game, $speaker] = makeGame(['milty_draft_data' => starChartDraft()]);
    $game->update(['star_chart_tiles' => array_map(fn ($tile) => ['tile' => (string) $tile, 'player' => 'X', 'drawn_at' => now()->toIso8601String()], range(22, 38))]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->assertSee('0 tiles left')
        ->call('drawStarChart')
        ->assertDispatched('flashMessage');

    expect($game->fresh()->star_chart_tiles)->toHaveCount(17);
});

test('only the speaker can undo a star chart draw', function () {
    Event::fake([TriggerRefresh::class]);
    [$game, $speaker, $other] = makeGame(['milty_draft_data' => starChartDraft()]);
    $game->update(['star_chart_tiles' => [['tile' => '25', 'player' => 'Other', 'drawn_at' => now()->toIso8601String()]]]);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $other])
        ->assertDontSee('Undo drawing tile 25')
        ->call('removeStarChartTile', '25');
    expect($game->fresh()->starChartTileNumbers()->all())->toBe(['25']);

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->call('removeStarChartTile', '25');
    expect($game->fresh()->star_chart_tiles)->toBeNull()
        ->and($game->fresh()->starChartPool())->toContain('25');
});

test('star chart needs a milty draft', function () {
    [$game, $speaker] = makeGame();

    Livewire::test(GameDashboard::class, ['game' => $game, 'player' => $speaker])
        ->assertDontSee('Draw a system tile')
        ->call('drawStarChart');

    expect($game->fresh()->star_chart_tiles)->toBeNull();
});
