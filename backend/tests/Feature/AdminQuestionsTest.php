<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\QuestionController;
use PHPUnit\Framework\TestCase;

final class AdminQuestionsTest extends TestCase
{
    private int $testId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (profession_id, title_uz, title_ru, passing_score) VALUES (?, "T", "T", 50)')
            ->execute([$professionId]);
        $this->testId = (int) $pdo->lastInsertId();
    }

    public function testStoreFiltersOutBlankAnswerPairsAndDoesNotPersistThem(): void
    {
        $router = new Router();
        (new QuestionController())->register($router);
        $token = Csrf::token();

        // The view always submits 4 answer_text_uz[]/answer_text_ru[] pairs; only the first
        // 2 are filled in by the operator here, the last 2 arrive as blank strings.
        $result = $router->dispatch(new Request('POST', "/admin/tests/{$this->testId}/questions", [], [], [
            'csrf_token' => $token,
            'text_uz' => 'Savol matni',
            'text_ru' => 'Text voprosa',
            'answer_text_uz' => ['To\'g\'ri javob', 'Noto\'g\'ri javob', '', ''],
            'answer_text_ru' => ['Pravilny otvet', 'Nepravilny otvet', '', ''],
            'correct_index' => '0',
        ]));

        $this->assertSame("/admin/tests/{$this->testId}/questions", $result['redirect']);

        $pdo = Database::pdo();
        $questionId = (int) $pdo->query('SELECT id FROM questions ORDER BY id DESC LIMIT 1')->fetchColumn();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM answers WHERE question_id = ?');
        $stmt->execute([$questionId]);
        $this->assertSame(2, (int) $stmt->fetchColumn());

        $blankStmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE question_id = ? AND text_uz = ''");
        $blankStmt->execute([$questionId]);
        $this->assertSame(0, (int) $blankStmt->fetchColumn());
    }

    public function testStoreRejectsWhenCorrectIndexPointsAtABlankAnswer(): void
    {
        $router = new Router();
        (new QuestionController())->register($router);
        $token = Csrf::token();

        // correct_index=2 points at a blank pair (index 2 is empty), so nothing should be
        // inserted and the operator should be redirected back with a flash.
        $result = $router->dispatch(new Request('POST', "/admin/tests/{$this->testId}/questions", [], [], [
            'csrf_token' => $token,
            'text_uz' => 'Savol matni',
            'text_ru' => 'Text voprosa',
            'answer_text_uz' => ['Javob A', 'Javob B', '', ''],
            'answer_text_ru' => ['Otvet A', 'Otvet B', '', ''],
            'correct_index' => '2',
        ]));

        $this->assertSame("/admin/tests/{$this->testId}/questions", $result['redirect']);
        $this->assertArrayHasKey('flash', $result);

        $pdo = Database::pdo();
        $count = (int) $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
        $this->assertSame(0, $count);
    }

    public function testStoreRejectsWhenFewerThanTwoNonBlankAnswersSurvive(): void
    {
        $router = new Router();
        (new QuestionController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/tests/{$this->testId}/questions", [], [], [
            'csrf_token' => $token,
            'text_uz' => 'Savol matni',
            'text_ru' => 'Text voprosa',
            'answer_text_uz' => ['Yagona javob', '', '', ''],
            'answer_text_ru' => ['Odin otvet', '', '', ''],
            'correct_index' => '0',
        ]));

        $this->assertSame("/admin/tests/{$this->testId}/questions", $result['redirect']);
        $this->assertArrayHasKey('flash', $result);

        $pdo = Database::pdo();
        $count = (int) $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
        $this->assertSame(0, $count);
    }
}
