<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Repositories\MediaRepository;

final class MediaController
{
    public function __construct(private readonly MediaRepository $repository = new MediaRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/media', fn (Request $req) => $this->index($req));
    }

    private function index(Request $request): array
    {
        return ['media' => $this->repository->all()];
    }
}
