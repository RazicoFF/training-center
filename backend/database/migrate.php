<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

$envFile = $argv[1] ?? '.env';
Env::load(dirname(__DIR__), $envFile);

$pdo = Database::pdo();
$dir = __DIR__ . '/migrations';
$files = glob($dir . '/*.sql');
sort($files);

foreach ($files as $file) {
    echo "Running {$file}\n";
    $pdo->exec((string) file_get_contents($file));
}

echo "Migrations complete.\n";
