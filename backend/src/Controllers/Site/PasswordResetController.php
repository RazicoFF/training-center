<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\SiteView;
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
        $router->get('/forgot-password', fn (Request $req) => $this->form($req));
        $router->post('/forgot-password', fn (Request $req) => $this->submit($req));
    }

    private function form(Request $request): array
    {
        SiteView::render('site/forgot_password', ['submitted' => false]);
        return ['rendered' => true];
    }

    private function submit(Request $request): array
    {
        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/forgot-password'];
        }

        $phone = trim((string) ($body['phone'] ?? ''));

        if ($phone !== '') {
            // A student/teacher account matching this phone creates a real request the
            // admin can act on; a phone that matches nothing (or an admin's own phone -
            // admins manage their own passwords via the Adminlar section) still shows the
            // same confirmation, so this endpoint never reveals which phone numbers exist.
            $user = $this->users->findByPhone($phone);
            if ($user !== null && in_array($user['role'], ['student', 'teacher'], true)) {
                $this->requests->create($phone, (int) $user['id']);
            }
        }

        SiteView::render('site/forgot_password', ['submitted' => true]);
        return ['rendered' => true];
    }
}
