<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Repositories\NewsRepository;

final class NewsController
{
    public function __construct(private readonly NewsRepository $repository = new NewsRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/news', fn (Request $req) => $this->index($req));
    }

    private function index(Request $request): array
    {
        return ['news' => $this->repository->all()];
    }
}
