<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class TeacherRepository
{
    public function all(): array
    {
        $stmt = Database::pdo()->query("SELECT id, full_name, phone, created_at FROM users WHERE role = 'teacher' ORDER BY full_name");
        return $stmt->fetchAll();
    }

    public function search(?string $q = null, ?int $professionId = null): array
    {
        $sql = "SELECT u.id, u.full_name, u.phone, u.created_at, tp.photo_url
                FROM users u
                LEFT JOIN teacher_profiles tp ON tp.user_id = u.id
                WHERE u.role = 'teacher'";
        $params = [];

        if ($q !== null && $q !== '') {
            $sql .= ' AND (u.full_name LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($professionId !== null) {
            $sql .= ' AND u.id IN (SELECT DISTINCT teacher_id FROM `groups` WHERE profession_id = ? AND teacher_id IS NOT NULL)';
            $params[] = $professionId;
        }

        $sql .= ' ORDER BY u.full_name';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
