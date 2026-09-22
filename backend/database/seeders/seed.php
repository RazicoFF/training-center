<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

$envFile = $argv[1] ?? '.env';
Env::load(dirname(__DIR__, 2), $envFile);

$pdo = Database::pdo();

$professions = [
    ['Ekskavator mashinisti', 'Машинист экскаватора', 'Ekskavator boshqarish kasbi.', 'Профессия управления экскаватором.', 30, 1500000],
    ['Burg\'ilash stanogi mashinisti', 'Машинист бурового станка', 'Burg\'ilash stanogini boshqarish kasbi.', 'Профессия управления буровым станком.', 30, 1500000],
    ['Avtosamosval haydovchisi', 'Водитель автосамосвала', 'Avtosamosvalni boshqarish kasbi.', 'Профессия управления автосамосвалом.', 20, 1200000],
];

$stmt = $pdo->prepare(
    'INSERT INTO professions (name_uz, name_ru, description_uz, description_ru, duration_days, price)
     VALUES (?, ?, ?, ?, ?, ?)'
);

foreach ($professions as $p) {
    $stmt->execute($p);
}

echo "Seeded " . count($professions) . " professions.\n";
