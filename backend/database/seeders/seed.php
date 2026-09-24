<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

$envFile = $argv[1] ?? '.env';
Env::load(dirname(__DIR__, 2), $envFile);

$pdo = Database::pdo();

$professions = [
    [
        'Ekskavator mashinisti', 'Машинист экскаватора',
        'Ekskavator boshqarish kasbi.', 'Профессия управления экскаватором.',
        30, 1500000, '/images/professions/excavator.jpg',
        'Kursni tugatgach qurilish, konchilik va yo\'l qurilishi kompaniyalarida ekskavator mashinisti sifatida ishlashingiz mumkin.',
        'После окончания курса вы сможете работать машинистом экскаватора в строительных, горнодобывающих и дорожно-строительных компаниях.',
    ],
    [
        'Burg\'ilash stanogi mashinisti', 'Машинист бурового станка',
        'Burg\'ilash stanogini boshqarish kasbi.', 'Профессия управления буровым станком.',
        30, 1500000, '/images/professions/drilling-rig.jpg',
        'Kursni tugatgach kon-metallurgiya va geologik qidiruv tashkilotlarida burg\'ilash stanogi mashinisti sifatida ishlashingiz mumkin.',
        'После окончания курса вы сможете работать машинистом бурового станка в горно-металлургических и геологоразведочных организациях.',
    ],
    [
        'Avtosamosval haydovchisi', 'Водитель автосамосвала',
        'Avtosamosvalni boshqarish kasbi.', 'Профессия управления автосамосвалом.',
        20, 1200000, '/images/professions/dump-truck.jpg',
        'Kursni tugatgach karyerlar, qurilish maydonchalari va yuk tashish kompaniyalarida avtosamosval haydovchisi sifatida ishlashingiz mumkin.',
        'После окончания курса вы сможете работать водителем автосамосвала на карьерах, стройплощадках и в транспортных компаниях.',
    ],
];

$stmt = $pdo->prepare(
    'INSERT INTO professions (name_uz, name_ru, description_uz, description_ru, duration_days, price, image_url, career_info_uz, career_info_ru)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

foreach ($professions as $p) {
    $stmt->execute($p);
}

echo "Seeded " . count($professions) . " professions.\n";
