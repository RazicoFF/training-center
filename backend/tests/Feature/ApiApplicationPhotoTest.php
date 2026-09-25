<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\ApplicationController;
use PHPUnit\Framework\TestCase;

final class ApiApplicationPhotoTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->exec("DELETE FROM applications WHERE phone = '+998987770290'");
    }

    public function testDataUriPhotoIsDecodedAndSaved(): void
    {
        // A minimal valid 1x1 PNG, base64-encoded.
        $onePixelPngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $router = new Router();
        (new ApplicationController())->register($router);

        $result = $router->dispatch(new Request('POST', '/api/v1/applications', [], [
            'full_name' => 'Photo Api Applicant',
            'phone' => '+998987770290',
            'profession_id' => $this->professionId,
            'photo_base64' => 'data:image/png;base64,' . $onePixelPngBase64,
        ]));

        $this->assertSame(201, $result['status']);

        $pdo = Database::pdo();
        $photoUrl = $pdo->query("SELECT photo_url FROM applications WHERE id = {$result['id']}")->fetchColumn();

        $this->assertIsString($photoUrl);
        $this->assertStringStartsWith('/uploads/applications/', $photoUrl);

        $absolutePath = dirname(__DIR__, 2) . '/public' . $photoUrl;
        $this->assertFileExists($absolutePath);
        @unlink($absolutePath);
    }

    public function testMissingPhotoStillSucceeds(): void
    {
        $router = new Router();
        (new ApplicationController())->register($router);

        $result = $router->dispatch(new Request('POST', '/api/v1/applications', [], [
            'full_name' => 'No Photo Applicant',
            'phone' => '+998987770290',
            'profession_id' => $this->professionId,
        ]));

        $this->assertSame(201, $result['status']);

        $photoUrl = Database::pdo()->query("SELECT photo_url FROM applications WHERE id = {$result['id']}")->fetchColumn();
        $this->assertNull($photoUrl);
    }
}
