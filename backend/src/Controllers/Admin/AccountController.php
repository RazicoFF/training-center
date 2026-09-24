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
use App\Repositories\UserRepository;

final class AccountController
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/account', fn (Request $req) => $this->edit($req));
        $router->post('/admin/account/password', fn (Request $req) => $this->changePassword($req));
    }

    private function edit(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('account/edit', []);
        return ['rendered' => true];
    }

    private function changePassword(Request $request): array
    {
        $claims = AdminAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $currentPassword = (string) ($body['current_password'] ?? '');
        $newPassword = (string) ($body['new_password'] ?? '');
        $confirmPassword = (string) ($body['confirm_password'] ?? '');

        $user = $this->users->find($claims['user_id']);

        if ($user === null || !Auth::verifyPassword($currentPassword, $user['password_hash'])) {
            return ['redirect' => '/admin/account', 'flash' => Lang::t('account_current_password_invalid')];
        }

        if (strlen($newPassword) < 6) {
            return ['redirect' => '/admin/account', 'flash' => Lang::t('account_password_too_short')];
        }

        if ($newPassword !== $confirmPassword) {
            return ['redirect' => '/admin/account', 'flash' => Lang::t('account_password_mismatch')];
        }

        $this->users->updatePassword($claims['user_id'], Auth::hashPassword($newPassword));

        return ['redirect' => '/admin/account', 'flash' => Lang::t('account_password_updated')];
    }
}
