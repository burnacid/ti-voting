<?php

namespace App\Models;

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
}
