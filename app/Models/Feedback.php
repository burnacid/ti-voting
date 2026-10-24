<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    public const TYPES = [
        'idea' => 'Idea',
        'bug' => 'Bug',
        'other' => 'Other',
    ];

    protected $table = 'feedback';

    protected $fillable = [
        'type',
        'message',
        'name',
        'game',
        'url',
        'user_agent',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
