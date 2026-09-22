<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Router;
use App\Core\Request;
use App\Controllers\Api\ApplicationController;
use App\Core\Database;
use PHPUnit\Framework\TestCase;

final class ApplicationsEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        Database::pdo()->exec('DELETE FROM applications');
    }

    public function testCreatesApplicationForExistingProfession(): void
    {
        $professionId = (int) Database::pdo()->query('SELECT id FROM professions LIMIT 1')->fetchColumn();

        $router = new Router();
        (new ApplicationController())->register($router);

        $request = new Request('POST', '/api/v1/applications', [], [
            'full_name' => 'Aziz Karimov',
            'phone' => '+998901112233',
            'profession_id' => $professionId,
        ]);

        $result = $router->dispatch($request);

        $this->assertArrayHasKey('id', $result);

        $row = Database::pdo()->query('SELECT status FROM applications WHERE id = ' . (int) $result['id'])->fetch();
        $this->assertSame('pending', $row['status']);
    }

    public function testRejectsMissingFields(): void
    {
        $router = new Router();
        (new ApplicationController())->register($router);

        $request = new Request('POST', '/api/v1/applications', [], ['full_name' => 'No Phone']);
        $result = $router->dispatch($request);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame(422, $result['status']);
    }
}
