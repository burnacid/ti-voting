<?php

namespace App\Models;

use App\Services\PlanetService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'milty_draft_data' => 'array',
        'fracture_in_play' => 'boolean',
        'representative_government' => 'boolean',
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

    // Page where players who lost their session ask to rejoin, another player has to confirm it
    public function rejoinUrl(): string
    {
        return route('game.rejoin', $this->code);
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
        $Tiles = $this->miltyTilesFromSlices($Slices);
        $Factions = $this->miltyFactionNames();
        $HomePlanets = $planetService->getFactionPlanets($Factions->toArray());
        $HomePlanets = collect($HomePlanets);

        if ($this->isFractureInPlay()) {
            $Tiles = $Tiles->merge(PlanetService::FRACTURE_TILES);
        }

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

    public function miltyFactionNames()
    {
        if(!$this->milty_draft_data) {
            return collect([]);
        }

        $factionNames = collect($this->milty_draft_data['draft']['players'])->pluck('faction');

        return $factionNames;
    }
}
