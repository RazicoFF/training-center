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

    /**
     * A searchable/filterable student directory: one row per student with their
     * group names, test pass/attempt counts, and certificate count already
     * aggregated, so the admin list and the dashboard drill-down links can
     * render everything without N+1 queries.
     *
     * @param string|null $stat one of 'studying'|'completed'|'dropped'|'in_exam', or null for all students
     * @param int|null $teacherId when given, restricts to students enrolled in a group taught
     *                            by this teacher (the teacher-portal scope)
     */
    public function list(
        ?string $q = null,
        ?int $groupId = null,
        ?string $stat = null,
        ?int $professionId = null,
        ?int $brandId = null,
        ?int $teacherId = null
    ): array {
        $sql = "SELECT u.id, u.full_name, u.phone, u.photo_url,
                    (SELECT GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ')
                       FROM enrollments e JOIN `groups` g ON g.id = e.group_id
                      WHERE e.user_id = u.id) AS group_names,
                    (SELECT COUNT(*) FROM test_attempts ta WHERE ta.user_id = u.id) AS tests_taken,
                    (SELECT COUNT(*) FROM test_attempts ta WHERE ta.user_id = u.id AND ta.passed = 1) AS tests_passed,
                    (SELECT COUNT(*) FROM certificates c WHERE c.user_id = u.id) AS certificate_count,
                    (SELECT c2.id FROM certificates c2 WHERE c2.user_id = u.id ORDER BY c2.issue_date DESC LIMIT 1) AS latest_certificate_id
                FROM users u
                WHERE u.role = 'student'";
        $params = [];

        if ($q !== null && $q !== '') {
            $sql .= ' AND (u.full_name LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($groupId !== null) {
            $sql .= ' AND EXISTS (SELECT 1 FROM enrollments e2 WHERE e2.user_id = u.id AND e2.group_id = ?)';
            $params[] = $groupId;
        }

        if ($professionId !== null) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM enrollments e4 JOIN `groups` g4 ON g4.id = e4.group_id
                WHERE e4.user_id = u.id AND g4.profession_id = ?
            )';
            $params[] = $professionId;
        }

        if ($brandId !== null) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM enrollments e5 JOIN `groups` g5 ON g5.id = e5.group_id
                WHERE e5.user_id = u.id AND g5.brand_id = ?
            )';
            $params[] = $brandId;
        }

        if ($teacherId !== null) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM enrollments e6 JOIN `groups` g6 ON g6.id = e6.group_id
                WHERE e6.user_id = u.id AND g6.teacher_id = ?
            )';
            $params[] = $teacherId;
        }

        switch ($stat) {
            case 'studying':
                $sql .= " AND EXISTS (SELECT 1 FROM enrollments e3 WHERE e3.user_id = u.id AND e3.status = 'active')";
                break;
            case 'completed':
                $sql .= " AND EXISTS (SELECT 1 FROM enrollments e3 WHERE e3.user_id = u.id AND e3.status = 'completed')";
                break;
            case 'dropped':
                $sql .= " AND EXISTS (SELECT 1 FROM enrollments e3 WHERE e3.user_id = u.id AND e3.status = 'dropped')";
                break;
            case 'in_exam':
                $sql .= " AND EXISTS (
                    SELECT 1 FROM enrollments e3
                    JOIN `groups` g3 ON g3.id = e3.group_id
                    JOIN tests t3 ON t3.profession_id = g3.profession_id
                    WHERE e3.user_id = u.id AND e3.status = 'active'
                      AND t3.id NOT IN (SELECT test_id FROM test_attempts WHERE user_id = u.id AND passed = 1)
                      AND (t3.opens_at IS NULL OR NOW() >= t3.opens_at)
                      AND (t3.closes_at IS NULL OR NOW() <= t3.closes_at)
                )";
                break;
        }

        $sql .= ' ORDER BY u.full_name';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
