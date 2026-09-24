<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\SiteSettingsController;
use PHPUnit\Framework\TestCase;

final class AdminSiteSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
    }

    public function testEditFormRenders(): void
    {
        $router = new Router();
        (new SiteSettingsController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/settings', [], [], []));
        ob_end_clean();

        $this->assertSame(['rendered' => true], $result);
    }

    public function testUpdateSavesAllFields(): void
    {
        $router = new Router();
        (new SiteSettingsController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/settings', [], [], [
            'csrf_token' => $token,
            'address_uz' => 'Toshkent, Chilonzor',
            'address_ru' => 'Ташкент, Чиланзар',
            'map_embed_url' => 'https://www.google.com/maps/embed?pb=test',
            'telegram' => '@omuquvmarkazi',
            'email' => 'info@example.uz',
            'phone' => '+998901234567',
            'about_uz' => 'Bizning markaz haqida',
            'about_ru' => 'О нашем центре',
            'stat_graduates' => '500',
            'stat_years' => '10',
            'stat_employment_percent' => '85',
        ]));

        $this->assertArrayHasKey('redirect', $result);

        $row = Database::pdo()->query('SELECT * FROM site_settings WHERE id = 1')->fetch();
        $this->assertSame('Toshkent, Chilonzor', $row['address_uz']);
        $this->assertSame('@omuquvmarkazi', $row['telegram']);
        $this->assertSame(500, (int) $row['stat_graduates']);
        $this->assertSame(85, (int) $row['stat_employment_percent']);
    }

    public function testUpdateRejectsMissingCsrfToken(): void
    {
        $router = new Router();
        (new SiteSettingsController())->register($router);

        $result = $router->dispatch(new Request('POST', '/admin/settings', [], [], [
            'address_uz' => 'Should not be saved',
        ]));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }
}
