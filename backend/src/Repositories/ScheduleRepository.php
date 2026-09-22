<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ScheduleRepository
{
    public function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT s.lesson_date, s.start_time, s.end_time, s.room, g.name AS group_name
             FROM schedule s
             JOIN `groups` g ON g.id = s.group_id
             JOIN enrollments e ON e.group_id = g.id
             WHERE e.user_id = ? AND e.status = 'active'
             ORDER BY s.lesson_date, s.start_time"
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }
}
