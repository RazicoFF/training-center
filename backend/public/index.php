<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Controllers\Api\ProfessionController;
use App\Controllers\Api\ApplicationController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\MeController;

Env::load(dirname(__DIR__));

$router = new Router();
(new ProfessionController())->register($router);
(new ApplicationController())->register($router);
(new AuthController())->register($router);
(new MeController())->register($router);

$request = Request::fromGlobals();
$result = $router->dispatch($request);

if ($result === null) {
    Response::error('NOT_FOUND', 'Route not found', 404);
    return;
}

if (isset($result['error'])) {
    Response::error($result['error']['code'], $result['error']['message'], $result['status']);
    return;
}

$status = $result['status'] ?? 200;
unset($result['status']);
Response::json($result, $status);
