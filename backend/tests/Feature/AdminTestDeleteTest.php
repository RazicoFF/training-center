<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\TestController;
use PHPUnit\Framework\TestCase;

final class AdminTestDeleteTest extends TestCase
{
    private int $testId;
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM test_attempts');
        $pdo->exec('DELETE FROM test_question_selections');
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');

        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (profession_id, title_uz, title_ru, passing_score) VALUES (?, "T", "T", 50)')
            ->execute([$this->professionId]);
        $this->testId = (int) $pdo->lastInsertId();
    }

    public function testDeletingATestCascadesThroughQuestionsAnswersAndAttempts(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO questions (test_id, text_uz, text_ru) VALUES (?, "Q", "Q ru")')->execute([$this->testId]);
        $questionId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, "A", "A ru", 1)')->execute([$questionId]);

        $pdo->exec("DELETE FROM users WHERE phone = '+998987770300'");
        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('Attempt User', '+998987770300', 'x', 'student')")->execute();
        $userId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO test_attempts (user_id, test_id, score, passed) VALUES (?, ?, 80, 1)')->execute([$userId, $this->testId]);
        $pdo->prepare('INSERT INTO test_question_selections (user_id, test_id, question_ids) VALUES (?, ?, ?)')
            ->execute([$userId, $this->testId, (string) $questionId]);

        $router = new Router();
        (new TestController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/tests/{$this->testId}/delete", [], [], [
            'csrf_token' => $token,
        ]));

        $this->assertSame('/admin/tests', $result['redirect']);
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM tests WHERE id = {$this->testId}")->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM questions WHERE test_id = {$this->testId}")->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM answers WHERE question_id = {$questionId}")->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM test_attempts WHERE test_id = {$this->testId}")->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM test_question_selections WHERE test_id = {$this->testId}")->fetchColumn());
    }

    public function testDeletingANonexistentTestReturns404(): void
    {
        $router = new Router();
        (new TestController())->register($router);
        $token = Csrf::token();

        ob_start();
        $router->dispatch(new Request('POST', '/admin/tests/999999/delete', [], [], [
            'csrf_token' => $token,
        ]));
        $html = ob_get_clean();

        $this->assertStringContainsString('404', $html);
    }
}
