<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agenda extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'game_id',
        'title',
        'description',
        'options',
        'status',
        'voting_started_at',
        'voting_ended_at',
    ];

    protected $casts = [
        'options' => 'array',
        'voting_started_at' => 'datetime',
        'voting_ended_at' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function getVoteResults(): array
    {
        $results = [];
        $totalInfluence = $this->votes()->sum('influence_spent');

        foreach ($this->options as $option) {
            $influenceTotal = $this->votes()->where('option', $option)->sum('influence_spent');

            $results[$option] = [
                'influence' => $influenceTotal,
                'percentage' => $totalInfluence > 0 ? round(($influenceTotal / $totalInfluence) * 100, 1) : 0,
            ];
        }

        return $results;
    }

    public function allPlayersVoted(): bool
    {
        return $this->votes()->count() >= $this->game->players()->count();
    }
}
