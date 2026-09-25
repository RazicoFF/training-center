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
use App\Controllers\Api\NewsController;
use App\Controllers\Api\TestController;
use App\Controllers\Api\CertificateController;
use App\Controllers\Api\MediaController;

Env::load(dirname(__DIR__));

try {
    $router = new Router();
    (new ProfessionController())->register($router);
    (new ApplicationController())->register($router);
    (new AuthController())->register($router);
    (new MeController())->register($router);
    (new TestController())->register($router);
    (new CertificateController())->register($router);
    (new NewsController())->register($router);
    (new MediaController())->register($router);

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

    if (isset($result['file_path'])) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($result['file_path']) . '"');
        readfile($result['file_path']);
        return;
    }

    $status = $result['status'] ?? 200;
    unset($result['status']);
    Response::json($result, $status);
} catch (\Throwable $e) {
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    error_log($e->getMessage() . "\n" . $e->getTraceAsString(), 3, $logDir . '/app.log');
    Response::error('SERVER_ERROR', 'Internal server error', 500);
}
