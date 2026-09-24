<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\GroupRepository;
use App\Repositories\StudentStatsRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class StudentStatsListTest extends TestCase
{
    private int $professionId;
    private int $groupId;
    private int $studyingStudentId;
    private int $completedStudentId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM enrollments WHERE user_id IN (SELECT id FROM users WHERE phone IN ('+998987770100', '+998987770101'))");
        $pdo->exec("DELETE FROM users WHERE phone IN ('+998987770100', '+998987770101')");

        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "Stats List Group", "2026-01-01", "2026-06-01")')
            ->execute([$this->professionId]);
        $this->groupId = (int) $pdo->lastInsertId();

        $users = new UserRepository();
        $this->studyingStudentId = $users->create('Studying Student', '+998987770100', 'x', 'student');
        $this->completedStudentId = $users->create('Completed Student', '+998987770101', 'x', 'student');

        $groups = new GroupRepository();
        $groups->enrollStudent($this->groupId, $this->studyingStudentId);
        $groups->enrollStudent($this->groupId, $this->completedStudentId);
        $completedEnrollmentId = (int) $pdo->query("SELECT id FROM enrollments WHERE user_id = {$this->completedStudentId}")->fetchColumn();
        $groups->updateEnrollmentStatus($completedEnrollmentId, 'completed');
    }

    public function testFiltersByStatCategory(): void
    {
        $repo = new StudentStatsRepository();

        $studying = array_column($repo->list(null, null, 'studying'), 'id');
        $completed = array_column($repo->list(null, null, 'completed'), 'id');

        $this->assertContains($this->studyingStudentId, $studying);
        $this->assertNotContains($this->completedStudentId, $studying);
        $this->assertContains($this->completedStudentId, $completed);
        $this->assertNotContains($this->studyingStudentId, $completed);
    }

    public function testFiltersByGroupAndSearch(): void
    {
        $repo = new StudentStatsRepository();

        $byGroup = array_column($repo->list(null, $this->groupId, null), 'id');
        $this->assertContains($this->studyingStudentId, $byGroup);
        $this->assertContains($this->completedStudentId, $byGroup);

        $bySearch = $repo->list('Studying Student', null, null);
        $this->assertCount(1, $bySearch);
        $this->assertSame($this->studyingStudentId, $bySearch[0]['id']);
        $this->assertSame('Stats List Group', $bySearch[0]['group_names']);
    }
}
