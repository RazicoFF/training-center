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
}
