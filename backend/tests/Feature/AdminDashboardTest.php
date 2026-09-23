<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\DashboardController;
use PHPUnit\Framework\TestCase;

final class AdminDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testRedirectsToLoginWhenNotAuthenticated(): void
    {
        $router = new Router();
        (new DashboardController())->register($router);

        $result = $router->dispatch(new Request('GET', '/admin', [], [], []));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }

    public function testRendersCountsWhenAuthenticated(): void
    {
        $_SESSION['admin_user_id'] = 1;
        $_SESSION['admin_role'] = 'admin';

        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM applications WHERE phone = '+998922222222'");
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO applications (full_name, phone, profession_id, status) VALUES (?, ?, ?, "pending")')
            ->execute(['Dash Test', '+998922222222', $professionId]);

        $router = new Router();
        (new DashboardController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString((string) $pdo->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn(), $html);
    }
}
