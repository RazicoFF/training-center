<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\ApplicationRepository;
use PHPUnit\Framework\TestCase;

final class ApplicationOrderingTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM applications WHERE phone IN ('+998987770090', '+998987770091', '+998987770092')");
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
    }

    public function testPendingApplicationsAlwaysSortBeforeProcessedOnes(): void
    {
        $repo = new ApplicationRepository();
        $pdo = Database::pdo();

        // An old pending application...
        $oldPendingId = $repo->create('Old Pending', '+998987770090', $this->professionId);
        $pdo->prepare('UPDATE applications SET created_at = DATE_SUB(NOW(), INTERVAL 2 DAY) WHERE id = ?')->execute([$oldPendingId]);

        // ...a brand-new approved application...
        $newApprovedId = $repo->create('New Approved', '+998987770091', $this->professionId);
        $pdo->prepare("UPDATE applications SET status = 'approved' WHERE id = ?")->execute([$newApprovedId]);

        // ...and a brand-new pending application.
        $newPendingId = $repo->create('New Pending', '+998987770092', $this->professionId);

        $ids = array_column($repo->allWithProfession(), 'id');
        $oldPendingPos = array_search($oldPendingId, $ids, true);
        $newPendingPos = array_search($newPendingId, $ids, true);
        $newApprovedPos = array_search($newApprovedId, $ids, true);

        // Both pending applications (even the older one) must appear before the
        // already-processed one, regardless of creation date.
        $this->assertLessThan($newApprovedPos, $oldPendingPos);
        $this->assertLessThan($newApprovedPos, $newPendingPos);
        // Among the pending ones, newest still comes first.
        $this->assertLessThan($oldPendingPos, $newPendingPos);
    }
}
