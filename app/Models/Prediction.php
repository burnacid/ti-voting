<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An outcome a player predicted aloud after the agenda was revealed, e.g. with a Rider.
 * A player who predicts cannot vote on the agenda.
 */
class Prediction extends Model
{
    use HasFactory, HasUuids;

    // Cards and abilities that predict an outcome, with what the player gets when the prediction is correct.
    // shows_voters: the reward depends on who voted for the predicted outcome, so the player gets to see them
    public const SOURCES = [
        'construction_rider' => ['label' => 'Construction Rider', 'reward' => 'Place 1 space dock from your reinforcements on a planet you control.'],
        'diplomacy_rider' => ['label' => 'Diplomacy Rider', 'reward' => 'Choose 1 system that contains a planet you control; each other player places a command token from their reinforcements in that system.'],
        'imperial_rider' => ['label' => 'Imperial Rider', 'reward' => 'Gain 1 victory point.'],
        'leadership_rider' => ['label' => 'Leadership Rider', 'reward' => 'Gain 3 command tokens.'],
        'politics_rider' => ['label' => 'Politics Rider', 'reward' => 'Draw 3 action cards and gain the speaker token.'],
        'technology_rider' => ['label' => 'Technology Rider', 'reward' => 'Research 1 technology.'],
        'trade_rider' => ['label' => 'Trade Rider', 'reward' => 'Gain 5 trade goods.'],
        'warfare_rider' => ['label' => 'Warfare Rider', 'reward' => 'Place 1 dreadnought from your reinforcements in a system that contains 1 or more of your ships.'],
        'nekro_galactic_threat' => ['label' => 'The Nekro Virus: Galactic Threat', 'reward' => 'Gain 1 technology that is owned by a player who voted how you predicted.', 'shows_voters' => true],
        'other' => ['label' => 'Other card or ability', 'reward' => null],
    ];

    protected $fillable = [
        'agenda_id',
        'player_id',
        'source',
        'option',
    ];

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source]['label'] ?? $this->source;
    }

    public function getRewardAttribute(): ?string
    {
        return self::SOURCES[$this->source]['reward'] ?? null;
    }

    public function showsVoters(): bool
    {
        return self::SOURCES[$this->source]['shows_voters'] ?? false;
    }

    // Correct once the agenda has an outcome, i.e. voting ended and a tie was broken
    public function isCorrect(): bool
    {
        return $this->agenda->status === 'completed' && $this->option === $this->agenda->outcome();
    }
}
