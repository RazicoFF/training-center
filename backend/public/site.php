<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Site\AuthController;
use App\Controllers\Site\HomeController;
use App\Controllers\Site\PortalController;
use App\Controllers\Site\TeacherController;

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

Env::load(dirname(__DIR__));

$router = new Router();
(new HomeController())->register($router);
(new AuthController())->register($router);
(new TeacherController())->register($router);
(new PortalController())->register($router);

$request = Request::fromGlobals();

try {
    $result = $router->dispatch($request);
} catch (\Throwable $e) {
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    error_log($e->getMessage() . "\n" . $e->getTraceAsString(), 3, $logDir . '/app.log');
    http_response_code(500);
    echo 'Internal server error';
    return;
}

if ($result === null) {
    http_response_code(404);
    echo '<h1>404 - Sahifa topilmadi</h1>';
    return;
}

if (isset($result['redirect'])) {
    if (isset($result['flash'])) {
        $_SESSION['flash'] = $result['flash'];
    }
    header('Location: ' . $result['redirect']);
    return;
}

if (isset($result['file'])) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($result['file']) . '"');
    readfile($result['file']);
    return;
}

// $result['rendered'] === true: the handler already echoed HTML via SiteView::render().
