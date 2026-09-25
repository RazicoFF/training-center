<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\StudentStatsRepository;

final class DashboardController
{
    public function __construct(
        private readonly StudentStatsRepository $studentStats = new StudentStatsRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin', fn (Request $req) => $this->index($req));
    }

    private function index(Request $request): array
    {
        $claims = AdminAuthMiddleware::authenticate();
        if ($claims === null) {
            return ['redirect' => '/admin/login'];
        }

        // A teacher's home is their own groups, not the center-wide dashboard.
        if ($claims['role'] === 'teacher') {
            return ['redirect' => '/admin/groups'];
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
            'studentStats' => $this->studentStats->summary(),
        ]);

        return ['rendered' => true];
    }
}
