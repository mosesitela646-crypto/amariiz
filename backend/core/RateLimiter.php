<?php
declare(strict_types=1);

namespace Core;

final class RateLimiter
{
    public static function assertAllowed(string $key, int $max, int $window): void
    {
        $directory = dirname(__DIR__) . '/storage/rate_limits'; if (!is_dir($directory)) mkdir($directory, 0775, true);
        $file = $directory . '/' . hash('sha256', $key) . '.json'; $now = time();
        $history = is_file($file) ? json_decode(file_get_contents($file) ?: '[]', true) : [];
        $history = array_values(array_filter(is_array($history) ? $history : [], fn($time) => $time > $now - $window));
        if (count($history) >= $max) Response::error('Trop de commandes depuis cette adresse IP. Réessayez plus tard.', 429);
        $history[] = $now; file_put_contents($file, json_encode($history), LOCK_EX);
    }
}
