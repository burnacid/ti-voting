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
    ];

    protected $casts = [
        'is_speaker' => 'boolean',
    ];

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

    public function hasVotedOn(Agenda $agenda): bool
    {
        return $this->votes()->where('agenda_id', $agenda->id)->exists();
    }
}
