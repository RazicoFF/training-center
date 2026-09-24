<?php

declare(strict_types=1);

// Dev-server router for `php -S host:port -t public router.php`.
// PHP's built-in server has no path-based routing of its own, and this
// backend serves three front controllers (index.php for /api/v1/..., admin.php
// for /admin/..., site.php for the public site + student portal) from the
// same docroot. A real deployment handles this with Apache/Nginx rewrite
// rules; this script is the local-dev equivalent.

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Let the built-in server serve real static files (css/js/images) directly
// instead of routing them through a front controller.
$staticFile = __DIR__ . '/public' . $path;
if ($path !== '/' && is_file($staticFile)) {
    return false;
}

$sitePaths = ['/', '/login', '/logout', '/lang', '/teachers', '/apply', '/portal'];
$isSitePath = in_array($path, $sitePaths, true)
    || str_starts_with($path, '/teachers/')
    || str_starts_with($path, '/apply/')
    || str_starts_with($path, '/portal/')
    || str_starts_with($path, '/professions/');

if (str_starts_with($path, '/admin')) {
    require __DIR__ . '/public/admin.php';
} elseif ($isSitePath) {
    require __DIR__ . '/public/site.php';
} else {
    require __DIR__ . '/public/index.php';
}
