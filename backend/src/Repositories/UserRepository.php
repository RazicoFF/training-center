<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class UserRepository
{
    public function create(string $fullName, string $phone, string $passwordHash, string $role = 'student'): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (full_name, phone, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$fullName, $phone, $passwordHash, $role]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE phone = ?');
        $stmt->execute([$phone]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function allByRole(string $role): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, full_name, phone, created_at FROM users WHERE role = ? ORDER BY full_name'
        );
        $stmt->execute([$role]);

        return $stmt->fetchAll();
    }
}
