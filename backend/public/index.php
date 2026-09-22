<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Controllers\Api\ProfessionController;

Env::load(dirname(__DIR__));

$router = new Router();
(new ProfessionController())->register($router);

$request = Request::fromGlobals();
$result = $router->dispatch($request);

if ($result === null) {
    Response::error('NOT_FOUND', 'Route not found', 404);
    return;
}

Response::json($result);
