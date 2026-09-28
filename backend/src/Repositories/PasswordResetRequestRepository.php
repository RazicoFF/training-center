<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class PasswordResetRequestRepository
{
    public function create(string $phone, ?int $userId): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO password_reset_requests (phone, user_id) VALUES (?, ?)'
        );
        $stmt->execute([$phone, $userId]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM password_reset_requests WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Newest pending requests first, then resolved ones - matches the "new items float to
     * the top" pattern already used for applications elsewhere in the admin panel.
     */
    public function allWithUserInfo(): array
    {
        $sql = "SELECT r.id, r.phone, r.user_id, r.status, r.created_at, r.resolved_at,
                       u.full_name, u.role
                FROM password_reset_requests r
                LEFT JOIN users u ON u.id = r.user_id
                ORDER BY (r.status = 'pending') DESC, r.created_at DESC";

        return Database::pdo()->query($sql)->fetchAll();
    }

    public function resolve(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE password_reset_requests SET status = 'resolved', resolved_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
