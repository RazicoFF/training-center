<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Router;
use App\Core\Request;
use App\Controllers\Api\ProfessionController;
use PHPUnit\Framework\TestCase;

final class ProfessionsEndpointTest extends TestCase
{
    public function testListsSeededProfessions(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);

        $request = new Request('GET', '/api/v1/professions', [], []);
        $result = $router->dispatch($request);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result['professions']));
        $this->assertArrayHasKey('name_uz', $result['professions'][0]);
        $this->assertArrayHasKey('pdf_url', $result['professions'][0]);
    }

    public function testShowReturnsProfessionWithVideosAndTests(): void
    {
        $professionId = (int) Database::pdo()->query('SELECT id FROM professions LIMIT 1')->fetchColumn();

        $router = new Router();
        (new ProfessionController())->register($router);

        $result = $router->dispatch(new Request('GET', "/api/v1/professions/{$professionId}", [], []));

        $this->assertSame($professionId, (int) $result['id']);
        $this->assertArrayHasKey('videos', $result);
        $this->assertArrayHasKey('tests', $result);
    }

    public function testShowReturns404ForUnknownProfession(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);

        $result = $router->dispatch(new Request('GET', '/api/v1/professions/999999', [], []));

        $this->assertSame(404, $result['status']);
    }
}
