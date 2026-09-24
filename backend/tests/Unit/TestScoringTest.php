<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\TestRepository;
use PHPUnit\Framework\TestCase;

final class TestScoringTest extends TestCase
{
    private int $testId;
    private int $userId;
    private array $correctAnswerIds = [];
    private array $incorrectAnswerIds = [];

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        // test_attempts must be cleared before tests/questions/answers, or the DELETE FROM tests
        // below can hit a FK violation from an attempt row left by another test in this run.
        $pdo->exec('DELETE FROM test_attempts');
        $pdo->exec('DELETE FROM test_question_selections');
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');

        $pdo->exec("DELETE FROM users WHERE phone = '+998987770200'");
        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('Scoring Test User', '+998987770200', 'x', 'student')")->execute();
        $this->userId = (int) $pdo->lastInsertId();

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (id, profession_id, title_uz, title_ru, passing_score) VALUES (1, ?, "T", "T", 70)')
            ->execute([$professionId]);
        $this->testId = 1;

        for ($i = 1; $i <= 2; $i++) {
            $pdo->prepare('INSERT INTO questions (id, test_id, text_uz, text_ru) VALUES (?, 1, "Q", "Q")')->execute([$i]);
            $pdo->prepare('INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, "A", "A", 1)')->execute([$i]);
            $this->correctAnswerIds[] = (int) Database::pdo()->lastInsertId();
            $pdo->prepare('INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, "B", "B", 0)')->execute([$i]);
            $this->incorrectAnswerIds[] = (int) Database::pdo()->lastInsertId();
        }
    }

    public function testAllCorrectAnswersGivesFullScoreAndPasses(): void
    {
        $repo = new TestRepository();
        $result = $repo->score($this->testId, $this->userId, $this->correctAnswerIds);

        $this->assertSame(100, $result['score']);
        $this->assertTrue($result['passed']);
    }

    public function testUnknownAnswerIdsAreRejectedAsValidationError(): void
    {
        $repo = new TestRepository();

        // Unknown ids don't belong to this test's questions, so this is a validation error,
        // not a legitimate zero-score submission.
        $this->expectException(\InvalidArgumentException::class);
        $repo->score($this->testId, $this->userId, [999999, 999998]);
    }

    public function testPartialCreditGivesFiftyPercent(): void
    {
        $repo = new TestRepository();
        // One correct answer (question 1), one incorrect answer (question 2) = 1 of 2 correct.
        $result = $repo->score($this->testId, $this->userId, [$this->correctAnswerIds[0], $this->incorrectAnswerIds[1]]);

        $this->assertSame(50, $result['score']);
        $this->assertFalse($result['passed']);
    }

    public function testAnswersFromADifferentTestAreRejected(): void
    {
        $pdo = Database::pdo();
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (id, profession_id, title_uz, title_ru, passing_score) VALUES (2, ?, "T2", "T2", 70)')
            ->execute([$professionId]);
        $pdo->exec('INSERT INTO questions (id, test_id, text_uz, text_ru) VALUES (100, 2, "Q", "Q")');
        $pdo->exec('INSERT INTO answers (id, question_id, text_uz, text_ru, is_correct) VALUES (500, 100, "A", "A", 1)');

        $repo = new TestRepository();

        $this->expectException(\InvalidArgumentException::class);
        // Answer 500 belongs to test 2's question, submitted against test 1.
        $repo->score($this->testId, $this->userId, [500, $this->correctAnswerIds[1]]);
    }

    public function testDuplicateQuestionSubmissionsAreRejected(): void
    {
        $repo = new TestRepository();

        $this->expectException(\InvalidArgumentException::class);
        // Both answer ids belong to question 1 (correct + incorrect option for the same question).
        $repo->score($this->testId, $this->userId, [$this->correctAnswerIds[0], $this->incorrectAnswerIds[0]]);
    }
}
