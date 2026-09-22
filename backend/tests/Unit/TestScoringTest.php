<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\TestRepository;
use PHPUnit\Framework\TestCase;

final class TestScoringTest extends TestCase
{
    private int $testId;
    private array $correctAnswerIds = [];

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (id, profession_id, title_uz, title_ru, passing_score) VALUES (1, ?, "T", "T", 70)')
            ->execute([$professionId]);
        $this->testId = 1;

        for ($i = 1; $i <= 2; $i++) {
            $pdo->prepare('INSERT INTO questions (id, test_id, text_uz, text_ru) VALUES (?, 1, "Q", "Q")')->execute([$i]);
            $pdo->prepare('INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, "A", "A", 1)')->execute([$i]);
            $this->correctAnswerIds[] = (int) Database::pdo()->lastInsertId();
            $pdo->prepare('INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, "B", "B", 0)')->execute([$i]);
        }
    }

    public function testAllCorrectAnswersGivesFullScoreAndPasses(): void
    {
        $repo = new TestRepository();
        $result = $repo->score($this->testId, $this->correctAnswerIds);

        $this->assertSame(100, $result['score']);
        $this->assertTrue($result['passed']);
    }

    public function testZeroCorrectAnswersFails(): void
    {
        $repo = new TestRepository();
        $result = $repo->score($this->testId, [999999, 999998]);

        $this->assertSame(0, $result['score']);
        $this->assertFalse($result['passed']);
    }
}
