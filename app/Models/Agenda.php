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

    // Elect line as printed on the agenda cards, keyed by the "Elect" value in agendas.json
    public const ELECT_LABELS = [
        'player' => 'Elect Player',
        'planet' => 'Elect Planet',
        'industrial_planet' => 'Elect Industrial Planet',
        'cultural_planet' => 'Elect Cultural Planet',
        'hazardous_planet' => 'Elect Hazardous Planet',
        'non_home_non_mecathol' => 'Elect Non-Home Planet Other Than Mecatol Rex',
        'law' => 'Elect Law',
        'strategy card' => 'Elect Strategy Card',
        'secret_objective' => 'Elect Scored Secret Objective',
    ];

    // Elect value for agendas created without a preset, keyed by the agenda type picked in the form
    public const ELECT_BY_AGENDA_TYPE = [
        'elect_player' => 'player',
        'elect_planet_any' => 'planet',
        'elect_planet_industrial' => 'industrial_planet',
        'elect_planet_cultural' => 'cultural_planet',
        'elect_planet_hazardous' => 'hazardous_planet',
        'elect_planet_non_home' => 'non_home_non_mecathol',
    ];

    protected $fillable = [
        'game_id',
        'title',
        'description',
        'type',
        'elect',
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

    public function getElectLabelAttribute(): ?string
    {
        return self::ELECT_LABELS[$this->elect] ?? null;
    }

    public function getFormattedDescriptionAttribute(): string
    {
        $paragraphs = preg_split('/\R+/', trim($this->description));

        return collect($paragraphs)->map(function ($paragraph) {
            $paragraph = e(trim($paragraph));

            // Style "FOR :" and "AGAINST :" like the printed cards ("For:" / "Against:")
            $paragraph = preg_replace_callback('/^(FOR|AGAINST)\s*:/', function ($matches) {
                return '<strong class="agenda-card-outcome">' . ucfirst(strtolower($matches[1])) . ':</strong>';
            }, $paragraph);

            // Game terms written in capitals (e.g. SUSTAIN DAMAGE) are printed in small caps
            $paragraph = preg_replace_callback('/\b[A-Z]{2,}(?:\s+[A-Z]{2,})*\b/', function ($matches) {
                return '<span class="agenda-card-keyword">' . strtolower($matches[0]) . '</span>';
            }, $paragraph);

            return "<p>{$paragraph}</p>";
        })->implode('');
    }
}
