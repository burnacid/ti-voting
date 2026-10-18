<?php

use App\Events\TriggerRefresh;
use App\Livewire\CreateGame;
use App\Livewire\UpdateMiltyUrl;
use App\Models\Game;
use App\Models\Player;
use App\Services\MapStringService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Event::fake([TriggerRefresh::class]);
});

const EXAMPLE_MAP_STRING = '41 50 73 31 59 40 24 26 30 39 110 4274 29 25 66 33 32 115 92 69 76 15 20 4275 4207 22 42 4201 68 4270 57 77 102 118 109 48';

// A trimmed TI4 Lab draft page, with the draft serialized the way the page embeds it
function tiDraftPage(): string
{
    $tile = fn ($idx, $id) => "\$R[1$idx]={idx:$idx,position:\$R[2$idx]={x:0,y:0},type:\"SYSTEM\",systemId:\"$id\"}";

    return '<html><body><script data-tsr-stream-part="">$_TSR.router=($R=>$R[0]={matches:$R[14]=[$R[17]={i:"x",s:"success",l:$R[18]={id:"e5ab",'
        . 'urlName:"training-people-operations",data:$R[19]={settings:$R[20]={factionGameSets:$R[21]=["base","pok"],'
        . 'tileGameSets:$R[22]=["base","pok","te"],draftSpeaker:!0,allowEmptyTiles:!1},'
        . 'players:$R[26]=[$R[27]={id:0,name:"Alice"},$R[28]={id:1,name:"Bob <3"}],integrations:$R[33]={},'
        . 'selections:$R[35]=[$R[37]={type:"SELECT_FACTION",playerId:0,factionId:"argent"},$R[38]={type:"SELECT_FACTION",playerId:1,factionId:"l1z1x"},'
        . '$R[39]={type:"SELECT_SLICE",playerId:0,sliceIdx:1}],'
        . 'slices:$R[61]=[$R[62]={name:"Slice A",tiles:$R[63]=[$R[64]={idx:0,type:"HOME"},' . $tile(1, '19') . ']},'
        . '$R[76]={name:"Slice B",tiles:$R[77]=[' . $tile(2, '26') . ',' . $tile(3, '110') . ']}],'
        . 'presetMap:$R[188]=[$R[189]={idx:0,type:"SYSTEM",systemId:"18"},$R[190]={idx:1,type:"SYSTEM",systemId:"25"},$R[191]={idx:2,type:"OPEN"}],'
        . 'stagedSelections:$R[263]={}},type:"milty",isComplete:!1,stream:void 0},ssr:!0}]}))($R["tsr"])</script></body></html>';
}

test('a map string gives the tiles and the factions of its home systems', function () {
    $data = (new MapStringService())->toMiltyData(EXAMPLE_MAP_STRING);

    expect(Game::factionsFromDraft($data)->keys()->all())->toBe([
        "L'Tokk Khrask", 'Last Bastion', 'The Crimson Rebellion', 'The Naaz-Rokha Alliance', 'The Yssaril Tribes', 'Tnelis Syndicate',
    ])
        ->and($data['map_tiles'])->toContain('41', '110', '4274', '118')->not->toContain('15', '92')
        ->and($data['config'])->toMatchArray(['include_pok' => true, 'include_te_tiles' => true, 'include_ds_tiles' => true]);
});

test('a map string can be separated by commas and hold rotated hyperlanes', function () {
    $data = (new MapStringService())->toMiltyData('{18},26, 83a2,0,-1, 1');

    expect($data['map_tiles'])->toBe(['26', '83A'])
        ->and(Game::factionsFromDraft($data)->keys()->all())->toBe(['The Federation of Sol']);
});

test('text that is not a map string is rejected', function () {
    expect((new MapStringService())->toMiltyData('hello world'))->toBeNull()
        ->and((new MapStringService())->toMiltyData('9999 9998'))->toBeNull();
});

