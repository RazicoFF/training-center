<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\SiteView;
use App\Middleware\SiteAuthMiddleware;
use App\Repositories\UserRepository;

final class AuthController
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/login', fn (Request $req) => $this->loginForm($req));
        $router->post('/login', fn (Request $req) => $this->login($req));
        $router->post('/logout', fn (Request $req) => $this->logout($req));
        $router->post('/lang', fn (Request $req) => $this->switchLang($req));
    }

    private function loginForm(Request $request): array
    {
        if (SiteAuthMiddleware::authenticate() !== null) {
            return ['redirect' => '/portal'];
        }

        if (($_SESSION['admin_role'] ?? null) === 'teacher') {
            return ['redirect' => '/admin/groups'];
        }

        SiteView::render('site/login', ['error' => null]);
        return ['rendered' => true];
    }

    private function login(Request $request): array
    {
        $body = $request->formBody();

        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/login'];
        }

        $phone = trim((string) ($body['phone'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $user = $this->users->findByPhone($phone);

        if ($user === null
            || !in_array($user['role'], ['student', 'teacher'], true)
            || !Auth::verifyPassword($password, $user['password_hash'])
        ) {
            SiteView::render('site/login', ['error' => Lang::t('login_invalid')]);
            return ['rendered' => true];
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        unset($_SESSION['csrf_token']);

        // A teacher logging in from the public site's login page lands in their scoped
        // admin-panel view (groups/students only) - the admin session keys, not the site
        // ones, are what AdminAuthMiddleware and the admin panel's teacher-scoping check.
        if ($user['role'] === 'teacher') {
            $_SESSION['admin_user_id'] = (int) $user['id'];
            $_SESSION['admin_role'] = $user['role'];

            return ['redirect' => '/admin/groups'];
        }

        $_SESSION['site_user_id'] = (int) $user['id'];
        $_SESSION['site_role'] = $user['role'];

        return ['redirect' => '/portal'];
    }

    private function logout(Request $request): array
    {
        $body = $request->formBody();

        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/login'];
        }

        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return ['redirect' => '/login'];
    }

    private function switchLang(Request $request): array
    {
        $body = $request->formBody();

        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/'];
        }

        Lang::set((string) ($body['locale'] ?? 'uz'));
        $back = (string) ($body['back'] ?? '/');

        if (!str_starts_with($back, '/') || str_starts_with($back, '//')) {
            $back = '/';
        }

        return ['redirect' => $back];
    }
}
