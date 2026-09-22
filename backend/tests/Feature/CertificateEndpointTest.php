<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\CertificateController;
use App\Repositories\CertificateRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class CertificateEndpointTest extends TestCase
{
    public function testIssuedCertificateAppearsInListAndDownloads(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM certificates');
        $pdo->exec("DELETE FROM users WHERE phone = '+998900000005'");

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $userId = (new UserRepository())->create('Cert Student', '+998900000005', Auth::hashPassword('pass1234'), 'student');

        $certificate = (new CertificateRepository())->issue($userId, $professionId);
        $this->assertFileExists($certificate['pdf_path']);

        $token = Auth::issueToken($userId, 'student');
        $router = new Router();
        (new CertificateController())->register($router);

        $listRequest = new Request('GET', '/api/v1/me/certificates', ['AUTHORIZATION' => "Bearer {$token}"], []);
        $listResult = $router->dispatch($listRequest);
        $this->assertCount(1, $listResult['certificates']);

        $downloadRequest = new Request('GET', "/api/v1/certificates/{$certificate['id']}/download", ['AUTHORIZATION' => "Bearer {$token}"], []);
        $downloadResult = $router->dispatch($downloadRequest);
        $this->assertFileExists($downloadResult['file_path']);
    }
}
