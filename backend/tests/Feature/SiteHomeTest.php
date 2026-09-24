<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Site\HomeController;
use PHPUnit\Framework\TestCase;

final class SiteHomeTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        Database::pdo()->exec("DELETE FROM applications WHERE phone = '+998987770030'");
    }

    public function testHomeRendersProfessionPriceAndCareerInfo(): void
    {
        $router = new Router();
        (new HomeController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('Ekskavator', $html);
        $this->assertStringContainsString('1,500,000', $html);
    }

    public function testHomeRendersAboutAddressContactsAndStatsWhenSet(): void
    {
        Database::pdo()->exec(
            "UPDATE site_settings SET
                address_uz = 'Toshkent shahri, Chilonzor tumani',
                map_embed_url = 'https://www.google.com/maps/embed?pb=test',
                telegram = '@omuquvmarkazi',
                email = 'info@example.uz',
                phone = '+998901234567',
                about_uz = 'Bizning oquv markazimiz haqida matn',
                stat_graduates = 500,
                stat_years = 10,
                stat_employment_percent = 85
             WHERE id = 1"
        );

        $router = new Router();
        (new HomeController())->register($router);

        ob_start();
        $router->dispatch(new Request('GET', '/', [], [], []));
        $html = ob_get_clean();

        $this->assertStringContainsString('Bizning oquv markazimiz haqida matn', $html);
        $this->assertStringContainsString('Toshkent shahri, Chilonzor tumani', $html);
        $this->assertStringContainsString('maps/embed?pb=test', $html);
        $this->assertStringContainsString('@omuquvmarkazi', $html);
        $this->assertStringContainsString('info@example.uz', $html);
        $this->assertStringContainsString('data-counter-target="500"', $html);
        $this->assertStringContainsString('data-counter-suffix="+"', $html);
        $this->assertStringContainsString('data-counter-target="85"', $html);
        $this->assertStringContainsString('data-counter-suffix="%"', $html);

        Database::pdo()->exec(
            "UPDATE site_settings SET address_uz = NULL, map_embed_url = NULL, telegram = NULL, email = NULL,
             phone = NULL, about_uz = NULL, stat_graduates = NULL, stat_years = NULL, stat_employment_percent = NULL
             WHERE id = 1"
        );
    }

    public function testHomeRendersNewsAndProfessionLinksToDetailPage(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM news WHERE title_uz = 'Smoke Home News'");
        $pdo->prepare('INSERT INTO news (title_uz, title_ru, body_uz) VALUES (?, ?, ?)')
            ->execute(['Smoke Home News', 'Тестовая новость', 'Yangilik matni']);
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();

        $router = new Router();
        (new HomeController())->register($router);

        ob_start();
        $router->dispatch(new Request('GET', '/', [], [], []));
        $html = ob_get_clean();

        $this->assertStringContainsString('Smoke Home News', $html);
        $this->assertStringContainsString("/professions/{$professionId}", $html);

        $pdo->exec("DELETE FROM news WHERE title_uz = 'Smoke Home News'");
    }

    public function testApplySubmitsApplicationAndShowsSuccessMessage(): void
    {
        $professionId = (int) Database::pdo()->query('SELECT id FROM professions LIMIT 1')->fetchColumn();

        $router = new Router();
        (new HomeController())->register($router);
        $token = Csrf::token();

        ob_start();
        $result = $router->dispatch(new Request('POST', '/apply', [], [], [
            'csrf_token' => $token,
            'full_name' => 'Site Applicant',
            'phone' => '+998987770030',
            'profession_id' => (string) $professionId,
        ]));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('qabul qilindi', $html);

        $count = Database::pdo()->query("SELECT COUNT(*) FROM applications WHERE phone = '+998987770030'")->fetchColumn();
        $this->assertSame('1', (string) $count);
    }

    public function testApplyFormShowsBrandsAsJsonAndSubmitStoresSelectedBrand(): void
    {
        $pdo = Database::pdo();
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->exec("DELETE FROM profession_brands WHERE profession_id = {$professionId} AND name = 'Caterpillar Test Brand'");
        $pdo->prepare('INSERT INTO profession_brands (profession_id, name) VALUES (?, ?)')->execute([$professionId, 'Caterpillar Test Brand']);
        $brandId = (int) $pdo->lastInsertId();

        $router = new Router();
        (new HomeController())->register($router);

        ob_start();
        $router->dispatch(new Request('GET', '/apply', [], [], []));
        $formHtml = ob_get_clean();
        $this->assertStringContainsString('Caterpillar Test Brand', $formHtml);

        $token = Csrf::token();
        ob_start();
        $router->dispatch(new Request('POST', '/apply', [], [], [
            'csrf_token' => $token,
            'full_name' => 'Brand Applicant',
            'phone' => '+998987770031',
            'profession_id' => (string) $professionId,
            'brand_id' => (string) $brandId,
        ]));
        ob_end_clean();

        $storedBrandId = $pdo->query("SELECT brand_id FROM applications WHERE phone = '+998987770031'")->fetchColumn();
        $this->assertSame($brandId, (int) $storedBrandId);

        $pdo->exec("DELETE FROM applications WHERE phone = '+998987770031'");
        $pdo->exec("DELETE FROM profession_brands WHERE id = {$brandId}");
    }

    public function testApplyRejectsMissingCsrfToken(): void
    {
        $router = new Router();
        (new HomeController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('POST', '/apply', [], [], [
            'full_name' => 'No Csrf',
            'phone' => '+998987770030',
            'profession_id' => '1',
        ]));
        ob_end_clean();

        $this->assertSame(['redirect' => '/apply'], $result);
        $count = Database::pdo()->query("SELECT COUNT(*) FROM applications WHERE phone = '+998987770030'")->fetchColumn();
        $this->assertSame('0', (string) $count);
    }
}
