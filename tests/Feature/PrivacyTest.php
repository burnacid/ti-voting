<?php

test('the privacy policy is linked in the footer', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('privacy'));
});

test('the privacy policy shows the configured retention periods', function () {
    config(['app.game_retention_days' => 9, 'logging.channels.database.days' => 21]);

    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('after 9 days without any activity')
        ->assertSee('deleted after 21 days');
});

test('the privacy policy only mentions feedback when it is enabled', function () {
    config(['app.feedback' => false]);
    $this->get(route('privacy'))->assertDontSee('When you send feedback');

    config(['app.feedback' => true]);
    $this->get(route('privacy'))->assertSee('When you send feedback');
});

test('no page loads fonts from google', function () {
    [$game, $player] = makeGame();

    $this->withCookie(\App\Support\PlayerGames::COOKIE, json_encode([$game->code => $player->session_token]))
        ->get(route('game.show', $game->code))
        ->assertOk()
        ->assertDontSee('fonts.googleapis.com');
});
