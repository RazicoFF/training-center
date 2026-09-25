<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\ApplicationController;
use PHPUnit\Framework\TestCase;

final class SiteApplyPhotoTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = [];
        $pdo = Database::pdo();
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->exec("DELETE FROM applications WHERE phone = '+998987770260'");
        $pdo->exec("DELETE FROM users WHERE phone = '+998987770260'");
    }

    protected function tearDown(): void
    {
        $_FILES = [];
    }

    public function testApplicationPhotoCarriesOverToTheApprovedUser(): void
    {
        // Simulate an already-uploaded photo directly on the application row (the upload
        // mechanics themselves - move_uploaded_file() - aren't exercised by this test, only
        // the copy-on-approval behavior).
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO applications (full_name, phone, profession_id, photo_url, status) VALUES (?, ?, ?, ?, "pending")')
            ->execute(['Photo Applicant', '+998987770260', $this->professionId, '/uploads/applications/sample.jpg']);
        $applicationId = (int) $pdo->lastInsertId();

        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $router = new Router();
        (new ApplicationController())->register($router);
        $token = Csrf::token();

        $router->dispatch(new Request('POST', "/admin/applications/{$applicationId}/approve", [], [], [
            'csrf_token' => $token,
            'password' => 'temppass1',
        ]));

        $userId = (int) $pdo->query("SELECT created_user_id FROM applications WHERE id = {$applicationId}")->fetchColumn();
        $this->assertGreaterThan(0, $userId);

        $photoUrl = $pdo->query("SELECT photo_url FROM users WHERE id = {$userId}")->fetchColumn();
        $this->assertSame('/uploads/applications/sample.jpg', $photoUrl);
    }
}
