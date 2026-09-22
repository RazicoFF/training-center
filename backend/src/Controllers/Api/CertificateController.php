<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Repositories\CertificateRepository;

final class CertificateController
{
    public function __construct(private readonly CertificateRepository $repository = new CertificateRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/me/certificates', fn (Request $req) => $this->index($req));
        $router->get('/api/v1/certificates/{id}/download', fn (Request $req) => $this->download($req));
    }

    private function index(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        return ['certificates' => $this->repository->forUser($claims['user_id'])];
    }

    private function download(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        $certificate = $this->repository->find((int) $request->param('id'));

        if ($certificate === null || (int) $certificate['user_id'] !== $claims['user_id']) {
            return ['error' => ['code' => 'NOT_FOUND', 'message' => 'Certificate not found'], 'status' => 404];
        }

        return ['file_path' => $certificate['pdf_path']];
    }
}
