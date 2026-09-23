<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\CertificateController;
use App\Repositories\CertificateRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminCertificatesTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
    }

    public function testListAndDownloadCertificate(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM certificates');
        $pdo->exec("DELETE FROM users WHERE phone = '+998966666666'");
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $userId = (new UserRepository())->create('Cert Admin Test', '+998966666666', Auth::hashPassword('x'), 'student');
        $certificate = (new CertificateRepository())->issue($userId, $professionId);

        $router = new Router();
        (new CertificateController())->register($router);

        ob_start();
        $listResult = $router->dispatch(new Request('GET', '/admin/certificates', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $listResult);
        $this->assertStringContainsString('Cert Admin Test', $html);

        $downloadResult = $router->dispatch(new Request('GET', "/admin/certificates/{$certificate['id']}/download", [], [], []));

        $this->assertArrayHasKey('file', $downloadResult);
        $this->assertFileExists($downloadResult['file']);
    }
}
