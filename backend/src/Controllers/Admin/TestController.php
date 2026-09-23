<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\ProfessionRepository;
use App\Repositories\TestRepository;

final class TestController
{
    public function __construct(
        private readonly TestRepository $tests = new TestRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/tests', fn (Request $req) => $this->index($req));
        $router->get('/admin/tests/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/tests', fn (Request $req) => $this->create($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('tests/index', ['tests' => $this->tests->allWithProfession()]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('tests/create', ['professions' => $this->professions->all()]);
        return ['rendered' => true];
    }

    private function create(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $professionId = (int) ($body['profession_id'] ?? 0);
        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));
        $passingScore = (int) ($body['passing_score'] ?? 70);

        if ($professionId <= 0 || $titleUz === '' || $titleRu === '') {
            return ['redirect' => '/admin/tests/create', 'flash' => 'Barcha maydonlarni to\'ldiring'];
        }

        $testId = $this->tests->create($professionId, $titleUz, $titleRu, $passingScore);

        return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('test_created')];
    }
}
