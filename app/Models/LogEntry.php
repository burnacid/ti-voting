<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class LogEntry extends Model
{
    use MassPrunable;

    public const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
    public const ERROR_LEVELS = ['emergency', 'alert', 'critical', 'error'];
    public const WARNING_LEVELS = ['warning', 'notice'];

    const UPDATED_AT = null;

    protected $fillable = [
        'level',
        'message',
        'game',
        'context',
        'exception',
        'trace',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'exception' => 'array',
    ];

    public static function retentionDays(): int
    {
        return (int) config('logging.channels.database.days', 30);
    }

    // Deleted by the scheduled model:prune command
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(static::retentionDays()));
    }

    public function isError(): bool
    {
        return in_array($this->level, self::ERROR_LEVELS);
    }

    public function isWarning(): bool
    {
        return in_array($this->level, self::WARNING_LEVELS);
    }
}
