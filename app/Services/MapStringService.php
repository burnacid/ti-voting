<?php

namespace App\Services;

/**
 * Reads map strings as other draft tools export them: system tile numbers, separated by spaces or commas,
 * ring by ring around Mecatol Rex, e.g. "41 50 73 31 59 40 24 ...". Home systems give the factions in play.
 */
class MapStringService
{
    // Expansion of each system tile in planets.json and the Milty setting that puts its tiles in the game
    private const TILE_SETS = [
        'PoK' => 'include_pok',
        'TE' => 'include_te_tiles',
        'DSPlus' => 'include_ds_tiles',
    ];

    // Home systems that planets.json has no faction for. Their planets stay on the map, as no faction owns them there
    private const HOME_TILES = [
        '118' => 'The Crimson Rebellion',
    ];

    public function isMapString(string $input): bool
    {
        return preg_match('/^\{?\d+[A-Za-z]?\d*\}?([\s,]+-?\d+[A-Za-z]?\d*)+$/', trim($input)) === 1;
    }

    /**
     * Convert a map string to the shape of a Milty draft, which the rest of the app reads.
     * Returns null when the string has no known system tiles.
     */
    public function toMiltyData(string $mapString): ?array
    {
        if (!$this->isMapString($mapString)) {
            return null;
        }

        $planetService = app(PlanetService::class);
        $tiles = [];
        $factions = [];
        $config = array_fill_keys(array_values(self::TILE_SETS), false) + ['include_te_factions' => false];

        foreach (preg_split('/[\s,]+/', trim($mapString, " \t\n\r,{}")) as $token) {
            $tile = $this->tileNumber($planetService, trim($token, '{}'));
            if ($tile === null || $tile === '18') {
                continue;
            }

            $info = $planetService->getTile($tile);
            if (isset(self::TILE_SETS[$info['set'] ?? ''])) {
                $config[self::TILE_SETS[$info['set']]] = true;
            }

            $faction = self::HOME_TILES[$tile] ?? $info['faction'] ?? null;
            if ($faction) {
                $factions[] = $faction;
                $config['include_te_factions'] = $config['include_te_factions'] || ($info['set'] ?? null) === 'TE';
            }
            if (!$faction || isset(self::HOME_TILES[$tile])) {
                $tiles[] = $tile;
            }
        }

        if (empty($tiles) && empty($factions)) {
            return null;
        }

        return [
            'source' => 'map_string',
            'map_string' => trim($mapString),
            'config' => $config,
            'slices' => [],
            'map_tiles' => $tiles,
            'draft' => ['players' => array_map(
                fn ($faction) => ['name' => null, 'faction' => $faction, 'slice' => null],
                $factions
            )],
        ];
    }

    /**
     * The planets.json number of a tile in a map string, which can carry a rotation, e.g. hyperlane "83A2" is tile "83A".
     */
    private function tileNumber(PlanetService $planetService, string $token): ?string
    {
        foreach ([$token, preg_replace('/^(\d+[A-Za-z])\d+$/', '$1', $token)] as $candidate) {
            foreach ([$candidate, strtoupper($candidate), strtolower($candidate)] as $number) {
                if ($planetService->getTile($number) !== null) {
                    return $number;
                }
            }
        }

        return null;
    }
}
