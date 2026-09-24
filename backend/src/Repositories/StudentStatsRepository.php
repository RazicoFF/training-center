<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class StudentStatsRepository
{
    /**
     * @return array{total:int, studying:int, completed:int, dropped:int, inExam:int}
     */
    public function summary(): array
    {
        $pdo = Database::pdo();

        $total = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
        $studying = (int) $pdo->query("SELECT COUNT(DISTINCT user_id) FROM enrollments WHERE status = 'active'")->fetchColumn();
        $completed = (int) $pdo->query("SELECT COUNT(DISTINCT user_id) FROM enrollments WHERE status = 'completed'")->fetchColumn();
        $dropped = (int) $pdo->query("SELECT COUNT(DISTINCT user_id) FROM enrollments WHERE status = 'dropped'")->fetchColumn();

        $inExam = (int) $pdo->query(
            "SELECT COUNT(DISTINCT e.user_id)
             FROM enrollments e
             JOIN `groups` g ON g.id = e.group_id
             JOIN tests t ON t.profession_id = g.profession_id
             WHERE e.status = 'active'
               AND t.id NOT IN (SELECT test_id FROM test_attempts WHERE user_id = e.user_id AND passed = 1)
               AND (t.opens_at IS NULL OR NOW() >= t.opens_at)
               AND (t.closes_at IS NULL OR NOW() <= t.closes_at)"
        )->fetchColumn();

        return [
            'total' => $total,
            'studying' => $studying,
            'completed' => $completed,
            'dropped' => $dropped,
            'inExam' => $inExam,
        ];
    }
}
