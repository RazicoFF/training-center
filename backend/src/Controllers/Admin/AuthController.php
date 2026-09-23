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

final class AuthController
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/login', fn (Request $req) => $this->loginForm($req));
        $router->post('/admin/login', fn (Request $req) => $this->login($req));
        $router->post('/admin/logout', fn (Request $req) => $this->logout($req));
        $router->post('/admin/lang', fn (Request $req) => $this->switchLang($req));
    }

    private function loginForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() !== null) {
            return ['redirect' => '/admin'];
        }

        View::render('login', ['error' => null]);
        return ['rendered' => true];
    }

    private function login(Request $request): array
    {
        $body = $request->formBody();

        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $phone = trim((string) ($body['phone'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $user = $this->users->findByPhone($phone);

        if ($user === null
            || !in_array($user['role'], ['admin', 'teacher'], true)
            || !Auth::verifyPassword($password, $user['password_hash'])
        ) {
            View::render('login', ['error' => Lang::t('login_invalid')]);
            return ['rendered' => true];
        }

        $_SESSION['admin_user_id'] = (int) $user['id'];
        $_SESSION['admin_role'] = $user['role'];

        return ['redirect' => '/admin'];
    }

    private function logout(Request $request): array
    {
        $body = $request->formBody();

        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        unset($_SESSION['admin_user_id'], $_SESSION['admin_role']);
        return ['redirect' => '/admin/login'];
    }

    private function switchLang(Request $request): array
    {
        $body = $request->formBody();

        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        Lang::set((string) ($body['locale'] ?? 'uz'));
        $back = (string) ($body['back'] ?? '/admin');

        if (!str_starts_with($back, '/') || str_starts_with($back, '//')) {
            $back = '/admin';
        }

        return ['redirect' => $back];
    }
}
