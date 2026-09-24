<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\TestRepository;
use PHPUnit\Framework\TestCase;

final class TestRandomizationTest extends TestCase
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
        $pdo->exec("DELETE FROM users WHERE phone = '+998987770210'");

        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('Random Test User', '+998987770210', 'x', 'student')")->execute();
        $this->userId = (int) $pdo->lastInsertId();

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare(
            'INSERT INTO tests (id, profession_id, title_uz, title_ru, passing_score, random_question_count) VALUES (1, ?, "T", "T", 50, 3)'
        )->execute([$professionId]);
        $this->testId = 1;

        for ($i = 1; $i <= 10; $i++) {
            $pdo->prepare('INSERT INTO questions (id, test_id, text_uz, text_ru) VALUES (?, 1, "Q", "Q")')->execute([$i]);
            $pdo->prepare('INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, "A", "A", 1)')->execute([$i]);
            $pdo->prepare('INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, "B", "B", 0)')->execute([$i]);
        }
    }

    public function testOnlyRandomQuestionCountIsReturned(): void
    {
        $repo = new TestRepository();

        $questions = $repo->questionsWithAnswers($this->testId, $this->userId);

        $this->assertCount(3, $questions);
        foreach ($questions as $q) {
            $this->assertCount(2, $q['answers']);
        }
    }

    public function testSelectionIsPersistedForScoring(): void
    {
        $repo = new TestRepository();

        $questions = $repo->questionsWithAnswers($this->testId, $this->userId);
        $shownIds = array_column($questions, 'id');

        $stored = Database::pdo()->prepare('SELECT question_ids FROM test_question_selections WHERE user_id = ? AND test_id = ?');
        $stored->execute([$this->userId, $this->testId]);
        $storedIds = array_map('intval', explode(',', $stored->fetchColumn()));

        sort($shownIds);
        sort($storedIds);
        $this->assertSame($shownIds, $storedIds);
    }

    public function testScoreUsesShownQuestionCountAsTotalNotFullBank(): void
    {
        $repo = new TestRepository();

        $questions = $repo->questionsWithAnswers($this->testId, $this->userId);
        // Answer only the first of the 3 shown questions correctly; leave the rest unanswered.
        // questionsWithAnswers() deliberately omits is_correct from its payload (it goes to the
        // test-taking client), so look it up directly instead.
        $firstQuestionId = (int) $questions[0]['id'];
        $correctAnswerStmt = Database::pdo()->prepare('SELECT id FROM answers WHERE question_id = ? AND is_correct = 1');
        $correctAnswerStmt->execute([$firstQuestionId]);
        $correctAnswerId = (int) $correctAnswerStmt->fetchColumn();

        $result = $repo->score($this->testId, $this->userId, [$correctAnswerId]);

        // 1 correct out of 3 shown (not out of the 10-question bank) = 33%.
        $this->assertSame(33, $result['score']);
    }

    public function testSubmittingAnswerToAQuestionNotShownIsRejected(): void
    {
        $repo = new TestRepository();
        $repo->questionsWithAnswers($this->testId, $this->userId);

        $allQuestionIds = range(1, 10);
        $shownStmt = Database::pdo()->prepare('SELECT question_ids FROM test_question_selections WHERE user_id = ? AND test_id = ?');
        $shownStmt->execute([$this->userId, $this->testId]);
        $shownIds = array_map('intval', explode(',', $shownStmt->fetchColumn()));
        $notShownQuestionId = array_values(array_diff($allQuestionIds, $shownIds))[0];

        $answerStmt = Database::pdo()->prepare('SELECT id FROM answers WHERE question_id = ? AND is_correct = 1');
        $answerStmt->execute([$notShownQuestionId]);
        $answerId = (int) $answerStmt->fetchColumn();

        $this->expectException(\InvalidArgumentException::class);
        $repo->score($this->testId, $this->userId, [$answerId]);
    }
}
