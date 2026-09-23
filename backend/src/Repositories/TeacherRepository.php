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
}
