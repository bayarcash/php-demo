<?php

declare(strict_types=1);

namespace BayarcashDemo;

/**
 * Callbacks are server-to-server, so nothing appears in your browser when one
 * lands. Tail this while testing:  tail -f storage/logs/demo.log
 */
final class Log
{
    public static function write(string $message, array $context = []): void
    {
        $dir = dirname(__DIR__) . '/storage/logs';

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $line = sprintf(
            "[%s] %s%s\n",
            date('Y-m-d H:i:s'),
            $message,
            $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES)
        );

        file_put_contents($dir . '/demo.log', $line, FILE_APPEND | LOCK_EX);
    }

    /** Empties the log file. */
    public static function clear(): void
    {
        $file = dirname(__DIR__) . '/storage/logs/demo.log';

        if (is_file($file)) {
            file_put_contents($file, '');
        }
    }

    /** @return array<int, string> newest first */
    public static function tail(int $lines = 40): array
    {
        $file = dirname(__DIR__) . '/storage/logs/demo.log';

        if (! is_file($file)) {
            return [];
        }

        $all = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return array_reverse(array_slice($all, -$lines));
    }
}
