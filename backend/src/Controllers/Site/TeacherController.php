<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Request;
use App\Core\Router;
use App\Core\SiteView;
use App\Repositories\TeacherProfileRepository;

final class TeacherController
{
    public function __construct(
        private readonly TeacherProfileRepository $profiles = new TeacherProfileRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/teachers', fn (Request $req) => $this->index($req));
    }

    private function index(Request $request): array
    {
        SiteView::render('site/teachers', ['teachers' => $this->profiles->allTeachersWithProfiles()]);
        return ['rendered' => true];
    }
}
