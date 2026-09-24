<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Request;
use App\Core\Router;
use App\Core\SiteView;
use App\Repositories\MediaRepository;

final class MediaController
{
    public function __construct(
        private readonly MediaRepository $media = new MediaRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/media', fn (Request $req) => $this->index($req));
    }

    private function index(Request $request): array
    {
        SiteView::render('site/media', ['mediaItems' => $this->media->all()]);
        return ['rendered' => true];
    }
}
