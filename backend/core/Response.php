<?php
declare(strict_types=1);

namespace Core;

final class Response
{
    public static function json(bool $success, mixed $data = null, string $message = '', mixed $errors = null, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'data' => $data, 'message' => $message, 'errors' => $errors], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Opération réussie.', int $status = 200): never { self::json(true, $data, $message, null, $status); }
    public static function error(string $message, int $status, array $errors = []): never { self::json(false, null, $message, $errors ?: null, $status); }
}
