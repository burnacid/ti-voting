<?php

namespace App\Support;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

/**
 * The games this browser plays in, kept in a long-lived encrypted cookie as game code => player session token.
 * It does not depend on the session, so players stay in their games when the session expires between agenda phases.
 */
class PlayerGames
{
    public const COOKIE = 'ti4_games';

    // Renewed on every visit, games without activity are deleted much sooner
    private const DAYS = 30;

    private const MAX_GAMES = 20;

    /**
     * Game code => session token, most recently used first.
     */
    public static function tokens(): array
    {
        $tokens = json_decode((string) request()->cookie(self::COOKIE), true);

        return is_array($tokens) ? array_filter($tokens, 'is_string') : [];
    }

    public static function remember(Player $player): void
    {
        $tokens = [$player->game->code => $player->session_token] + self::tokens();

        self::store(array_slice($tokens, 0, self::MAX_GAMES, true));
    }

    public static function forget(string $code): void
    {
        $tokens = self::tokens();
        unset($tokens[$code]);

        self::store($tokens);
    }

    /**
     * This browser's player in the game, if it plays in it.
     */
    public static function playerFor(Game $game): ?Player
    {
        // Before this cookie existed, the player was only kept in the session
        $token = self::tokens()[$game->code] ?? session('player_token');

        return $token ? $game->players()->where('session_token', $token)->first() : null;
    }

    /**
     * This browser's players in games that still exist, most recently used first.
     */
    public static function players(): Collection
    {
        $tokens = self::tokens();
        // Before this cookie existed, the player was only kept in the session
        $legacy = session('player_token');
        if (!$tokens && !$legacy) {
            return collect();
        }

        $order = array_flip(array_values($tokens));

        return Player::with('game')
            ->whereIn('session_token', array_filter([...array_values($tokens), $legacy]))
            ->get()
            ->filter(fn (Player $player) => ($tokens[$player->game->code] ?? $legacy) === $player->session_token)
            ->sortBy(fn (Player $player) => $order[$player->session_token] ?? PHP_INT_MAX)
            ->values();
    }

    private static function store(array $tokens): void
    {
        Cookie::queue(self::COOKIE, json_encode($tokens), self::DAYS * 24 * 60);
    }
}
