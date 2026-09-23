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

final class AutoCertificateOnPassTest extends TestCase
{
    public function testPassingSubmitIssuesCertificateExactlyOnce(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM certificates');
        $pdo->exec('DELETE FROM test_attempts');
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');
        $pdo->exec("DELETE FROM users WHERE phone = '+998977777777'");

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (id, profession_id, title_uz, title_ru, passing_score) VALUES (1, ?, "T", "T", 50)')
            ->execute([$professionId]);
        $pdo->exec('INSERT INTO questions (id, test_id, text_uz, text_ru) VALUES (1, 1, "Q", "Q")');
        $pdo->exec('INSERT INTO answers (id, question_id, text_uz, text_ru, is_correct) VALUES (1, 1, "A", "A", 1)');

        $userId = (new UserRepository())->create('Cert Auto', '+998977777777', Auth::hashPassword('x'), 'student');
        $token = Auth::issueToken($userId, 'student');

        $router = new Router();
        (new TestController())->register($router);

        // First submit: passes, should issue a certificate.
        $router->dispatch(new Request('POST', '/api/v1/me/tests/1/submit', ['AUTHORIZATION' => "Bearer {$token}"], ['answers' => [1]]));

        $count = (int) $pdo->query("SELECT COUNT(*) FROM certificates WHERE user_id = {$userId} AND profession_id = {$professionId}")->fetchColumn();
        $this->assertSame(1, $count);

        // Second submit: passes again, must NOT issue a second certificate.
        $router->dispatch(new Request('POST', '/api/v1/me/tests/1/submit', ['AUTHORIZATION' => "Bearer {$token}"], ['answers' => [1]]));

        $countAfterSecondSubmit = (int) $pdo->query("SELECT COUNT(*) FROM certificates WHERE user_id = {$userId} AND profession_id = {$professionId}")->fetchColumn();
        $this->assertSame(1, $countAfterSecondSubmit);
    }
}
