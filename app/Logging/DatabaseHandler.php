<?php

namespace App\Logging;

use Illuminate\Support\Facades\DB;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Throwable;

/**
 * Writes log entries to the log_entries table, shown in the admin log viewer.
 */
class DatabaseHandler extends AbstractProcessingHandler
{
    // Set while writing, so a failing insert that gets logged itself does not loop
    private static bool $writing = false;

    protected function write(LogRecord $record): void
    {
        if (self::$writing) {
            return;
        }

        self::$writing = true;

        try {
            $normalized = (new NormalizerFormatter('Y-m-d H:i:s'))->format($record);
            $context = array_merge($normalized['extra'] ?? [], $normalized['context'] ?? []);
            $exception = $context['exception'] ?? null;
            unset($context['exception']);

            $game = is_string($context['game'] ?? null) ? $context['game'] : null;
            unset($context['game']);

            DB::table('log_entries')->insert([
                'level' => strtolower($record->level->getName()),
                'message' => $record->message,
                'game' => $game,
                'context' => $context ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : null,
                'exception' => is_array($exception) ? json_encode([
                    'class' => $exception['class'] ?? null,
                    'message' => $exception['message'] ?? null,
                    'location' => $exception['file'] ?? null,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : null,
                'trace' => ($record->context['exception'] ?? null) instanceof Throwable
                    ? $record->context['exception']->getTraceAsString()
                    : null,
                'created_at' => $record->datetime->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            // The database is unavailable or not migrated yet, the other log channels still have the entry
        } finally {
            self::$writing = false;
        }
    }
}