test('creating a game with a map string lists its planets and factions', function () {
    Http::fake();

    Livewire::test(CreateGame::class)
        ->set('gameName', 'Map Game')
        ->set('playerName', 'Alice')
        ->set('miltyUrl', EXAMPLE_MAP_STRING)
        ->assertSee('Map loaded with 6 factions')
        ->set('faction', 'Last Bastion')
        ->call('createGame')
        ->assertHasNoErrors()
        ->assertRedirect();

    Http::assertNothingSent();

    $game = Game::first();
    expect($game->milty_url)->toBeNull()
        ->and($game->getPlanets()->keys())->toContain('Mecatol Rex', 'Lodor', 'Retillion')
        ->and($game->starChartPool())->not->toContain('41', '73');
});

test('a tidraft url loads the players, factions and slices of the draft', function () {
    Http::fake(['tidraft.com/*' => Http::response(tiDraftPage())]);

    Livewire::test(CreateGame::class)
        ->set('gameName', 'Lab Game')
        ->set('playerName', 'Alice')
        ->set('miltyUrl', 'https://tidraft.com/draft/training-people-operations')
        ->assertSee('Draft loaded with 2 factions')
        ->assertSee('The Argent Flight (Alice)')
        ->assertSee('The Lizix Mindnet (Bob &lt;3)', false)
        ->set('faction', 'The Argent Flight')
        ->call('createGame')
        ->assertHasNoErrors();

    $game = Game::first();
    expect($game->milty_url)->toBe('https://tidraft.com/draft/training-people-operations')
        ->and($game->milty_draft_id)->toBe('training-people-operations')
        ->and($game->milty_draft_data['config'])->toMatchArray(['include_pok' => true, 'include_te_tiles' => true, 'include_ds_tiles' => false])
        // Alice picked slice B, and tile 25 was on the map before the draft
        ->and($game->starChartPool())->not->toContain('26', '25')->toContain('19');
});

test('an unreadable tidraft page shows an error', function () {
    Http::fake(['tidraft.com/*' => Http::response('<html></html>')]);

    Livewire::test(CreateGame::class)
        ->set('gameName', 'Lab Game')
        ->set('playerName', 'Alice')
        ->set('miltyUrl', 'https://tidraft.com/draft/nope')
        ->call('createGame')
        ->assertHasErrors('miltyUrl');

    expect(Game::count())->toBe(0);
});

test('the speaker can change the map to a map string and back to a url', function () {
    $game = Game::create(['code' => Game::generateCode(), 'name' => 'Test', 'status' => 'waiting']);
    $speaker = Player::create([
        'game_id' => $game->id, 'name' => 'Speaker', 'session_token' => Player::generateSessionToken(), 'is_speaker' => true,
    ]);

    Livewire::test(UpdateMiltyUrl::class, ['game' => $game, 'player' => $speaker])
        ->set('miltyUrl', EXAMPLE_MAP_STRING)
        ->call('updateMiltyUrl')
        ->assertHasNoErrors();

    expect($game->fresh()->milty_draft_data['map_string'])->toBe(EXAMPLE_MAP_STRING);

    // The modal shows the map string again
    Livewire::test(UpdateMiltyUrl::class, ['game' => $game->fresh(), 'player' => $speaker])
        ->assertSet('miltyUrl', EXAMPLE_MAP_STRING)
        ->set('miltyUrl', 'not a map')
        ->call('updateMiltyUrl')
        ->assertHasErrors('miltyUrl');

    Http::fake(['milty.shenanigans.be/*' => Http::response(['draft' => ['players' => []], 'slices' => []])]);
    Livewire::test(UpdateMiltyUrl::class, ['game' => $game->fresh(), 'player' => $speaker])
        ->set('miltyUrl', 'https://milty.shenanigans.be/d/abc123')
        ->call('updateMiltyUrl')
        ->assertHasNoErrors();

    expect($game->fresh())
        ->milty_url->toBe('https://milty.shenanigans.be/d/abc123')
        ->milty_draft_data->not->toHaveKey('map_string');
});
