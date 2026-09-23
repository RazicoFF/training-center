<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;

final class DashboardController
{
    public function register(Router $router): void
    {
        $router->get('/admin', fn (Request $req) => $this->index($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $pdo = Database::pdo();

        $pendingApplications = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
        $activeGroups = (int) $pdo->query('SELECT COUNT(*) FROM `groups` WHERE CURDATE() BETWEEN start_date AND end_date')->fetchColumn();
        $certificatesThisMonth = (int) $pdo->query(
            'SELECT COUNT(*) FROM certificates WHERE MONTH(issue_date) = MONTH(CURDATE()) AND YEAR(issue_date) = YEAR(CURDATE())'
        )->fetchColumn();

        View::render('dashboard', [
            'pendingApplications' => $pendingApplications,
            'activeGroups' => $activeGroups,
            'certificatesThisMonth' => $certificatesThisMonth,
        ]);

        return ['rendered' => true];
    }
}
