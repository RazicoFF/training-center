<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesMatchingRouteWithParams(): void
    {
        $router = new Router();
        $router->get('/professions/{id}', function (Request $req) {
            return ['id' => $req->param('id')];
        });

        $request = new Request('GET', '/professions/42', [], []);
        $result = $router->dispatch($request);

        $this->assertSame(['id' => '42'], $result);
    }

    public function testReturnsNullWhenNoRouteMatches(): void
    {
        $router = new Router();
        $request = new Request('GET', '/unknown', [], []);

        $this->assertNull($router->dispatch($request));
    }
}
