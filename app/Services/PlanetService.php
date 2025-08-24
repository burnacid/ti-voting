<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class PlanetService
{
    protected array $planets = [];
    protected array $flattenedPlanets = [];

    public function __construct()
    {
        $this->loadPlanets();
    }

    protected function loadPlanets(): void
    {
        $path = resource_path('data/planets.json');

        if (File::exists($path)) {
            $this->planets = json_decode(File::get($path), true);
            $this->flattenPlanets();
        }
    }

    protected function flattenPlanets(): void
    {
        foreach ($this->planets as $tileId => $tile) {
            foreach ($tile['planets'] as $planet) {
                $this->flattenedPlanets[$planet['name']] = array_merge(
                    $planet,
                    [
                        'tile_id' => $tileId,
                        'type' => $tile['type'],
                        'faction' => isset($tile['faction']) ? $tile['faction'] : null,
                    ]
                );
            }
        }
    }

    public function getAllPlanets(): array
    {
        return $this->flattenedPlanets;
    }

    public function getPlanet(string $name): ?array
    {
        return $this->flattenedPlanets[$name] ?? null;
    }

    public function getPlanetsByFaction(?string $faction = null): array
    {
        if (!$faction) {
            return $this->flattenedPlanets;
        }

        return array_filter($this->flattenedPlanets, function ($planet) use ($faction) {
            return $planet['faction'] === $faction;
        });
    }

    public function getPlanetOptions(): array
    {
        $options = [];

        foreach ($this->flattenedPlanets as $name => $planet) {
            $resources = $planet['resources'];
            $influence = $planet['influence'];
            $options[$name] = "{$name} (R:{$resources}/I:{$influence})";
        }

        asort($options);

        return $options;
    }

    public function getPlanetsByTileNumber(int|array $tileNumbers): array
    {
        // Convert single tile number to array for consistent handling
        if (!is_array($tileNumbers)) {
            $tileNumbers = [$tileNumbers];
        }

        return array_filter($this->flattenedPlanets, function ($planet) use ($tileNumbers) {
            return in_array($planet['tile_id'], $tileNumbers);
        });
    }

    public function getPlanetOptionsByTileNumber(int|array $tileNumbers): array
    {
        $planets = $this->getPlanetsByTileNumber($tileNumbers);
        $options = [];

        foreach ($planets as $name => $planet) {
            $resources = $planet['resources'];
            $influence = $planet['influence'];
            $options[$name] = "{$name} (R:{$resources}/I:{$influence})";
        }

        asort($options);

        return $options;
    }

    public function getFactionPlanets(string|array $factions): array
    {
        if (!is_array($factions)) {
            $factions = [$factions];
        }

        // Simple exact matching
        $result = array_filter($this->flattenedPlanets, function ($planet) use ($factions) {
            // Skip planets without a faction
            if (empty($planet['faction'])) {
                return false;
            }

            // Check if the planet's faction is in our list of factions
            $planetFaction = $planet['faction'];

            // Try exact match first
            if (in_array($planetFaction, $factions)) {
                return true;
            }

            // Try normalized comparison (remove "The " prefix and convert to lowercase)
            $normalizedPlanetFaction = strtolower(preg_replace('/^The\s+/i', '', $planetFaction));

            foreach ($factions as $faction) {
                $normalizedFaction = strtolower(preg_replace('/^The\s+/i', '', $faction));

                // If normalized versions match, it's the same faction
                if ($normalizedPlanetFaction === $normalizedFaction) {
                    return true;
                }
            }

            return false;
        });

        return $result;
    }
}
