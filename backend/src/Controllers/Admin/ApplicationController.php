<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\ApplicationRepository;

final class ApplicationController
{
    public function __construct(private readonly ApplicationRepository $repository = new ApplicationRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/applications', fn (Request $req) => $this->index($req));
        $router->post('/admin/applications/{id}/approve', fn (Request $req) => $this->approve($req));
        $router->post('/admin/applications/{id}/reject', fn (Request $req) => $this->reject($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $status = $request->formBody()['status'] ?? null;
        $status = in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : null;

        View::render('applications/index', [
            'applications' => $this->repository->allWithProfession($status),
            'statusFilter' => $status,
        ]);

        return ['rendered' => true];
    }

    private function approve(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $id = (int) $request->param('id');
        $password = (string) ($body['password'] ?? '');

        if (strlen($password) < 6) {
            return ['redirect' => '/admin/applications', 'flash' => 'Parol kamida 6 belgidan iborat bo\'lishi kerak'];
        }

        try {
            $this->repository->approve($id, $password);
        } catch (\RuntimeException) {
            return ['redirect' => '/admin/applications', 'flash' => 'Ariza allaqachon ko\'rib chiqilgan'];
        }

        return ['redirect' => '/admin/applications', 'flash' => Lang::t('application_approved')];
    }

    private function reject(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        if (!Csrf::verify($request->formBody()['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $this->repository->reject((int) $request->param('id'));

        return ['redirect' => '/admin/applications', 'flash' => Lang::t('application_rejected')];
    }
}
