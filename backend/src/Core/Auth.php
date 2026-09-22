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

        return JWT::encode($payload, self::secret(), 'HS256');
    }

    /**
     * @return array{user_id:int, role:string}|null
     */
    public static function verifyToken(string $token): ?array
    {
        // Deliberately outside the try/catch below: a misconfigured JWT_SECRET must fail loudly
        // (propagate to the global exception handler) rather than be swallowed as "invalid token".
        $secret = self::secret();

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return [
                'user_id' => (int) $decoded->user_id,
                'role' => (string) $decoded->role,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @throws \RuntimeException when JWT_SECRET is missing or left at the shipped example value
     */
    private static function secret(): string
    {
        $secret = Env::get('JWT_SECRET', '');

        if ($secret === '' || $secret === 'change-me-in-production') {
            throw new \RuntimeException('JWT_SECRET is not configured');
        }

        return $secret;
    }
}
