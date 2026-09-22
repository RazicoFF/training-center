<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Router;
use App\Repositories\UserRepository;

final class AuthController
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->post('/api/v1/auth/login', fn (Request $req) => $this->login($req));
    }

    private function login(Request $request): array
    {
        $body = $request->jsonBody();
        $phone = trim((string) ($body['phone'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        $user = $this->users->findByPhone($phone);

        if ($user === null || !Auth::verifyPassword($password, $user['password_hash'])) {
            return [
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Phone or password incorrect'],
                'status' => 401,
            ];
        }

        return ['token' => Auth::issueToken((int) $user['id'], $user['role'])];
    }
}
