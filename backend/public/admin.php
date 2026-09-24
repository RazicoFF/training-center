<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\ApplicationController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\CertificateController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\GroupController;
use App\Controllers\Admin\NewsController;
use App\Controllers\Admin\ProfessionController;
use App\Controllers\Admin\QuestionController;
use App\Controllers\Admin\SiteSettingsController;
use App\Controllers\Admin\StudentController;
use App\Controllers\Admin\TeacherController;
use App\Controllers\Admin\TestController;

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

Env::load(dirname(__DIR__));

$router = new Router();
(new AuthController())->register($router);
(new DashboardController())->register($router);
(new ApplicationController())->register($router);
(new GroupController())->register($router);
(new StudentController())->register($router);
(new TeacherController())->register($router);
(new ProfessionController())->register($router);
(new TestController())->register($router);
(new QuestionController())->register($router);
(new CertificateController())->register($router);
(new SiteSettingsController())->register($router);
(new NewsController())->register($router);

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

// $result['rendered'] === true: the handler already echoed HTML via View::render().
