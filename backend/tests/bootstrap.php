<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(dirname(__DIR__), '.env.testing');

// Reset the test DB schema once per suite run so that two consecutive full-suite runs both pass,
// even if a previous run crashed mid-test and left rows behind (e.g. a test_attempts row
// referencing a tests.id that a later run's fixtures try to delete).
$pdo = Database::pdo();

$tablesToClear = [
    'certificates',
    'test_attempts',
    'answers',
    'questions',
    'tests',
    'enrollments',
    'schedule',
    'groups',
    'applications',
    'teacher_profiles',
    'profession_videos',
    'news',
    'users',
];

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($tablesToClear as $table) {
    $pdo->exec("DELETE FROM `{$table}`");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

// professions is intentionally left untouched above; re-seed only if empty (e.g. a fresh DB).
$professionCount = (int) $pdo->query('SELECT COUNT(*) FROM professions')->fetchColumn();
if ($professionCount === 0) {
    $professions = [
        ['Ekskavator mashinisti', 'Машинист экскаватора', "Ekskavator boshqarish kasbi.", 'Профессия управления экскаватором.', 30, 1500000],
        ["Burg'ilash stanogi mashinisti", 'Машинист бурового станка', "Burg'ilash stanogini boshqarish kasbi.", 'Профессия управления буровым станком.', 30, 1500000],
        ['Avtosamosval haydovchisi', 'Водитель автосамосвала', 'Avtosamosvalni boshqarish kasbi.', 'Профессия управления автосамосвалом.', 20, 1200000],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO professions (name_uz, name_ru, description_uz, description_ru, duration_days, price)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    foreach ($professions as $p) {
        $stmt->execute($p);
    }
}

// site_settings is a singleton row (id=1); ensure it exists without wiping any values
// admin-panel/site tests may have already left there.
$settingsCount = (int) $pdo->query('SELECT COUNT(*) FROM site_settings WHERE id = 1')->fetchColumn();
if ($settingsCount === 0) {
    $pdo->exec('INSERT INTO site_settings (id) VALUES (1)');
}
