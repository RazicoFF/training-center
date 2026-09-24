<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\TestController;
use App\Controllers\Admin\QuestionController;
use PHPUnit\Framework\TestCase;

final class AdminTestsTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
    }

    public function testCreateTestAndAddQuestionWithAnswers(): void
    {
        $router = new Router();
        (new TestController())->register($router);
        (new QuestionController())->register($router);
        $token = Csrf::token();

        $createResult = $router->dispatch(new Request('POST', '/admin/tests', [], [], [
            'csrf_token' => $token,
            'profession_id' => (string) $this->professionId,
            'title_uz' => 'Yakuniy',
            'title_ru' => 'Финал',
            'passing_score' => '60',
        ]));

        $this->assertArrayHasKey('redirect', $createResult);
        $testId = (int) Database::pdo()->query("SELECT id FROM tests WHERE title_uz = 'Yakuniy'")->fetchColumn();
        $this->assertGreaterThan(0, $testId);

        $questionResult = $router->dispatch(new Request('POST', "/admin/tests/{$testId}/questions", [], [], [
            'csrf_token' => $token,
            'text_uz' => 'Savol?',
            'text_ru' => 'Вопрос?',
            'answer_text_uz' => ['A', 'B'],
            'answer_text_ru' => ['А', 'Б'],
            'correct_index' => '0',
        ]));

        $this->assertArrayHasKey('redirect', $questionResult);

        $questionId = (int) Database::pdo()->query("SELECT id FROM questions WHERE test_id = {$testId}")->fetchColumn();
        $answers = Database::pdo()->query("SELECT text_uz, is_correct FROM answers WHERE question_id = {$questionId} ORDER BY id")->fetchAll();

        $this->assertCount(2, $answers);
        $this->assertSame(1, (int) $answers[0]['is_correct']);
        $this->assertSame(0, (int) $answers[1]['is_correct']);
    }

    public function testEditFormRendersExistingValuesAndUpdateSavesChanges(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO tests (profession_id, title_uz, title_ru, passing_score) VALUES (?, ?, ?, ?)')
            ->execute([$this->professionId, 'Original Title', 'Оригинал', 70]);
        $testId = (int) $pdo->lastInsertId();

        $router = new Router();
        (new TestController())->register($router);

        ob_start();
        $editResult = $router->dispatch(new Request('GET', "/admin/tests/{$testId}/edit", [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $editResult);
        $this->assertStringContainsString('Original Title', $html);

        $token = Csrf::token();
        $updateResult = $router->dispatch(new Request('POST', "/admin/tests/{$testId}/edit", [], [], [
            'csrf_token' => $token,
            'title_uz' => 'Updated Title',
            'title_ru' => 'Обновлённый',
            'passing_score' => '80',
        ]));

        $this->assertArrayHasKey('redirect', $updateResult);

        $row = $pdo->query("SELECT title_uz, passing_score FROM tests WHERE id = {$testId}")->fetch();
        $this->assertSame('Updated Title', $row['title_uz']);
        $this->assertSame(80, (int) $row['passing_score']);
    }
}
