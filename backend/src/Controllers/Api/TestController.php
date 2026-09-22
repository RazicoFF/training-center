<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Repositories\TestRepository;

final class TestController
{
    public function __construct(private readonly TestRepository $repository = new TestRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/me/tests', fn (Request $req) => $this->index($req));
        $router->post('/api/v1/me/tests/{id}/submit', fn (Request $req) => $this->submit($req));
    }

    private function index(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        return ['tests' => $this->repository->availableForUser($claims['user_id'])];
    }

    private function submit(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        $testId = (int) $request->param('id');
        $answerIds = array_map('intval', $request->jsonBody()['answers'] ?? []);

        $result = $this->repository->score($testId, $answerIds);
        $this->repository->recordAttempt($claims['user_id'], $testId, $result['score'], $result['passed']);

        return $result;
    }
}
