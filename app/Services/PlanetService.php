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
                        'faction' => $tile['faction'],
                        'type' => $tile['type'],
                        'wormhole' => $tile['wormhole'],
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
}
