<?php

declare(strict_types=1);

// Serves /uploads/... files whose disk copy is gone (Railway wipes the container disk
// on every deploy) from the uploaded_files table, restoring the disk copy on the way.
// The web server only routes here when the file does not exist on disk.

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Services\UploadStore;

Env::load(dirname(__DIR__));

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

try {
    $file = (new UploadStore())->fetch($path);
} catch (\Throwable $e) {
    error_log('upload.php: ' . $e->getMessage());
    $file = null;
}

if ($file === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    return;
}

// Upload filenames carry a random suffix and are never rewritten, so they can be cached forever.
header('Content-Type: ' . $file['mime']);
header('Content-Length: ' . strlen($file['data']));
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
echo $file['data'];
