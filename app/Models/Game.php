<?php

namespace App\Models;

use App\Services\PlanetService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Game extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'speaker_id',
        'milty_url',
        'milty_draft_id',
        'milty_draft_data',
        'fracture_in_play',
        'representative_government',
        'absol_agendas',
        'show_results',
        'hidden_agenda',
        'managed_influence',
        'agenda_phase_started_at',
        'star_chart_tiles',
        'status',
    ];

    // Same as the column default, so a new game has secret votes before it is reloaded
    protected $attributes = [
        'hidden_agenda' => true,
    ];

    protected $casts = [
        'status' => 'string',
        'milty_draft_data' => 'array',
        'fracture_in_play' => 'boolean',
        'representative_government' => 'boolean',
        'absol_agendas' => 'boolean',
        'show_results' => 'boolean',
        'hidden_agenda' => 'boolean',
        'managed_influence' => 'boolean',
        'agenda_phase_started_at' => 'datetime',
        'star_chart_tiles' => 'array',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function speaker(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'speaker_id');
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class);
    }

    public function currentAgenda(): ?Agenda
    {
        return $this->agendas()->where('status', 'voting')->first();
    }

    // Law that, when voted "For", lets each player cast only 1 vote on each agenda
    public const REPRESENTATIVE_GOVERNMENT = 'Representative Government';

    // Page where new players join this game, shared as a link or QR code
    public function joinUrl(): string
    {
        return route('join-group', $this->code);
    }

    /**
     * QR code of the join link as inline SVG, made here so the link is not sent to a QR service.
     */
    public function joinQrSvg(int $size = 240): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd())))->writeString($this->joinUrl());

        return preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
    }

    // The draft URL to show as a link, only when it is a https URL, so a stored javascript: URL is never linked
    public function getSafeMiltyUrlAttribute(): ?string
    {
        return is_string($this->milty_url) && str_starts_with($this->milty_url, 'https://') ? $this->milty_url : null;
    }

    // Page where players who lost their session ask to rejoin, another player has to confirm it
    public function rejoinUrl(): string
    {
        return route('game.rejoin', $this->code);
    }

    // With managed influence, players enter their influence after the Speaker starts the agenda phase
    public function needsInfluenceFrom(Player $player): bool
    {
        return $this->managed_influence && $this->agenda_phase_started_at !== null && $player->influence === null;
    }

    public static function retentionDays(): int
    {
        return (int) config('app.game_retention_days', 7);
    }

    /**
     * Scope to games without any activity in the given number of days: no change to the game,
     * no player joining and no agenda changed or voted on.
     */
    public function scopeInactiveFor($query, int $days)
    {
        $cutoff = now()->subDays($days);

        return $query
            ->where('updated_at', '<', $cutoff)
            ->whereDoesntHave('players', fn ($query) => $query->where('created_at', '>=', $cutoff))
            ->whereDoesntHave('agendas', fn ($query) => $query->where('updated_at', '>=', $cutoff))
            ->whereDoesntHave('agendas.votes', fn ($query) => $query->where('created_at', '>=', $cutoff));
    }

    /**
     * Remove a player and their votes. They are also taken off an open "Elect Player" agenda,
     * unless someone already voted for them. A removed Speaker passes the token to the longest-joined player.
     */
    public function removePlayer(Player $player): void
    {
        if ($player->game_id !== $this->id) {
            throw new \InvalidArgumentException('Player does not belong to this game');
        }

        $player->delete();

        $currentAgenda = $this->currentAgenda();
        if ($currentAgenda?->elect === 'player' && !$currentAgenda->votes()->where('option', $player->name)->exists()) {
            $currentAgenda->update([
                'options' => collect($currentAgenda->options)->reject(fn ($option) => $option === $player->name)->values()->all(),
            ]);
        }

        if ($player->is_speaker && $newSpeaker = $this->players()->oldest()->first()) {
            $this->setSpeaker($newSpeaker);
        }
    }

    public function lastCompletedAgenda(): ?Agenda
    {
        return $this->agendas()->where('status', 'completed')->latest('voting_ended_at')->first();
    }

    public function setSpeaker(Player $player): void
    {
        if ($player->game_id !== $this->id) {
            throw new \InvalidArgumentException('Player does not belong to this game');
        }

        // Start a database transaction to ensure consistency
        \DB::transaction(function () use ($player) {
            // Remove speaker status from all players in this game
            $this->players()->update(['is_speaker' => false]);

            // Set the new speaker
            $player->update(['is_speaker' => true]);

            // Update the game's speaker_id
            $this->update(['speaker_id' => $player->id]);
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function getPlanets($type = null)
    {
        if(!$this->milty_draft_data) {
            return collect([]);
        }

        $planetService = new PlanetService();

        $Slices = $this->miltySelectedSlices();
        $Tiles = $this->miltyTilesFromSlices($Slices)->merge($this->milty_draft_data['map_tiles'] ?? []);
        $Factions = $this->miltyFactionNames();
        $HomePlanets = $planetService->getFactionPlanets($Factions->toArray());
        $HomePlanets = collect($HomePlanets);

        if ($this->isFractureInPlay()) {
            $Tiles = $Tiles->merge(PlanetService::FRACTURE_TILES);
        }

        // Systems drawn with Star Chart are on the map too
        $Tiles = $Tiles->merge($this->starChartTileNumbers());

        $planetInfo = $planetService->getPlanetsByTileNumber($Tiles->toArray());
        $planetInfo = collect($planetInfo);
        $planetInfo = $planetInfo->sortBy('name');

        if(in_array($type, ['industrial', 'cultural', 'hazardous'])){
            // Some Thunder's Edge planets have multiple traits stored as an array
            return $planetInfo->filter(fn ($planet) => in_array($type, (array) $planet['trait']));
        }

        if($type == 'non_home'){
            return $planetInfo->where('tile_id', '!=', 18);
        }

        $planetInfo = $planetInfo->merge($HomePlanets);
        $planetInfo = $planetInfo->sortBy('name');
        return $planetInfo;
    }

    public function isThundersEdgeGame(): bool
    {
        $config = $this->milty_draft_data['config'] ?? [];

        return !empty($config['include_te_tiles']) || !empty($config['include_te_factions']);
    }

    public function isFractureInPlay(): bool
    {
        return $this->isThundersEdgeGame() && $this->fracture_in_play;
    }

    public function miltySelectedSlices()
    {
        if(!$this->milty_draft_data) {
            return collect([]);
        }

        $selectedSlices = collect($this->milty_draft_data['draft']['players'])->pluck('slice');

        $slices = collect([]);
        foreach($selectedSlices as $slice) {
            // Players who did not pick a slice yet, e.g. when the draft is not finished
            if ($slice === null || !isset($this->milty_draft_data['slices'][$slice])) {
                continue;
            }

            $slices->push($this->milty_draft_data['slices'][$slice]);
        }

        return $slices;
    }

    public function miltyTilesFromSlices($slices)
    {
        $tiles = collect([18]);
        foreach($slices as $slice) {
            $tiles = $tiles->merge(collect($slice['tiles']));
        }

        return $tiles;
    }

    public static function isArgentFlight(?string $faction): bool
    {
        return $faction !== null && stripos($faction, 'Argent') !== false;
    }

    /**
     * Whether The Argent Flight is in play, from the Milty draft or a faction a player entered.
     */
    public function hasArgentFlight(): bool
    {
        return $this->miltyFactionNames()->contains(fn ($faction) => self::isArgentFlight($faction))
            || $this->players()->get()->contains(fn ($player) => self::isArgentFlight($player->faction));
    }

    /**
     * Name of the player playing The Argent Flight, from the players in this game or else the Milty draft.
     */
    public function argentFlightPlayerName(): ?string
    {
        $player = $this->players()->get()->first(fn ($player) => self::isArgentFlight($player->faction));
        if ($player) {
            return $player->name;
        }

        $miltyPlayer = collect($this->milty_draft_data['draft']['players'] ?? [])
            ->first(fn ($player) => self::isArgentFlight($player['faction'] ?? null));

        return $miltyPlayer['name'] ?? null;
    }

    /**
     * The factions picked in a Milty draft, as faction => name of the player in the draft, sorted by faction.
     */
    public static function factionsFromDraft(?array $draftData): Collection
    {
        return collect($draftData['draft']['players'] ?? [])
            ->filter(fn ($player) => !empty($player['faction']))
            ->mapWithKeys(fn ($player) => [$player['faction'] => $player['name'] ?? null])
            ->sortKeys();
    }

    /**
     * The drafted factions no other player in this game has chosen yet. Empty without Milty data.
     */
    public function availableFactions(?Player $except = null): Collection
    {
        $taken = $this->players()
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->whereNotNull('faction')
            ->pluck('faction');

        return self::factionsFromDraft($this->milty_draft_data)->except($taken->all());
    }

    // Expansion of each system tile in planets.json and the Milty setting that puts its tiles in the game, null for always
    private const STAR_CHART_SETS = [
        'BaseGame' => null,
        'PoK' => 'include_pok',
        'TE' => 'include_te_tiles',
        'DSPlus' => 'include_ds_tiles',
    ];

    /**
     * Numbers of the system tiles drawn with Star Chart so far.
     */
    public function starChartTileNumbers(): Collection
    {
        return collect($this->star_chart_tiles ?? [])->pluck('tile');
    }

    /**
     * The blue system tiles Star Chart can draw from: tiles of the expansions in the Milty draft
     * that are not on the map yet, i.e. not in a drafted slice or the map string and not drawn before.
     */
    public function starChartPool(): Collection
    {
        $config = $this->milty_draft_data['config'] ?? [];
        $slices = $this->milty_draft_data['slices'] ?? [];

        $onMap = collect($this->milty_draft_data['draft']['players'] ?? [])
            ->pluck('slice')
            ->filter(fn ($slice) => $slice !== null && isset($slices[$slice]))
            ->flatMap(fn ($slice) => $slices[$slice]['tiles'] ?? [])
            ->merge($this->milty_draft_data['map_tiles'] ?? [])
            ->map(fn ($tile) => (string) $tile)
            ->merge($this->starChartTileNumbers());

        return collect(app(PlanetService::class)->getTiles())
            ->filter(function ($tile, $number) use ($config, $onMap) {
                $set = $tile['set'] ?? '';
                $inGame = array_key_exists($set, self::STAR_CHART_SETS)
                    && (self::STAR_CHART_SETS[$set] === null || !empty($config[self::STAR_CHART_SETS[$set]]));

                return ($tile['type'] ?? null) === 'blue'
                    && empty($tile['nonDraftable'])
                    && $inGame
                    && !$onMap->contains((string) $number);
            })
            ->keys()
            ->map(fn ($number) => (string) $number)
            ->values();
    }

    /**
     * Draw a random tile from the Star Chart pool for this player. Returns the tile number, or null when the pool is empty.
     */
    public function drawStarChartTile(Player $player): ?string
    {
        $pool = $this->starChartPool();
        if ($pool->isEmpty()) {
            return null;
        }

        $tile = $pool[random_int(0, $pool->count() - 1)];

        $this->update(['star_chart_tiles' => [
            ...($this->star_chart_tiles ?? []),
            ['tile' => $tile, 'player' => $player->name, 'drawn_at' => now()->toIso8601String()],
        ]]);

        return $tile;
    }

    public function removeStarChartTile(string $tile): void
    {
        $this->update(['star_chart_tiles' => collect($this->star_chart_tiles ?? [])
            ->reject(fn ($draw) => $draw['tile'] === $tile)
            ->values()
            ->all() ?: null]);
    }

    public function miltyFactionNames()
    {
        if(!$this->milty_draft_data) {
            return collect([]);
        }

        $factionNames = collect($this->milty_draft_data['draft']['players'])->pluck('faction');

        return $factionNames;
    }
}
