<?php
declare(strict_types=1);

namespace Core;

use PDO;

final class Auth
{
    public function __construct(private PDO $pdo) {}
    public function user(): array
    {
        $header = Request::header('Authorization');
        if (!$header || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) Response::error('Token Bearer manquant.', 401);
        $stmt = $this->pdo->prepare('SELECT u.id, u.name, u.phone, u.email, u.role FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE t.token = :token AND t.expires_at > NOW() LIMIT 1');
        $stmt->execute(['token' => $matches[1]]);
        $user = $stmt->fetch();
        if (!$user) Response::error('Token invalide ou expiré.', 401);
        return $user;
    }
    public function requireRoles(array $roles): array
    {
        $user = $this->user();
        if (!in_array($user['role'], $roles, true)) Response::error('Vous ne disposez pas des droits nécessaires.', 403);
        return $user;
    }
    public function issueToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $days = max(1, (int) Env::get('TOKEN_TTL_DAYS', '7'));
        $stmt = $this->pdo->prepare('INSERT INTO api_tokens (user_id, token, expires_at) VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL :days DAY))');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT); $stmt->bindValue(':token', $token); $stmt->bindValue(':days', $days, PDO::PARAM_INT); $stmt->execute();
        return $token;
    }
    public function revokeCurrentToken(): void
    {
        $header = Request::header('Authorization');
        if (!$header || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) Response::error('Token Bearer manquant.', 401);
        $stmt = $this->pdo->prepare('DELETE FROM api_tokens WHERE token = :token'); $stmt->execute(['token' => $matches[1]]);
    }
}
