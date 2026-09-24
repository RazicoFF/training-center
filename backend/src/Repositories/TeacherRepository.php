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

    public function search(?string $q = null): array
    {
        $sql = "SELECT id, full_name, phone, created_at FROM users WHERE role = 'teacher'";
        $params = [];

        if ($q !== null && $q !== '') {
            $sql .= ' AND (full_name LIKE ? OR phone LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY full_name';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
