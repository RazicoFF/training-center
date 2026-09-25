<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\GroupRepository;
use App\Repositories\TestRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AutoCompleteEnrollmentTest extends TestCase
{
    private int $userId;
    private int $professionId;
    private int $groupId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM test_attempts');
        $pdo->exec('DELETE FROM test_question_selections');
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');
        $pdo->exec("DELETE FROM enrollments WHERE user_id IN (SELECT id FROM users WHERE phone = '+998987770230')");
        $pdo->exec("DELETE FROM `groups` WHERE name = 'Auto Complete Group'");
        $pdo->exec("DELETE FROM users WHERE phone = '+998987770230'");

        $this->userId = (new UserRepository())->create('Auto Complete Student', '+998987770230', 'x', 'student');
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();

        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "Auto Complete Group", "2026-01-01", "2026-06-01")')
            ->execute([$this->professionId]);
        $this->groupId = (int) $pdo->lastInsertId();
        (new GroupRepository())->enrollStudent($this->groupId, $this->userId);

        // Two tests for this profession.
        $pdo->prepare('INSERT INTO tests (profession_id, title_uz, title_ru, passing_score) VALUES (?, "T1", "T1", 70)')->execute([$this->professionId]);
        $test1Id = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO tests (profession_id, title_uz, title_ru, passing_score) VALUES (?, "T2", "T2", 70)')->execute([$this->professionId]);
        $test2Id = (int) $pdo->lastInsertId();

        $this->test1Id = $test1Id;
        $this->test2Id = $test2Id;
    }

    private int $test1Id;
    private int $test2Id;

    public function testNotAllPassedWhenOnlyOneOfTwoTestsPassed(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed) VALUES (?, ?, 90, 1)')->execute([$this->userId, $this->test1Id]);

        $repo = new TestRepository();
        $this->assertFalse($repo->allTestsPassedForProfession($this->userId, $this->professionId));
    }

    public function testAllPassedTriggersEnrollmentCompletion(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed) VALUES (?, ?, 90, 1)')->execute([$this->userId, $this->test1Id]);
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed) VALUES (?, ?, 80, 1)')->execute([$this->userId, $this->test2Id]);

        $testRepo = new TestRepository();
        $this->assertTrue($testRepo->allTestsPassedForProfession($this->userId, $this->professionId));

        $groupRepo = new GroupRepository();
        $groupRepo->completeEnrollmentsForProfession($this->userId, $this->professionId);

        $status = $pdo->query("SELECT status FROM enrollments WHERE user_id = {$this->userId} AND group_id = {$this->groupId}")->fetchColumn();
        $this->assertSame('completed', $status);
    }
}
