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
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'milty_draft_data' => 'array'
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

    public function lastCompletedAgenda(): ?Agenda
    {
        return $this->agendas()->where('status', 'completed')->first();
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

        $planetInfo = $planetService->getPlanetsByTileNumber($Tiles->toArray());
        $planetInfo = collect($planetInfo);
        $planetInfo = $planetInfo->sortBy('name');

        if($type == 'industrial'){
            return $planetInfo->where('trait', '==', 'industrial');
        }

        if($type == 'cultural'){
            return $planetInfo->where('trait', '==', 'cultural');
        }

        if($type == 'hazardous'){
            return $planetInfo->where('trait', '==', 'hazardous');
        }

        if($type == 'non_home'){
            return $planetInfo->where('tile_id', '!=', 18);
        }

        $planetInfo = $planetInfo->merge($HomePlanets);
        $planetInfo = $planetInfo->sortBy('name');
        return $planetInfo;
    }

    public function miltySelectedSlices()
    {
        if(!$this->milty_draft_data) {
            return collect([]);
        }

        $selectedSlices = collect($this->milty_draft_data['draft']['draft']['players'])->pluck('slice');

        $slices = collect([]);
        foreach($selectedSlices as $slice) {
            $slices->push($this->milty_draft_data['draft']['slices'][$slice]);
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

    public function miltyFactionNames()
    {
        if(!$this->milty_draft_data) {
            return collect([]);
        }

        $factionNames = collect($this->milty_draft_data['draft']['draft']['players'])->pluck('faction');

        return $factionNames;
    }
}
