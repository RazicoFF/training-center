<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\MeController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class MeEndpointTest extends TestCase
{
    public function testReturnsProfileForValidToken(): void
    {
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998900000002'");
        $userId = (new UserRepository())->create('Me Student', '+998900000002', Auth::hashPassword('pass1234'), 'student');
        $token = Auth::issueToken($userId, 'student');

        $router = new Router();
        (new MeController())->register($router);

        $request = new Request('GET', '/api/v1/me', ['AUTHORIZATION' => "Bearer {$token}"], []);
        $result = $router->dispatch($request);

        $this->assertSame('Me Student', $result['full_name']);
    }

    public function testRejectsMissingToken(): void
    {
        $router = new Router();
        (new MeController())->register($router);

        $request = new Request('GET', '/api/v1/me', [], []);
        $result = $router->dispatch($request);

        $this->assertSame(401, $result['status']);
    }

    public function testScheduleReturnsLessonsForEnrolledGroup(): void
    {
        $pdo = \App\Core\Database::pdo();
        $pdo->exec("DELETE FROM schedule");
        $pdo->exec("DELETE FROM enrollments");
        $pdo->exec("DELETE FROM `groups`");
        $pdo->exec("DELETE FROM users WHERE phone = '+998900000003'");

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $userId = (new UserRepository())->create('Schedule Student', '+998900000003', Auth::hashPassword('pass1234'), 'student');

        $pdo->prepare('INSERT INTO `groups` (id, profession_id, name, start_date, end_date) VALUES (1, ?, ?, CURDATE(), CURDATE())')
            ->execute([$professionId, 'Test Group']);
        $pdo->prepare('INSERT INTO enrollments (user_id, group_id, status) VALUES (?, 1, "active")')->execute([$userId]);
        $pdo->prepare("INSERT INTO schedule (group_id, lesson_date, start_time, end_time, room) VALUES (1, CURDATE(), '09:00:00', '11:00:00', '101')")->execute();

        $token = Auth::issueToken($userId, 'student');

        $router = new Router();
        (new MeController())->register($router);

        $request = new Request('GET', '/api/v1/me/schedule', ['AUTHORIZATION' => "Bearer {$token}"], []);
        $result = $router->dispatch($request);

        $this->assertCount(1, $result['schedule']);
        $this->assertSame('101', $result['schedule'][0]['room']);
    }
}
