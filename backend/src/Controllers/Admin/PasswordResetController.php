<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\PasswordResetRequestRepository;
use App\Repositories\UserRepository;

final class PasswordResetController
{
    public function __construct(
        private readonly PasswordResetRequestRepository $requests = new PasswordResetRequestRepository(),
        private readonly UserRepository $users = new UserRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/password-resets', fn (Request $req) => $this->index($req));
        $router->post('/admin/password-resets/{id}/resolve', fn (Request $req) => $this->resolve($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('password_resets/index', ['requests' => $this->requests->allWithUserInfo()]);
        return ['rendered' => true];
    }

    private function resolve(Request $request): array
    {
        if (AdminAuthMiddleware::requireAdmin() === null) {
            return ['redirect' => '/admin/login'];
        }

        $id = (int) $request->param('id');
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $resetRequest = $this->requests->find($id);
        if ($resetRequest === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        $newPassword = (string) ($body['password'] ?? '');
        if (strlen($newPassword) < 6) {
            return ['redirect' => '/admin/password-resets', 'flash' => 'Parol kamida 6 belgidan iborat bo\'lishi kerak', 'flash_type' => 'error'];
        }

        if ($resetRequest['user_id'] !== null) {
            $this->users->updatePassword((int) $resetRequest['user_id'], Auth::hashPassword($newPassword));
        }

        $this->requests->resolve($id);

        return ['redirect' => '/admin/password-resets', 'flash' => Lang::t('password_reset_resolved')];
    }
}
