<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Loads the map of a game from a Milty draft URL, a TI4 Lab draft URL or a map string,
 * as the milty_url, milty_draft_id and milty_draft_data of the game.
 */
class DraftImporter
{
    public const HELP = 'Enter a Milty draft URL (https://milty.shenanigans.be/d/...), a TI4 Lab draft URL (https://tidraft.com/draft/...) or a map string of tile numbers.';

    /**
     * @return array{milty_url: ?string, milty_draft_id: ?string, milty_draft_data: array}
     * @throws InvalidArgumentException with a message for the player when the input cannot be loaded
     */
    public function import(string $input): array
    {
        $input = trim($input);
        $mapStrings = new MapStringService();

        if ($mapStrings->isMapString($input)) {
            $data = $mapStrings->toMiltyData($input)
                ?? throw new InvalidArgumentException('The map string has no known system tiles.');

            return ['milty_url' => null, 'milty_draft_id' => null, 'milty_draft_data' => $data];
        }

        if (!filter_var($input, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(self::HELP);
        }

        $tiDraft = new TiDraftService();
        if ($draftId = $tiDraft->extractDraftId($input)) {
            $data = $tiDraft->fetchDraftData($draftId)
                ?? throw new InvalidArgumentException('Could not load the TI4 Lab draft. Please check the URL and try again.');

            return ['milty_url' => $input, 'milty_draft_id' => $draftId, 'milty_draft_data' => $data];
        }

        $milty = new MiltyService();
        if ($draftId = $milty->extractDraftId($input)) {
            $data = $milty->fetchDraftData($draftId)
                ?? throw new InvalidArgumentException('Could not fetch Milty draft data. Please check the URL and try again.');

            return ['milty_url' => $input, 'milty_draft_id' => $draftId, 'milty_draft_data' => $data];
        }

        throw new InvalidArgumentException(self::HELP);
    }

    /**
     * What the Speaker entered for this game: its draft URL or map string.
     */
    public static function inputOf($game): string
    {
        return $game->milty_url ?? $game->milty_draft_data['map_string'] ?? '';
    }
}
