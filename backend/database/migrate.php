<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

$envFile = $argv[1] ?? '.env';
Env::load(dirname(__DIR__), $envFile);

$pdo = Database::pdo();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        filename VARCHAR(255) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$applied = $pdo->query('SELECT filename FROM schema_migrations')->fetchAll(\PDO::FETCH_COLUMN);

$dir = __DIR__ . '/migrations';
$files = glob($dir . '/*.sql');
sort($files);

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }

    echo "Running {$file}\n";
    $pdo->exec((string) file_get_contents($file));

    $stmt = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
    $stmt->execute([$name]);
    $ran++;
}

echo $ran === 0 ? "Nothing to migrate.\n" : "Migrations complete ({$ran} applied).\n";
