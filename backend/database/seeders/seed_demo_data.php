<?php

declare(strict_types=1);

/**
 * Populates the site with realistic demo content: professions (if not already seeded),
 * technika brands, teachers with profiles, groups with schedules, students enrolled across
 * those groups, one test per profession with a few questions, some simulated attempt
 * results, and a couple of issued certificates. Safe to run more than once - each section
 * checks for existing data (by name/phone) before inserting.
 *
 * Usage: php database/seeders/seed_demo_data.php [.env file]
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Env;
use App\Repositories\CertificateRepository;
use App\Repositories\GroupRepository;
use App\Repositories\MediaRepository;
use App\Repositories\NewsRepository;
use App\Repositories\ProfessionBrandRepository;
use App\Repositories\ProfessionRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\SiteSettingsRepository;
use App\Repositories\TeacherProfileRepository;
use App\Repositories\TestRepository;
use App\Repositories\UserRepository;
use App\Services\ScheduleGenerator;

$envFile = $argv[1] ?? '.env';
Env::load(dirname(__DIR__, 2), $envFile);

$pdo = Database::pdo();
$users = new UserRepository();
$teacherProfiles = new TeacherProfileRepository();
$professions = new ProfessionRepository();
$brands = new ProfessionBrandRepository();
$groups = new GroupRepository();
$tests = new TestRepository();
$questions = new QuestionRepository();
$certificates = new CertificateRepository();
$media = new MediaRepository();
$news = new NewsRepository();
$siteSettings = new SiteSettingsRepository();
$scheduleGenerator = new ScheduleGenerator();

// ---------- 1. Professions ----------
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

$allProfessions = $professions->all();
$professionByName = [];
foreach ($allProfessions as $p) {
    $professionByName[$p['name_uz']] = (int) $p['id'];
}
$excavatorId = $professionByName['Ekskavator mashinisti'] ?? (int) $allProfessions[0]['id'];
$drillingId = $professionByName["Burg'ilash stanogi mashinisti"] ?? (int) ($allProfessions[1]['id'] ?? $excavatorId);
$dumpTruckId = $professionByName['Avtosamosval haydovchisi'] ?? (int) ($allProfessions[2]['id'] ?? $excavatorId);

// ---------- 2. Brands ----------
function ensureBrand(ProfessionBrandRepository $brands, int $professionId, string $name): int
{
    foreach ($brands->forProfession($professionId) as $b) {
        if ($b['name'] === $name) {
            return (int) $b['id'];
        }
    }
    return $brands->create($professionId, $name);
}

$brandCaterpillar = ensureBrand($brands, $excavatorId, 'Caterpillar');
ensureBrand($brands, $excavatorId, 'Komatsu');
$brandAtlasCopco = ensureBrand($brands, $drillingId, 'Atlas Copco');
ensureBrand($brands, $drillingId, 'Sandvik');
$brandKamaz = ensureBrand($brands, $dumpTruckId, 'KamAZ');
ensureBrand($brands, $dumpTruckId, 'Volvo');
echo "Brands ensured.\n";

// ---------- 3. Teachers ----------
function ensureTeacher(
    UserRepository $users,
    TeacherProfileRepository $profiles,
    string $fullName,
    string $phone,
    array $profileFields
): int {
    $existing = $users->findByPhone($phone);
    if ($existing !== null) {
        return (int) $existing['id'];
    }
    $id = $users->create($fullName, $phone, Auth::hashPassword('teacher123'), 'teacher');
    $profiles->upsert($id, $profileFields + [
        'age' => null, 'birth_date' => null, 'experience_years' => null,
        'skills_uz' => null, 'skills_ru' => null, 'education_uz' => null,
        'education_ru' => null, 'telegram' => null, 'email' => null, 'photo_url' => null,
    ]);
    return $id;
}

$teacherAziz = ensureTeacher($users, $teacherProfiles, 'Aziz Karimov', '+998901112233', [
    'age' => 42, 'birth_date' => '1983-05-12', 'experience_years' => 15,
    'skills_uz' => "Ekskavator boshqarish, texnik xizmat ko'rsatish",
    'skills_ru' => 'Управление экскаватором, техническое обслуживание',
    'education_uz' => 'Toshkent Davlat Texnika Universiteti',
    'education_ru' => 'Ташкентский государственный технический университет',
    'telegram' => '@aziz_karimov', 'email' => 'aziz.karimov@example.uz',
]);
$teacherBahodir = ensureTeacher($users, $teacherProfiles, 'Bahodir Yusupov', '+998902223344', [
    'age' => 48, 'birth_date' => '1977-09-03', 'experience_years' => 18,
    'skills_uz' => "Burg'ilash stanogini boshqarish", 'skills_ru' => 'Управление буровым станком',
    'education_uz' => 'Navoiy Kon-Metallurgiya Instituti', 'education_ru' => 'Навоийский горно-металлургический институт',
    'telegram' => '@bahodir_yusupov', 'email' => 'bahodir.yusupov@example.uz',
]);
$teacherSardor = ensureTeacher($users, $teacherProfiles, 'Sardor Rahimov', '+998903334455', [
    'age' => 36, 'birth_date' => '1989-11-20', 'experience_years' => 10,
    'skills_uz' => 'Avtosamosval boshqarish, xavfsizlik texnikasi',
    'skills_ru' => 'Управление автосамосвалом, техника безопасности',
    'education_uz' => 'Toshkent Avtomobil-Yo\'l Instituti', 'education_ru' => 'Ташкентский автомобильно-дорожный институт',
    'telegram' => '@sardor_rahimov', 'email' => 'sardor.rahimov@example.uz',
]);
$teacherNodira = ensureTeacher($users, $teacherProfiles, 'Nodira Tosheva', '+998904445566', [
    'age' => 33, 'birth_date' => '1993-02-14', 'experience_years' => 7,
    'skills_uz' => 'Ekskavator boshqarish', 'skills_ru' => 'Управление экскаватором',
    'education_uz' => 'Toshkent Davlat Texnika Universiteti', 'education_ru' => 'Ташкентский государственный технический университет',
    'telegram' => '@nodira_tosheva', 'email' => 'nodira.tosheva@example.uz',
]);
echo "Teachers ensured.\n";

// ---------- 4. Groups ----------
function ensureGroup(
    GroupRepository $groups,
    ScheduleGenerator $scheduleGenerator,
    int $professionId,
    int $teacherId,
    string $name,
    string $startDate,
    string $endDate,
    ?int $brandId
): int {
    foreach ($groups->all() as $g) {
        if ($g['name'] === $name) {
            return (int) $g['id'];
        }
    }
    $id = $groups->create($professionId, $teacherId, $name, $startDate, $endDate, $brandId);
    $scheduleGenerator->generateForGroup(
        $id,
        new DateTimeImmutable($startDate),
        new DateTimeImmutable($endDate),
        [
            ['weekday' => 1, 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'room' => '101'],
            ['weekday' => 3, 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'room' => '101'],
        ]
    );
    return $id;
}

$groupEks1 = ensureGroup($groups, $scheduleGenerator, $excavatorId, $teacherAziz, 'EKS-2026-01', '2026-01-12', '2026-02-12', $brandCaterpillar);
$groupBur1 = ensureGroup($groups, $scheduleGenerator, $drillingId, $teacherBahodir, 'BUR-2026-01', '2026-01-12', '2026-02-12', $brandAtlasCopco);
$groupAvt1 = ensureGroup($groups, $scheduleGenerator, $dumpTruckId, $teacherSardor, 'AVT-2026-01', '2026-01-19', '2026-02-09', $brandKamaz);
$groupEks2 = ensureGroup($groups, $scheduleGenerator, $excavatorId, $teacherNodira, 'EKS-2026-02', '2026-02-16', '2026-03-16', $brandCaterpillar);
echo "Groups ensured.\n";

// ---------- 5. Students ----------
function ensureStudent(UserRepository $users, string $fullName, string $phone): int
{
    $existing = $users->findByPhone($phone);
    if ($existing !== null) {
        return (int) $existing['id'];
    }
    return $users->create($fullName, $phone, Auth::hashPassword('student123'), 'student');
}

$studentNames = [
    ['Islom Yoqubov', '+998911112201', $groupEks1],
    ['Dilnoza Ergasheva', '+998911112202', $groupEks1],
    ['Jasur Normatov', '+998911112203', $groupEks1],
    ['Kamola Saidova', '+998911112204', $groupBur1],
    ['Otabek Xolmatov', '+998911112205', $groupBur1],
    ['Madina Abdullayeva', '+998911112206', $groupAvt1],
    ['Farrux Tursunov', '+998911112207', $groupAvt1],
    ['Gulnora Rashidova', '+998911112208', $groupAvt1],
    ['Sherzod Yusupov', '+998911112209', $groupEks2],
    ['Nilufar Karimova', '+998911112210', $groupEks2],
];

$studentIds = [];
foreach ($studentNames as [$name, $phone, $groupId]) {
    $id = ensureStudent($users, $name, $phone);
    $studentIds[] = ['id' => $id, 'group_id' => $groupId, 'name' => $name];
    $alreadyEnrolled = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE user_id = ? AND group_id = ?');
    $alreadyEnrolled->execute([$id, $groupId]);
    if ((int) $alreadyEnrolled->fetchColumn() === 0) {
        $groups->enrollStudent($groupId, $id);
    }
}
echo 'Students ensured: ' . count($studentIds) . "\n";

// ---------- 6. Tests + questions (one per profession) ----------
function ensureTest(TestRepository $tests, QuestionRepository $questions, int $professionId, string $titleUz, string $titleRu, array $questionDefs): int
{
    foreach ($tests->allWithProfession() as $t) {
        if ((int) $t['profession_id'] === $professionId && $t['title_uz'] === $titleUz) {
            return (int) $t['id'];
        }
    }
    $testId = $tests->create($professionId, $titleUz, $titleRu, 70);
    foreach ($questionDefs as [$textUz, $textRu, $answerDefs]) {
        $answers = [];
        foreach ($answerDefs as [$aUz, $aRu, $isCorrect]) {
            $answers[] = ['text_uz' => $aUz, 'text_ru' => $aRu, 'is_correct' => $isCorrect];
        }
        $questions->createWithAnswers($testId, $textUz, $textRu, $answers);
    }
    return $testId;
}

$testEks = ensureTest($tests, $questions, $excavatorId, 'Ekskavator boshqarish asoslari', 'Основы управления экскаватором', [
    ['Ekskavatorning asosiy vazifasi nima?', 'Какова основная функция экскаватора?', [
        ['Tuproq qazish va yuklash', 'Копание и погрузка грунта', true],
        ['Yo\'l tozalash', 'Очистка дороги', false],
        ['Yuk tashish', 'Перевозка груза', false],
    ]],
    ['Ekskavatorda xavfsizlik kamari nima uchun kerak?', 'Зачем нужен ремень безопасности в экскаваторе?', [
        ['Baxtsiz hodisalarning oldini olish uchun', 'Для предотвращения несчастных случаев', true],
        ['Qulaylik uchun', 'Для удобства', false],
    ]],
    ['Ish boshlashdan oldin nimani tekshirish kerak?', 'Что нужно проверить перед началом работы?', [
        ['Gidravlik tizim va moy darajasi', 'Гидравлическую систему и уровень масла', true],
        ['Faqat yoqilg\'ini', 'Только топливо', false],
        ['Hech narsa', 'Ничего', false],
    ]],
    ['Ekskavator qaysi turdagi ishlarda ishlatiladi?', 'Для каких работ используется экскаватор?', [
        ['Qurilish va konchilik ishlarida', 'В строительных и горных работах', true],
        ['Faqat qishloq xo\'jaligida', 'Только в сельском хозяйстве', false],
    ]],
]);

$testBur = ensureTest($tests, $questions, $drillingId, "Burg'ilash ishlari xavfsizligi", 'Безопасность буровых работ', [
    ["Burg'ilash stanogini ishga tushirishdan oldin nima qilinadi?", 'Что делается перед запуском бурового станка?', [
        ['Texnik ko\'rikdan o\'tkaziladi', 'Проводится технический осмотр', true],
        ['Hech narsa qilinmaydi', 'Ничего не делается', false],
    ]],
    ["Burg'ilash jarayonida asosiy xavf nima?", 'Какова основная опасность в процессе бурения?', [
        ['Uskunaning qulashi yoki portlash', 'Обрушение оборудования или взрыв', true],
        ['Shovqin', 'Шум', false],
    ]],
    ['Shaxsiy himoya vositalari nimalardan iborat?', 'Из чего состоят средства индивидуальной защиты?', [
        ['Kaska, ko\'zoynak, qo\'lqop', 'Каска, очки, перчатки', true],
        ['Faqat kaska', 'Только каска', false],
    ]],
]);

$testAvt = ensureTest($tests, $questions, $dumpTruckId, "Avtosamosval haydash qoidalari", 'Правила вождения автосамосвала', [
    ['Yuklangan avtosamosvalni haydashda nimaga e\'tibor berish kerak?', 'На что обращать внимание при вождении загруженного самосвала?', [
        ['Tormoz masofasi ortishiga', 'На увеличение тормозного пути', true],
        ['Hech narsaga', 'Ни на что', false],
    ]],
    ['Karyerda harakatlanishda qanday tezlik tavsiya etiladi?', 'Какая скорость рекомендуется при движении в карьере?', [
        ['Past va nazorat qilinadigan tezlik', 'Низкая и контролируемая скорость', true],
        ['Maksimal tezlik', 'Максимальная скорость', false],
    ]],
    ['Avtosamosvalni yuklashdan oldin nima tekshiriladi?', 'Что проверяется перед погрузкой самосвала?', [
        ['Kuzovning holati va shinalar bosimi', 'Состояние кузова и давление в шинах', true],
        ['Hech narsa', 'Ничего', false],
    ]],
]);
echo "Tests and questions ensured.\n";

// ---------- 7. Simulated results + certificates ----------
function recordAttemptIfMissing(TestRepository $tests, int $userId, int $testId, int $score, bool $passed): void
{
    $pdo = Database::pdo();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM test_attempts WHERE user_id = ? AND test_id = ?');
    $stmt->execute([$userId, $testId]);
    if ((int) $stmt->fetchColumn() > 0) {
        return;
    }
    $tests->recordAttempt($userId, $testId, $score, $passed);
}

$testByGroup = [
    $groupEks1 => $testEks,
    $groupBur1 => $testBur,
    $groupAvt1 => $testAvt,
    $groupEks2 => $testEks,
];
$professionByGroup = [
    $groupEks1 => $excavatorId,
    $groupBur1 => $drillingId,
    $groupAvt1 => $dumpTruckId,
    $groupEks2 => $excavatorId,
];

// Deterministic mix of outcomes: most pass, a couple fail, so the demo shows both states.
$outcomes = [85, 90, 55, 78, 92, 60, 88, 95, 70, 45];
foreach ($studentIds as $i => $student) {
    $testId = $testByGroup[$student['group_id']];
    $score = $outcomes[$i % count($outcomes)];
    $passed = $score >= 70;
    recordAttemptIfMissing($tests, $student['id'], $testId, $score, $passed);

    if ($passed) {
        $professionId = $professionByGroup[$student['group_id']];
        if ($tests->allTestsPassedForProfession($student['id'], $professionId)) {
            $groups->completeEnrollmentsForProfession($student['id'], $professionId);
            $existingCert = $pdo->prepare('SELECT COUNT(*) FROM certificates WHERE user_id = ? AND profession_id = ?');
            $existingCert->execute([$student['id'], $professionId]);
            if ((int) $existingCert->fetchColumn() === 0) {
                $certificates->issue($student['id'], $professionId);
            }
        }
    }
}
echo "Test attempts and certificates ensured.\n";

// ---------- 8. Media + news ----------
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

// ---------- 9. Site settings (contact info, map, about text, stats) ----------
$currentSettings = $siteSettings->get();
if (empty($currentSettings['phone']) && empty($currentSettings['address_uz'])) {
    $siteSettings->update([
        'address_uz' => "Toshkent shahri, Chilonzor tumani, Bunyodkor shoh ko'chasi, 45-uy",
        'address_ru' => 'г. Ташкент, Чиланзарский район, проспект Бунёдкор, дом 45',
        'map_embed_url' => 'https://maps.google.com/maps?q=Tashkent,Uzbekistan&z=14&output=embed',
        'telegram' => '@oquv_markazi',
        'email' => 'info@oquvmarkazi.uz',
        'phone' => '+998 71 200 30 40',
        'about_uz' => "O'quv markazimiz 2015-yildan buyon malakali ishchi kadrlar tayyorlab kelmoqda. "
            . "Zamonaviy texnika va tajribali o'qituvchilar yordamida siz qisqa muddatda talab qilinadigan "
            . "kasb-hunarga ega bo'lasiz.",
        'about_ru' => 'Наш учебный центр с 2015 года готовит квалифицированные рабочие кадры. '
            . 'С помощью современной техники и опытных преподавателей вы за короткий срок получите '
            . 'востребованную профессию.',
        'stat_graduates' => 1240,
        'stat_years' => 10,
        'stat_employment_percent' => 92,
    ]);
    echo "Site settings filled in.\n";
} else {
    echo "Site settings already filled in, skipping.\n";
}

echo "Done.\n";
