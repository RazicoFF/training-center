<?php

declare(strict_types=1);

namespace Tests\Feature;

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
    }
}
