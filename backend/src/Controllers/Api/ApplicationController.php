<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Repositories\ApplicationRepository;
use App\Repositories\ProfessionRepository;

final class ApplicationController
{
    public function __construct(
        private readonly ApplicationRepository $repository = new ApplicationRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->post('/api/v1/applications', fn (Request $req) => $this->store($req));
    }

    private function store(Request $request): array
    {
        $body = $request->jsonBody();
        $fullName = trim((string) ($body['full_name'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));
        $professionId = (int) ($body['profession_id'] ?? 0);

        if ($fullName === '' || $phone === '' || $professionId <= 0) {
            return [
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'full_name, phone, profession_id required'],
                'status' => 422,
            ];
        }

        if ($this->professions->find($professionId) === null) {
            return [
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'profession_id does not exist'],
                'status' => 422,
            ];
        }

        $id = $this->repository->create($fullName, $phone, $professionId);

        return ['id' => $id, 'status' => 201];
    }
}
