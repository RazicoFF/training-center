<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\TestController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class TestQuestionsEndpointTest extends TestCase
{
    public function testReturnsQuestionsWithAnswersOmittingIsCorrect(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM test_attempts');
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM test_question_selections');
        $pdo->exec('DELETE FROM tests');
        $pdo->exec("DELETE FROM users WHERE phone = '+998900000006'");

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (id, profession_id, title_uz, title_ru, passing_score) VALUES (1, ?, "T", "T", 50)')
            ->execute([$professionId]);
        $pdo->exec('INSERT INTO questions (id, test_id, text_uz, text_ru) VALUES (1, 1, "Q1", "Q1")');
        $pdo->exec('INSERT INTO answers (id, question_id, text_uz, text_ru, is_correct) VALUES (1, 1, "A", "A", 1)');
        $pdo->exec('INSERT INTO answers (id, question_id, text_uz, text_ru, is_correct) VALUES (2, 1, "B", "B", 0)');

        $userId = (new UserRepository())->create('Question Viewer', '+998900000006', Auth::hashPassword('pass1234'), 'student');
        $token = Auth::issueToken($userId, 'student');

        $router = new Router();
        (new TestController())->register($router);

        $request = new Request('GET', '/api/v1/me/tests/1', ['AUTHORIZATION' => "Bearer {$token}"], []);
        $result = $router->dispatch($request);

        $this->assertCount(1, $result['questions']);
        $this->assertCount(2, $result['questions'][0]['answers']);
        $this->assertArrayNotHasKey('is_correct', $result['questions'][0]['answers'][0]);
        $this->assertArrayNotHasKey('is_correct', $result['questions'][0]['answers'][1]);
    }

    public function testRejectsMissingToken(): void
    {
        $router = new Router();
        (new TestController())->register($router);

        $request = new Request('GET', '/api/v1/me/tests/1', [], []);
        $result = $router->dispatch($request);

        $this->assertSame(401, $result['status']);
    }
}
