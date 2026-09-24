<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class GroupRepository
{
    public function all(): array
    {
        $sql = 'SELECT g.*, p.name_uz AS profession_name_uz, t.full_name AS teacher_name,
                       (SELECT COUNT(*) FROM enrollments e WHERE e.group_id = g.id AND e.status = "active") AS student_count
                FROM `groups` g
                JOIN professions p ON p.id = g.profession_id
                LEFT JOIN users t ON t.id = g.teacher_id
                ORDER BY g.start_date DESC';

        return Database::pdo()->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT g.*, p.name_uz AS profession_name_uz, t.full_name AS teacher_name
             FROM `groups` g
             JOIN professions p ON p.id = g.profession_id
             LEFT JOIN users t ON t.id = g.teacher_id
             WHERE g.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(int $professionId, ?int $teacherId, string $name, string $startDate, string $endDate): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO `groups` (profession_id, teacher_id, name, start_date, end_date) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$professionId, $teacherId, $name, $startDate, $endDate]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function studentsIn(int $groupId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.full_name, u.phone
             FROM enrollments e JOIN users u ON u.id = e.user_id
             WHERE e.group_id = ? AND e.status = "active"
             ORDER BY u.full_name'
        );
        $stmt->execute([$groupId]);

        return $stmt->fetchAll();
    }

    public function unassignedApprovedStudents(): array
    {
        $sql = "SELECT u.id, u.full_name, u.phone
                FROM users u
                LEFT JOIN enrollments e ON e.user_id = u.id AND e.status = 'active'
                WHERE u.role = 'student' AND e.id IS NULL
                ORDER BY u.full_name";

        return Database::pdo()->query($sql)->fetchAll();
    }

    public function enrollStudent(int $groupId, int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO enrollments (user_id, group_id, status) VALUES (?, ?, 'active')"
        );
        $stmt->execute([$userId, $groupId]);
    }

    public function enrollmentsForUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT e.id, e.status, g.id AS group_id, g.name AS group_name
             FROM enrollments e
             JOIN `groups` g ON g.id = e.group_id
             WHERE e.user_id = ?
             ORDER BY e.joined_at DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function updateEnrollmentStatus(int $enrollmentId, string $status): void
    {
        if (!in_array($status, ['active', 'completed', 'dropped'], true)) {
            return;
        }

        $stmt = Database::pdo()->prepare('UPDATE enrollments SET status = ? WHERE id = ?');
        $stmt->execute([$status, $enrollmentId]);
    }

    public function update(int $id, ?int $teacherId, string $name, string $startDate, string $endDate): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE `groups` SET teacher_id = ?, name = ?, start_date = ?, end_date = ? WHERE id = ?'
        );
        $stmt->execute([$teacherId, $name, $startDate, $endDate, $id]);
    }
}
