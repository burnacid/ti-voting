<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads drafts from TI4 Lab (tidraft.com). It has no public API, so the draft is read from the data
 * embedded in the server-rendered draft page, and converted to the shape of a Milty draft.
 */
class TiDraftService
{
    // Game sets of TI4 Lab and the Milty setting with the same tiles
    private const TILE_SETS = [
        'pok' => 'include_pok',
        'te' => 'include_te_tiles',
        'unchartedstars' => 'include_ds_tiles',
    ];

    /**
     * Extract the draft name from a URL like https://tidraft.com/draft/training-people-operations
     */
    public function extractDraftId(string $url): ?string
    {
        if (preg_match('~^https?://(www\.)?tidraft\.com/draft/([a-zA-Z0-9_-]+)~', $url, $matches)) {
            return $matches[2];
        }

        return null;
    }

    public function fetchDraftData(string $draftId): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => !App::environment('local')])
                ->get("https://tidraft.com/draft/{$draftId}");

            if (!$response->successful()) {
                Log::error('Failed to fetch TI4 Lab draft', ['draft_id' => $draftId, 'status' => $response->status()]);
                return null;
            }

            $draft = $this->parsePage($response->body());
            if ($draft === null) {
                Log::error('Could not read the TI4 Lab draft page', ['draft_id' => $draftId]);
                return null;
            }

            return $this->toMiltyData($draft);
        } catch (\Exception $e) {
            Log::error('Exception while fetching TI4 Lab draft', ['draft_id' => $draftId, 'exception' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * The draft object embedded in a draft page: settings, players, selections, slices and presetMap.
     */
    public function parsePage(string $html): ?array
    {
        if (!preg_match('/urlName:"[^"]*",data:/', $html, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $start = strpos($html, '{', $match[0][1] + strlen($match[0][0]));
        $json = $start === false ? null : $this->objectToJson($html, $start);
        $draft = $json === null ? null : json_decode($json, true);

        return is_array($draft) && isset($draft['slices'], $draft['players']) ? $draft : null;
    }

    /**
     * Convert the JavaScript object literal at $start, as written by the page's serializer, to JSON.
     * Handles `$R[n]=` labels, `!0`/`!1` booleans, `void 0` and unquoted keys; references to earlier objects become null.
     */
    private function objectToJson(string $source, int $start): ?string
    {
        $json = '';
        $depth = 0;
        $length = strlen($source);

        for ($i = $start; $i < $length; $i++) {
            $char = $source[$i];

            if ($char === '"') {
                // Copy the string, its escapes are valid JSON except \x
                $end = $i + 1;
                while ($end < $length && $source[$end] !== '"') {
                    $end += $source[$end] === '\\' ? 2 : 1;
                }
                $json .= preg_replace('/\\\\x([0-9a-fA-F]{2})/', '\\\\u00$1', substr($source, $i, $end - $i + 1));
                $i = $end;
            } elseif (preg_match('/\G\$R\[\d+\](=?)/', $source, $ref, 0, $i)) {
                $json .= $ref[1] === '=' ? '' : 'null';
                $i += strlen($ref[0]) - 1;
            } elseif (preg_match('/\G!([01])/', $source, $bool, 0, $i)) {
                $json .= $bool[1] === '0' ? 'true' : 'false';
                $i += 1;
            } elseif (preg_match('/\Gvoid 0/', $source, $void, 0, $i)) {
                $json .= 'null';
                $i += 5;
            } elseif (preg_match('/\G([A-Za-z_$][A-Za-z0-9_$]*):/', $source, $key, 0, $i)) {
                $json .= '"' . $key[1] . '":';
                $i += strlen($key[0]) - 1;
            } else {
                $json .= $char;

                if ($char === '{' || $char === '[') {
                    $depth++;
                } elseif ($char === '}' || $char === ']') {
                    if (--$depth === 0) {
                        return $json;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Convert a TI4 Lab draft to the shape of a Milty draft, which the rest of the app reads.
     */
    public function toMiltyData(array $draft): array
    {
        $tileSets = $draft['settings']['tileGameSets'] ?? [];
        $factionSets = $draft['settings']['factionGameSets'] ?? [];

        $config = ['include_te_factions' => in_array('te', $factionSets)];
        foreach (self::TILE_SETS as $set => $setting) {
            $config[$setting] = in_array($set, $tileSets);
        }

        $slices = collect($draft['slices'])->map(fn ($slice) => [
            'name' => $slice['name'] ?? null,
            'tiles' => collect($slice['tiles'] ?? [])->pluck('systemId')->filter()->values()->all(),
        ])->all();

        $players = [];
        foreach ($draft['players'] as $player) {
            $players[$player['id']] = ['name' => $player['name'] ?? null, 'faction' => null, 'slice' => null];
        }
        foreach ($draft['selections'] ?? [] as $selection) {
            $id = $selection['playerId'] ?? null;
            if (!isset($players[$id])) {
                continue;
            }

            if (($selection['type'] ?? null) === 'SELECT_FACTION') {
                $players[$id]['faction'] = $this->factionName($selection['factionId']);
            } elseif (($selection['type'] ?? null) === 'SELECT_SLICE') {
                $players[$id]['slice'] = $selection['sliceIdx'];
            }
        }

        // Systems placed on the map before the draft, apart from Mecatol Rex
        $mapTiles = collect($draft['presetMap'] ?? [])
            ->where('type', 'SYSTEM')
            ->pluck('systemId')
            ->reject(fn ($tile) => (string) $tile === '18')
            ->values()
            ->all();

        return [
            'source' => 'tidraft',
            'config' => $config,
            'slices' => $slices,
            'map_tiles' => $mapTiles,
            'draft' => ['players' => array_values($players)],
        ];
    }

    // TI4 Lab faction ids and the faction names in planets.json, which Milty drafts use too
    private const FACTION_NAMES = [
        'sardakk' => 'Sardakk N\'orr',
        'arborec' => 'The Arborec',
        'barony' => 'The Barony of Letnev',
        'saar' => 'The Clan of Saar',
        'muaat' => 'The Embers of Muaat',
        'hacan' => 'The Emirates of Hacan',
        'sol' => 'The Federation of Sol',
        'creuss' => 'The Ghosts of Creuss',
        'l1z1x' => 'The Lizix Mindnet',
        'mentak' => 'The Mentak Coalition',
        'naalu' => 'The Naalu Collective',
        'nekro' => 'The Nekro Virus',
        'jolnar' => 'The Universities of Jol-Nar',
        'winnu' => 'The Winnu',
        'xxcha' => 'The Xxcha Kingdom',
        'yin' => 'The Yin Brotherhood',
        'yssaril' => 'The Yssaril Tribes',
        'argent' => 'The Argent Flight',
        'empyrean' => 'The Empyrean',
        'mahact' => 'The Mahact Gene-sorcerers',
        'naazrokha' => 'The Naaz-Rokha Alliance',
        'nomad' => 'The Nomad',
        'titans' => 'The Titans of Ul',
        'vulraith' => 'The Vuil\'raith Cabal',
        'keleres' => 'Council Keleres',
        'bastion' => 'Last Bastion',
        'dws' => 'The Deepwrought Scholarate',
        'crimson' => 'The Crimson Rebellion',
        'ralnel' => 'The Ral Nel Consortium',
        'firmament' => 'The Firmament',
        'ilyxum' => 'Augurs of Ilyxum',
        'celdauri' => 'Celdauri Trade Confederation',
        'dihmohn' => 'Dih-Mohn Flotilla',
        'florzen' => 'Florzen Profiteers',
        'freesystems' => 'Free Systems Compact',
        'ghemina' => 'Ghemina Raiders',
        'mortheus' => 'Glimmer of Mortheus',
        'kollecc' => 'Kollecc Society',
        'kortali' => 'Kortali Tribunal',
        'lizho' => 'Li-Zho Dynasty',
        'khrask' => 'L\'Tokk Khrask',
        'mirveda' => 'Mirveda Protectorate',
        'myko' => 'Myko-Mentori',
        'nivyn' => 'Nivyn Star Kings',
        'olradin' => 'Olradin League',
        'rohdina' => 'Roh\'Dhna Mechatronics',
        'cymiae' => 'Savages of Cymiae',
        'axis' => 'Shipwrights of Axis',
        'tnelis' => 'Tnelis Syndicate',
        'vaden' => 'Vaden Banking Clans',
        'vaylerian' => 'Vaylerian Scourge',
        'veldyr' => 'Veldyr Sovereignty',
        'rhodun' => 'Zealots of Rhodun',
        'zelian' => 'Zelian Purifier',
        'bentor' => 'Bentor Conglomerate',
        'kjalengard' => 'Berserkers of Kjalengard',
        'cheiran' => 'Cheiran Hordes',
        'edyn' => 'Edyn Mandate',
        'ghoti' => 'Ghoti Wayfarers',
        'gledge' => 'Gledge Union',
        'kyro' => 'Kyro Sodality',
        'lanefir' => 'Lanefir Remnants',
        'kolume' => 'The Monks of Kolume',
        'nokar' => 'Nokar Sellships',
        'drahn' => 'Drahn Consortium',
    ];

    private function factionName(string $factionId): string
    {
        return self::FACTION_NAMES[$factionId] ?? $factionId;
    }
}
