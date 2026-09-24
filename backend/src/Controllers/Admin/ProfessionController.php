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

final class ProfessionController
{
    public function __construct(
        private readonly ProfessionRepository $professions = new ProfessionRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/professions', fn (Request $req) => $this->index($req));
        $router->get('/admin/professions/{id}/edit', fn (Request $req) => $this->editForm($req));
        $router->post('/admin/professions/{id}', fn (Request $req) => $this->update($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('professions/index', ['professions' => $this->professions->all()]);
        return ['rendered' => true];
    }

    private function editForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $profession = $this->professions->find((int) $request->param('id'));

        if ($profession === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('professions/edit', ['profession' => $profession]);
        return ['rendered' => true];
    }

    private function update(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $professionId = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        if ($this->professions->find($professionId) === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $careerInfoUz = trim((string) ($body['career_info_uz'] ?? ''));
        $careerInfoRu = trim((string) ($body['career_info_ru'] ?? ''));

        $this->professions->updateCareerInfo(
            $professionId,
            $careerInfoUz !== '' ? $careerInfoUz : null,
            $careerInfoRu !== '' ? $careerInfoRu : null
        );

        return ['redirect' => '/admin/professions', 'flash' => Lang::t('profession_updated')];
    }
}
