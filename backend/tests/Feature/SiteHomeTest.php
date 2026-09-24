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
