<?php

declare(strict_types=1);

/**
 * Seeds the public-facing content only: the three professions (if the table is empty),
 * the media gallery (if empty) and the news items (matched by title). Images point at
 * static files under public/images, so they survive redeploys on hosts without a
 * persistent volume. Safe to run more than once. Also required by seed_demo_data.php.
 *
 * Usage: php database/seeders/seed_content.php [.env file]
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Repositories\MediaRepository;
use App\Repositories\NewsRepository;
use App\Repositories\ProfessionRepository;

if (!isset($pdo)) {
    Env::load(dirname(__DIR__, 2), $argv[1] ?? '.env');
    $pdo = Database::pdo();
}
$professions = new ProfessionRepository();
$media = new MediaRepository();
$news = new NewsRepository();

// ---------- Professions ----------
if ((int) $pdo->query('SELECT COUNT(*) FROM professions')->fetchColumn() === 0) {
    $professions->create(
        'Ekskavator mashinisti', 'Машинист экскаватора',
        'Ekskavator boshqarish kasbi.', 'Профессия управления экскаватором.',
        30, 1500000.0, '/images/professions/excavator.jpg',
        "Kursni tugatgach qurilish, konchilik va yo'l qurilishi kompaniyalarida ekskavator mashinisti sifatida ishlashingiz mumkin.",
        'После окончания курса вы сможете работать машинистом экскаватора в строительных, горнодобывающих и дорожно-строительных компаниях.'
    );
    $professions->create(
        "Burg'ilash stanogi mashinisti", 'Машинист бурового станка',
        "Burg'ilash stanogini boshqarish kasbi.", 'Профессия управления буровым станком.',
        30, 1500000.0, '/images/professions/drilling-rig.jpg',
        "Kursni tugatgach kon-metallurgiya va geologik qidiruv tashkilotlarida burg'ilash stanogi mashinisti sifatida ishlashingiz mumkin.",
        'После окончания курса вы сможете работать машинистом бурового станка в горно-металлургических и геологоразведочных организациях.'
    );
    $professions->create(
        'Avtosamosval haydovchisi', 'Водитель автосамосвала',
        'Avtosamosvalni boshqarish kasbi.', 'Профессия управления автосамосвалом.',
        20, 1200000.0, '/images/professions/dump-truck.jpg',
        "Kursni tugatgach karyerlar, qurilish maydonchalari va yuk tashish kompaniyalarida avtosamosval haydovchisi sifatida ishlashingiz mumkin.",
        'После окончания курса вы сможете работать водителем автосамосвала на карьерах, стройплощадках и в транспортных компаниях.'
    );
    echo "Seeded 3 professions.\n";
} else {
    echo "Professions already present, skipping.\n";
}

// ---------- Media + news ----------
if ((int) $pdo->query('SELECT COUNT(*) FROM media_items')->fetchColumn() === 0) {
    $media->createImage('/images/professions/excavator.jpg', 'Ekskavator mashg\'uloti', 'Занятие по экскаватору');
    $media->createImage('/images/professions/drilling-rig.jpg', "Burg'ilash stanogi mashg'uloti", 'Занятие по буровому станку');
    $media->createImage('/images/professions/dump-truck.jpg', 'Avtosamosval mashg\'uloti', 'Занятие по автосамосвалу');
    echo "Media items seeded.\n";
} else {
    echo "Media items already present, skipping.\n";
}

function ensureNews(NewsRepository $news, PDO $pdo, string $titleUz, string $titleRu, string $bodyUz, string $bodyRu, ?string $imageUrl): void
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM news WHERE title_uz = ?');
    $stmt->execute([$titleUz]);
    if ((int) $stmt->fetchColumn() > 0) {
        return;
    }
    $news->create($titleUz, $titleRu, $bodyUz, $bodyRu, $imageUrl);
}

ensureNews(
    $news, $pdo,
    "Yangi o'quv yili boshlandi", 'Начался новый учебный год',
    "O'quv markazimizda yangi guruhlar shakllantirildi va darslar boshlandi.",
    'В нашем учебном центре сформированы новые группы и начались занятия.',
    '/images/professions/excavator.jpg'
);
ensureNews(
    $news, $pdo,
    'Bitiruvchilarga sertifikatlar topshirildi', 'Выпускникам вручены сертификаты',
    "Kursni muvaffaqiyatli tugatgan talabalarga elektron sertifikatlar berildi.",
    'Студентам, успешно завершившим курс, вручены электронные сертификаты.',
    '/images/professions/dump-truck.jpg'
);
ensureNews(
    $news, $pdo,
    "Yangi ekskavator o'quv poligoni ishga tushdi", 'Запущен новый учебный полигон для экскаваторов',
    "Amaliy mashg'ulotlar uchun zamonaviy texnika bilan jihozlangan yangi o'quv poligoni ochildi.",
    'Открыт новый учебный полигон, оснащённый современной техникой для практических занятий.',
    '/images/professions/excavator.jpg'
);
ensureNews(
    $news, $pdo,
    "Burg'ilash yo'nalishiga qabul boshlandi", 'Начался приём на направление бурения',
    "Burg'ilash stanogi mashinisti kasbi bo'yicha yangi guruhga qabul e'lon qilindi. Arizalarni saytimiz orqali qoldirishingiz mumkin.",
    "Объявлен набор в новую группу по профессии машиниста бурового станка. Заявку можно оставить на нашем сайте.",
    '/images/professions/drilling-rig.jpg'
);
ensureNews(
    $news, $pdo,
    "O'qituvchilar malaka oshirish kursidan o'tdi", 'Преподаватели прошли курсы повышения квалификации',
    "Markazimiz o'qituvchilari zamonaviy texnika bo'yicha malaka oshirish kursini muvaffaqiyatli tamomladi.",
    'Преподаватели нашего центра успешно завершили курсы повышения квалификации по современной технике.',
    null
);
echo "News ensured.\n";
