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
        'rejoin_requested_at' => 'datetime',
        'rejoin_approved_at' => 'datetime',
    ];

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

    public function clearRejoinRequest(): void
    {
        $this->update(['rejoin_request_token' => null, 'rejoin_requested_at' => null, 'rejoin_approved_at' => null]);
    }

    public function hasVotedOn(Agenda $agenda): bool
    {
        return $this->votes()->where('agenda_id', $agenda->id)->exists();
    }
}
