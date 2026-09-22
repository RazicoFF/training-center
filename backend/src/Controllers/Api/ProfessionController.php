<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Repositories\ProfessionRepository;

final class ProfessionController
{
    public function __construct(private readonly ProfessionRepository $repository = new ProfessionRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/professions', fn (Request $req) => $this->index($req));
    }

    private function index(Request $request): array
    {
        return ['professions' => $this->repository->all()];
    }
}
