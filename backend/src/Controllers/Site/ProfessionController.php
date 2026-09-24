<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Request;
use App\Core\Router;
use App\Core\SiteView;
use App\Repositories\ProfessionRepository;
use App\Repositories\ProfessionVideoRepository;
use App\Repositories\TestRepository;

final class ProfessionController
{
    public function __construct(
        private readonly ProfessionRepository $professions = new ProfessionRepository(),
        private readonly ProfessionVideoRepository $videos = new ProfessionVideoRepository(),
        private readonly TestRepository $tests = new TestRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/professions/{id}', fn (Request $req) => $this->show($req));
    }

    private function show(Request $request): array
    {
        $professionId = (int) $request->param('id');
        $profession = $this->professions->find($professionId);

        if ($profession === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        SiteView::render('site/profession_show', [
            'profession' => $profession,
            'videos' => $this->videos->forProfession($professionId),
            'tests' => $this->tests->forProfession($professionId),
        ]);
        return ['rendered' => true];
    }
}
