<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Player extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'game_id',
        'name',
        'session_token',
        'faction',
        'influence',
        'is_speaker',
        'rejoin_request_token',
        'rejoin_requested_at',
        'rejoin_approved_at',
    ];

    protected $hidden = [
        'session_token',
        'rejoin_request_token',
    ];

    protected $casts = [
        'is_speaker' => 'boolean',
        'influence' => 'integer',
        'rejoin_requested_at' => 'datetime',
        'rejoin_approved_at' => 'datetime',
    ];

    // Up to two letters for the avatar in the player list, e.g. "Jan de Vries" becomes "JD"
    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    // Longer names do not fit the player list on a phone
    public const MAX_NAME_LENGTH = 40;

    // How long a request to rejoin as this player waits for another player to confirm it
    public const REJOIN_REQUEST_MINUTES = 10;

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public static function generateSessionToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('session_token', $token)->exists());

        return $token;
    }

    public function hasPendingRejoinRequest(): bool
    {
        return $this->rejoin_request_token !== null
            && $this->rejoin_approved_at === null
            && $this->rejoin_requested_at?->gt(now()->subMinutes(self::REJOIN_REQUEST_MINUTES));
    }

    /**
     * Whether this player may confirm the rejoin request of another player. Only players who were in the game
     * before the request was made, so someone cannot join as a new player and approve their own request.
     */
    public function canApproveRejoinOf(Player $requested): bool
    {
        return $requested->id !== $this->id
            && $requested->game_id === $this->game_id
            && $requested->hasPendingRejoinRequest()
            && $this->created_at !== null
            && $this->created_at->lt($requested->rejoin_requested_at);
    }

    public function clearRejoinRequest(): void
    {
        $this->update(['rejoin_request_token' => null, 'rejoin_requested_at' => null, 'rejoin_approved_at' => null]);
    }

    public function hasVotedOn(Agenda $agenda): bool
    {
        return $this->votes()->where('agenda_id', $agenda->id)->exists();
    }
}
