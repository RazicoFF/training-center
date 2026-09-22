<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\AuthController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AuthEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998900000001'");
        (new UserRepository())->create('Test Student', '+998900000001', Auth::hashPassword('pass1234'), 'student');
    }

    public function testLoginWithCorrectCredentialsReturnsToken(): void
    {
        $router = new Router();
        (new AuthController())->register($router);

        $request = new Request('POST', '/api/v1/auth/login', [], [
            'phone' => '+998900000001',
            'password' => 'pass1234',
        ]);

        $result = $router->dispatch($request);

        $this->assertArrayHasKey('token', $result);
        $this->assertNotNull(Auth::verifyToken($result['token']));
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        $router = new Router();
        (new AuthController())->register($router);

        $request = new Request('POST', '/api/v1/auth/login', [], [
            'phone' => '+998900000001',
            'password' => 'wrong',
        ]);

        $result = $router->dispatch($request);

        $this->assertSame(401, $result['status']);
    }
}
