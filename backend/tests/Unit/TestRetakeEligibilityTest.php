<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\TestRepository;
use PHPUnit\Framework\TestCase;

final class TestRetakeEligibilityTest extends TestCase
{
    private int $testId;
    private int $userId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM test_attempts');
        $pdo->exec('DELETE FROM test_question_selections');
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');
        $pdo->exec("DELETE FROM users WHERE phone = '+998987770220'");

        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('Retake User', '+998987770220', 'x', 'student')")->execute();
        $this->userId = (int) $pdo->lastInsertId();

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (id, profession_id, title_uz, title_ru, passing_score) VALUES (1, ?, "T", "T", 70)')
            ->execute([$professionId]);
        $this->testId = 1;
    }

    public function testEligibleWithNoAttempts(): void
    {
        $repo = new TestRepository();
        $result = $repo->retakeEligibility($this->userId, $this->testId);

        $this->assertTrue($result['eligible']);
    }

    public function testTooEarlyRightAfterFirstFailedAttempt(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed, attempted_at) VALUES (?, ?, 40, 0, NOW())')
            ->execute([$this->userId, $this->testId]);

        $repo = new TestRepository();
        $result = $repo->retakeEligibility($this->userId, $this->testId);

        $this->assertFalse($result['eligible']);
        $this->assertSame('too_early', $result['reason']);
        $this->assertNotNull($result['availableAt']);
    }

    public function testEligibleFifteenDaysAfterFirstFailedAttempt(): void
    {
        $pdo = Database::pdo();
        $fifteenDaysAgo = (new \DateTimeImmutable('-15 days'))->format('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed, attempted_at) VALUES (?, ?, 40, 0, ?)')
            ->execute([$this->userId, $this->testId, $fifteenDaysAgo]);

        $repo = new TestRepository();
        $result = $repo->retakeEligibility($this->userId, $this->testId);

        $this->assertTrue($result['eligible']);
    }

    public function testStillEligibleThirtyDaysAfterFirstFailedAttempt(): void
    {
        // No expiry on the retake window - only the 14-day minimum wait and the 2-attempt cap.
        $pdo = Database::pdo();
        $thirtyDaysAgo = (new \DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed, attempted_at) VALUES (?, ?, 40, 0, ?)')
            ->execute([$this->userId, $this->testId, $thirtyDaysAgo]);

        $repo = new TestRepository();
        $result = $repo->retakeEligibility($this->userId, $this->testId);

        $this->assertTrue($result['eligible']);
    }

    public function testNotEligibleAfterTwoAttempts(): void
    {
        $pdo = Database::pdo();
        $fifteenDaysAgo = (new \DateTimeImmutable('-15 days'))->format('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed, attempted_at) VALUES (?, ?, 40, 0, NOW())')
            ->execute([$this->userId, $this->testId]);
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed, attempted_at) VALUES (?, ?, 50, 0, ?)')
            ->execute([$this->userId, $this->testId, $fifteenDaysAgo]);

        $repo = new TestRepository();
        $result = $repo->retakeEligibility($this->userId, $this->testId);

        $this->assertFalse($result['eligible']);
        $this->assertSame('max_attempts', $result['reason']);
    }
}
