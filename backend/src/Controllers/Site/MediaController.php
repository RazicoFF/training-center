<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Lang;
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
        SiteView::render('site/media', [
            'mediaItems' => $this->media->all(),
            'meta' => ['title' => Lang::t('nav_media'), 'description' => Lang::t('site_meta_media')],
        ]);
        return ['rendered' => true];
    }
}
