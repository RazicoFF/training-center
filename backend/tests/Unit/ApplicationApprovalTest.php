<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\ApplicationRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class ApplicationApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM applications WHERE phone = '+998900000099'");
        $pdo->exec("DELETE FROM users WHERE phone = '+998900000099'");
    }

    public function testApprovingPendingApplicationCreatesUserAndLinksIt(): void
    {
        $pdo = Database::pdo();
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO applications (full_name, phone, profession_id) VALUES (?, ?, ?)');
        $stmt->execute(['Approval Test', '+998900000099', $professionId]);
        $applicationId = (int) $pdo->lastInsertId();

        $repo = new ApplicationRepository();
        $result = $repo->approve($applicationId, 'temp12345');

        $this->assertArrayHasKey('user_id', $result);
        $this->assertSame('+998900000099', $result['phone']);

        $user = (new UserRepository())->findByPhone('+998900000099');
        $this->assertNotNull($user);
        $this->assertSame($result['user_id'], (int) $user['id']);

        // Approving deletes the application record entirely - it's now a real student account.
        $this->assertNull($repo->find($applicationId));
    }

    public function testApprovingAlreadyApprovedApplicationThrows(): void
    {
        $pdo = Database::pdo();
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO applications (full_name, phone, profession_id) VALUES (?, ?, ?)');
        $stmt->execute(['Approval Test 2', '+998900000099', $professionId]);
        $applicationId = (int) $pdo->lastInsertId();

        $repo = new ApplicationRepository();
        $repo->approve($applicationId, 'temp12345');

        $this->expectException(\RuntimeException::class);
        $repo->approve($applicationId, 'temp12345');
    }

    public function testApprovingMissingApplicationThrows(): void
    {
        $repo = new ApplicationRepository();

        $this->expectException(\RuntimeException::class);
        $repo->approve(999999, 'temp12345');
    }
}
