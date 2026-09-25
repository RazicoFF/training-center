<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\ProfessionController;
use PHPUnit\Framework\TestCase;

final class ApiProfessionBrandsTest extends TestCase
{
    public function testProfessionDetailIncludesItsBrands(): void
    {
        $pdo = Database::pdo();
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->exec("DELETE FROM profession_brands WHERE name = 'API Brand Test'");
        $pdo->prepare('INSERT INTO profession_brands (profession_id, name) VALUES (?, ?)')
            ->execute([$professionId, 'API Brand Test']);

        $router = new Router();
        (new ProfessionController())->register($router);

        $result = $router->dispatch(new Request('GET', "/api/v1/professions/{$professionId}", [], []));

        $this->assertArrayHasKey('brands', $result);
        $names = array_column($result['brands'], 'name');
        $this->assertContains('API Brand Test', $names);
    }
}
