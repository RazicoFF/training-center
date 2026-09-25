<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Repositories\ProfessionBrandRepository;
use App\Repositories\ProfessionRepository;
use App\Repositories\ProfessionVideoRepository;
use App\Repositories\TestRepository;

final class ProfessionController
{
    public function __construct(
        private readonly ProfessionRepository $repository = new ProfessionRepository(),
        private readonly ProfessionVideoRepository $videos = new ProfessionVideoRepository(),
        private readonly TestRepository $tests = new TestRepository(),
        private readonly ProfessionBrandRepository $brands = new ProfessionBrandRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/professions', fn (Request $req) => $this->index($req));
        $router->get('/api/v1/professions/{id}', fn (Request $req) => $this->show($req));
    }

    private function index(Request $request): array
    {
        return ['professions' => $this->repository->all()];
    }

    private function show(Request $request): array
    {
        $professionId = (int) $request->param('id');
        $profession = $this->repository->find($professionId);

        if ($profession === null) {
            return ['error' => ['code' => 'NOT_FOUND', 'message' => 'Profession not found'], 'status' => 404];
        }

        $profession['videos'] = $this->videos->forProfession($professionId);
        $profession['tests'] = $this->tests->forProfession($professionId);
        $profession['brands'] = $this->brands->forProfession($professionId);

        return $profession;
    }
}
