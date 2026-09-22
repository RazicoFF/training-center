<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Repositories\UserRepository;

final class MeController
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/me', fn (Request $req) => $this->show($req));
    }

    private function show(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        $user = $this->users->find($claims['user_id']);
        unset($user['password_hash']);

        return $user;
    }
}
