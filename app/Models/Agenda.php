<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Agenda extends Model
{
    use HasFactory, HasUuids;

    // Before voting opens, the Speaker checks for these action card timings, in this order
    public const STEP_WHEN_REVEALED = 'when_revealed';
    public const STEP_AFTER_REVEALED = 'after_revealed';
    // Only when The Argent Flight is in the game: they vote first and announce their votes publicly
    public const STEP_ARGENT_VOTES = 'argent_votes';

    public const REVEAL_STEP_LABELS = [
        self::STEP_WHEN_REVEALED => 'When an agenda is revealed',
        self::STEP_AFTER_REVEALED => 'After an agenda is revealed',
        self::STEP_ARGENT_VOTES => 'The Argent Flight votes first',
    ];

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
        'unit_upgrade' => 'Elect Owned Non-Faction Unit Upgrade Technology',
    ];

    // Decks a preset agenda can come from, shown as a tag on the card
    public const SOURCE_POK = 'pok';
    public const SOURCE_ABSOL = 'absol';

    public const SOURCE_LABELS = [
        self::SOURCE_POK => 'Prophecy of Kings',
        self::SOURCE_ABSOL => 'Absol’s Lab',
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
        'source',
        'secret_votes',
        'options',
        'status',
        'reveal_step',
        'blocked_player_ids',
        'tiebreak_option',
        'voting_started_at',
        'voting_ended_at',
    ];

    protected $casts = [
        'options' => 'array',
        'blocked_player_ids' => 'array',
        'secret_votes' => 'boolean',
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

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
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

    /**
     * The options with the most votes, Abstain left out. More than one means a tie the Speaker breaks,
     * which includes no votes being cast at all.
     */
    public function leadingOptions(): array
    {
        $results = collect($this->getVoteResults())->except('Abstain');
        $most = $results->max('influence');

        return $results->filter(fn ($result) => $result['influence'] === $most)->keys()->all();
    }

    public function isTied(): bool
    {
        return count($this->leadingOptions()) > 1;
    }

    /**
     * The winning option, or null while a tie is not broken by the Speaker yet.
     */
    public function outcome(): ?string
    {
        $leading = $this->leadingOptions();

        if (count($leading) === 1) {
            return $leading[0];
        }

        return in_array($this->tiebreak_option, $leading, true) ? $this->tiebreak_option : null;
    }

    // Agendas whose outcome depends on how players voted, e.g. 'Each player who voted "For" gains 1 victory point'
    public function affectsVoters(): bool
    {
        return preg_match('/\bvoted\s+["“]?(for|against)\b/i', $this->description ?? '') === 1;
    }

    /**
     * The effect text of an outcome, i.e. the "FOR :" or "AGAINST :" paragraph of the card.
     */
    public function outcomeEffect(string $option): ?string
    {
        foreach (preg_split('/\R+/', trim($this->description ?? '')) as $paragraph) {
            if (preg_match('/^' . preg_quote($option, '/') . '\s*:\s*(.+)$/i', trim($paragraph), $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * The players an outcome applies to, grouped by the vote it names, e.g. ['For' => [players who voted For]].
     * Empty when the outcome does not depend on how players voted.
     */
    public function playersAffectedBy(string $option): array
    {
        preg_match_all('/\bvoted\s+["“]?(for|against)\b/i', $this->outcomeEffect($option) ?? '', $matches);

        return collect($matches[1])
            ->map(fn ($vote) => ucfirst(strtolower($vote)))
            ->unique()
            ->mapWithKeys(fn ($vote) => [
                $vote => $this->votes()->with('player')->where('option', $vote)->get()->pluck('player.name')->filter()->sort()->values()->all(),
            ])
            ->all();
    }

    /**
     * The players who voted for an option, e.g. for The Nekro Virus to take a technology from after a correct prediction.
     */
    public function playersWhoVoted(string $option): Collection
    {
        return $this->votes()->with('player')->where('option', $option)->get()->pluck('player')->filter()->sortBy('name')->values();
    }

    public function allPlayersVoted(): bool
    {
        return $this->votes()->count() >= $this->eligiblePlayerCount();
    }

    // Blocked by the Speaker, e.g. by Assassinate Representative, or by predicting an outcome
    public function isPlayerBlocked(Player $player): bool
    {
        return in_array($player->id, $this->blocked_player_ids ?? [], true) || $this->hasPredicted($player);
    }

    // A player who predicts an outcome, e.g. with a Rider, cannot vote on this agenda
    public function hasPredicted(Player $player): bool
    {
        return $this->predictions()->where('player_id', $player->id)->exists();
    }

    // Players who may vote on this agenda, i.e. everyone the Speaker did not block and who did not predict
    public function eligiblePlayerCount(): int
    {
        $ineligibleIds = [...($this->blocked_player_ids ?? []), ...$this->predictions()->pluck('player_id')];

        return $this->game->players()->whereNotIn('id', $ineligibleIds)->count();
    }

    public function isVotingOpen(): bool
    {
        return $this->status === 'voting' && $this->reveal_step === null;
    }

    /**
     * The steps the Speaker goes through before voting opens, in order.
     */
    public function revealSteps(): array
    {
        return array_values(array_filter([
            self::STEP_WHEN_REVEALED,
            self::STEP_AFTER_REVEALED,
            $this->game->hasArgentFlight() ? self::STEP_ARGENT_VOTES : null,
        ]));
    }

    /**
     * The step after the current one, or null when voting opens next.
     */
    public function nextRevealStep(): ?string
    {
        $steps = $this->revealSteps();
        $index = array_search($this->reveal_step, $steps, true);

        return $index === false ? null : ($steps[$index + 1] ?? null);
    }

    public function getRevealStepLabelAttribute(): ?string
    {
        return self::REVEAL_STEP_LABELS[$this->reveal_step] ?? null;
    }

    /**
     * The deck of an agenda in agendas.json, null for the base game.
     */
    public static function sourceOf(array $preset): ?string
    {
        return match (true) {
            (bool) ($preset['Absol'] ?? false) => self::SOURCE_ABSOL,
            (bool) ($preset['Pok'] ?? false) => self::SOURCE_POK,
            default => null,
        };
    }

    public function isAbsol(): bool
    {
        return $this->source === self::SOURCE_ABSOL;
    }

    public function getSourceLabelAttribute(): ?string
    {
        return self::SOURCE_LABELS[$this->source] ?? null;
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
