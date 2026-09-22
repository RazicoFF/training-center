<?php

declare(strict_types=1);

namespace App\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class Auth
{
    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function issueToken(int $userId, string $role): string
    {
        $ttlDays = (int) Env::get('JWT_TTL_DAYS', '30');

        $payload = [
            'user_id' => $userId,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + $ttlDays * 86400,
        ];

        return JWT::encode($payload, (string) Env::get('JWT_SECRET'), 'HS256');
    }

    /**
     * @return array{user_id:int, role:string}|null
     */
    public static function verifyToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key((string) Env::get('JWT_SECRET'), 'HS256'));
            return [
                'user_id' => (int) $decoded->user_id,
                'role' => (string) $decoded->role,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
