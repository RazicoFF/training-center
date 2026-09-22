# Web Admin Panel Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a server-rendered PHP admin panel (`backend/public/admin.php`, session-authenticated) on top of the existing backend, so staff can review applications, manage groups/schedules/teachers/tests/questions, and view issued certificates.

**Architecture:** A second front controller (`public/admin.php`) reuses the existing `App\Core\Router`/`App\Core\Request` and the Repositories from sub-project 1, but controllers render HTML via a small `View` class instead of returning JSON. Every admin handler returns an array with exactly one of `rendered` (HTML already echoed), `redirect` (+ optional `flash`), or `file` (stream a PDF) — this mirrors the `status`/`error`/`file_path` convention `public/index.php` already uses, so `Router::dispatch()` returning `null` unambiguously still means "no route matched."

**Tech Stack:** Same as backend (PHP 8.1+, PDO/MySQL, PHPUnit) plus PHP native sessions and Bootstrap 5 via CDN (no new Composer dependencies).

**Spec:** `docs/superpowers/specs/2026-09-23-admin-panel-design.md` (and `docs/superpowers/specs/2026-09-22-backend-db-design.md` for the DB schema and error-handling conventions this plan builds on).

## Global Constraints

- PHP >= 8.1, strict_types declared in every file (same as backend).
- All DB access through PDO with prepared statements — no string-concatenated SQL.
- Every admin route handler returns an array with exactly one of: `['rendered' => true]`, `['redirect' => string, 'flash'? => string]`, `['file' => string]`.
- Every protected handler starts by calling `AdminAuthMiddleware::authenticate(): ?array`; on `null`, return `['redirect' => '/admin/login']` immediately — no exceptions to this pattern.
- Every state-changing (`POST`) handler verifies `Csrf::verify($request->formBody()['csrf_token'] ?? null)` before doing anything else; on failure, return `['redirect' => '/admin/login']`.
- Passwords hashed with `App\Core\Auth::hashPassword()` (already exists), never logged or stored in session.
- Session cookies: `httponly`, `samesite=Lax` (set at `session_start()` in `public/admin.php`).
- Tests never call `session_start()` — they set `$_SESSION` directly as a plain array (`$_SESSION = ['admin_user_id' => ..., 'admin_role' => ...];`) and reset it to `[]` in `setUp()`.
- Route registration order matters wherever a literal path (`/admin/groups/create`) and a `{id}` pattern (`/admin/groups/{id}`) could both match the same segment — the literal route MUST be registered first, or the `{id}` pattern will swallow it (see Task 7).
- New/changed Repository methods and Controllers follow the existing codebase's exact patterns: `register(Router $router): void` on every controller, constructor-injected repositories with `= new XRepository()` defaults, prepared statements everywhere.

---

## File Structure

```
backend/
  public/
    admin.php                          # new entry point
  src/
    Core/
      Request.php                      # MODIFY: add formBody()
      Csrf.php                         # new
      Lang.php                         # new
      View.php                         # new
    Middleware/
      AdminAuthMiddleware.php          # new
    Controllers/
      Admin/
        AuthController.php
        DashboardController.php
        ApplicationController.php
        GroupController.php
        TeacherController.php
        TestController.php
        QuestionController.php
        CertificateController.php
    Controllers/Api/
      TestController.php                # MODIFY: auto-issue certificate on pass
    Repositories/
      ApplicationRepository.php         # MODIFY: add allWithProfession(), reject()
      GroupRepository.php               # new
      TeacherRepository.php             # new
      QuestionRepository.php            # new
      TestRepository.php                # MODIFY: add create(), allWithProfession()
      CertificateRepository.php         # MODIFY: add allWithDetails()
    Services/
      ScheduleGenerator.php             # new
  resources/
    lang/
      uz.php
      ru.php
    views/
      layout.php
      login.php
      dashboard.php
      applications/index.php
      groups/index.php
      groups/create.php
      groups/show.php
      teachers/index.php
      teachers/create.php
      tests/index.php
      tests/create.php
      tests/questions.php
      certificates/index.php
  database/
    seeders/
      seed_admin.php                   # new
  tests/
    Unit/
      CsrfTest.php
      LangTest.php
      AdminAuthMiddlewareTest.php
      ScheduleGeneratorTest.php
    Feature/
      AdminAuthEndpointTest.php
      AdminDashboardTest.php
      AdminApplicationsTest.php
      AdminGroupsTest.php
      AdminTeachersTest.php
      AdminTestsTest.php
      AdminCertificatesTest.php
      AutoCertificateOnPassTest.php
```

**Interfaces contract (used across tasks):**
- `Request::formBody(): array` — `$_POST` data (separate from `jsonBody()`, which stays JSON-only).
- `Csrf::token(): string`, `Csrf::verify(?string $submitted): bool`
- `Lang::set(string $locale): void`, `Lang::current(): string`, `Lang::t(string $key): string`
- `View::render(string $template, array $data = []): void` — echoes full HTML (template wrapped in `layout.php`).
- `AdminAuthMiddleware::authenticate(): ?array` — reads `$_SESSION`, returns `['user_id' => int, 'role' => string]` or `null`. No side effects, no redirect — callers decide.
- Every Admin controller: `register(Router $router): void`, private handler methods returning `array{rendered?:true, redirect?:string, flash?:string, file?:string}`.

---

### Task 1: `Request::formBody()` + `Csrf` + `Lang` + language files

**Files:**
- Modify: `backend/src/Core/Request.php`
- Create: `backend/src/Core/Csrf.php`
- Create: `backend/src/Core/Lang.php`
- Create: `backend/resources/lang/uz.php`
- Create: `backend/resources/lang/ru.php`
- Test: `backend/tests/Unit/CsrfTest.php`
- Test: `backend/tests/Unit/LangTest.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: `Request::formBody(): array`; `Csrf::token()/verify()`; `Lang::set()/current()/t()`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Unit/CsrfTest.php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsStableAcrossCallsAndVerifies(): void
    {
        $token = Csrf::token();

        $this->assertSame($token, Csrf::token());
        $this->assertTrue(Csrf::verify($token));
    }

    public function testVerifyRejectsWrongOrMissingToken(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::verify('wrong-token'));
        $this->assertFalse(Csrf::verify(null));
    }
}
```

```php
<?php
// tests/Unit/LangTest.php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Lang;
use PHPUnit\Framework\TestCase;

final class LangTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testDefaultsToUzAndTranslates(): void
    {
        $this->assertSame('uz', Lang::current());
        $this->assertSame('Boshqaruv paneli', Lang::t('nav_dashboard'));
    }

    public function testSetSwitchesLocale(): void
    {
        Lang::set('ru');

        $this->assertSame('ru', Lang::current());
        $this->assertSame('Панель управления', Lang::t('nav_dashboard'));
    }

    public function testSetIgnoresInvalidLocale(): void
    {
        Lang::set('ru');
        Lang::set('fr');

        $this->assertSame('ru', Lang::current());
    }

    public function testUnknownKeyReturnsTheKeyItself(): void
    {
        $this->assertSame('no_such_key', Lang::t('no_such_key'));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Unit/CsrfTest.php tests/Unit/LangTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Extend `Request` with `formBody()`**

Read the current `backend/src/Core/Request.php` first (from sub-project 1). Add a new constructor parameter and method — keep every existing parameter, position, and method unchanged so no existing call site breaks:

```php
    /**
     * @param array<string,string> $headers
     * @param array<string,mixed> $body
     * @param array<string,mixed> $formBody
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $headers = [],
        private readonly array $body = [],
        private readonly array $formBody = []
    ) {
    }
```

Add to `fromGlobals()`, right before `return new self(...)`:

```php
        $formBody = $_POST;
```

And change the final `return` in `fromGlobals()` to pass it through:

```php
        return new self($method, $path, $headers, $body, $formBody);
```

Add the accessor method (next to `jsonBody()`):

```php
    public function formBody(): array
    {
        return $this->formBody;
    }
```

- [ ] **Step 4: Implement `Csrf`**

```php
<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verify(?string $submitted): bool
    {
        return is_string($submitted)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $submitted);
    }
}
```

- [ ] **Step 5: Implement `Lang`**

```php
<?php

declare(strict_types=1);

namespace App\Core;

final class Lang
{
    private static ?array $strings = null;
    private static ?string $loadedLocale = null;

    public static function set(string $locale): void
    {
        if (!in_array($locale, ['uz', 'ru'], true)) {
            return;
        }

        $_SESSION['admin_lang'] = $locale;
        self::$strings = null;
    }

    public static function current(): string
    {
        return $_SESSION['admin_lang'] ?? 'uz';
    }

    public static function t(string $key): string
    {
        $locale = self::current();

        if (self::$strings === null || self::$loadedLocale !== $locale) {
            self::$strings = require dirname(__DIR__, 2) . '/resources/lang/' . $locale . '.php';
            self::$loadedLocale = $locale;
        }

        return self::$strings[$key] ?? $key;
    }
}
```

- [ ] **Step 6: Create `resources/lang/uz.php`**

```php
<?php

declare(strict_types=1);

return [
    'app_title' => 'Учебный центр — Boshqaruv paneli',
    'nav_dashboard' => 'Boshqaruv paneli',
    'nav_applications' => 'Arizalar',
    'nav_groups' => 'Guruhlar',
    'nav_teachers' => 'O\'qituvchilar',
    'nav_tests' => 'Testlar',
    'nav_certificates' => 'Sertifikatlar',
    'logout' => 'Chiqish',
    'login_title' => 'Tizimga kirish',
    'login_phone' => 'Telefon raqam',
    'login_password' => 'Parol',
    'login_submit' => 'Kirish',
    'login_invalid' => 'Telefon raqam yoki parol noto\'g\'ri',
    'theme_toggle' => 'Mavzu',
    'lang_uz' => 'O\'zbekcha',
    'lang_ru' => 'Русский',
    'status_pending' => 'Kutilmoqda',
    'status_approved' => 'Tasdiqlangan',
    'status_rejected' => 'Rad etilgan',
    'approve' => 'Tasdiqlash',
    'reject' => 'Rad etish',
    'application_approved' => 'Ariza tasdiqlandi',
    'application_rejected' => 'Ariza rad etildi',
    'group_create' => 'Yangi guruh',
    'group_name' => 'Guruh nomi',
    'group_profession' => 'Kasb',
    'group_teacher' => 'O\'qituvchi',
    'group_start_date' => 'Boshlanish sanasi',
    'group_end_date' => 'Tugash sanasi',
    'group_weekdays' => 'Hafta kunlari',
    'group_time_start' => 'Boshlanish vaqti',
    'group_time_end' => 'Tugash vaqti',
    'group_room' => 'Xona',
    'group_created' => 'Guruh yaratildi',
    'group_enroll' => 'Guruhga qo\'shish',
    'group_enrolled' => 'Talaba guruhga qo\'shildi',
    'weekday_1' => 'Dushanba',
    'weekday_2' => 'Seshanba',
    'weekday_3' => 'Chorshanba',
    'weekday_4' => 'Payshanba',
    'weekday_5' => 'Juma',
    'weekday_6' => 'Shanba',
    'weekday_7' => 'Yakshanba',
    'teacher_create' => 'Yangi o\'qituvchi',
    'teacher_name' => 'F.I.Sh',
    'teacher_phone' => 'Telefon raqam',
    'teacher_password' => 'Parol',
    'teacher_created' => 'O\'qituvchi qo\'shildi',
    'test_create' => 'Yangi test',
    'test_title_uz' => 'Sarlavha (uz)',
    'test_title_ru' => 'Sarlavha (ru)',
    'test_passing_score' => 'O\'tish balli (%)',
    'test_created' => 'Test yaratildi',
    'question_add' => 'Savol qo\'shish',
    'question_text_uz' => 'Savol matni (uz)',
    'question_text_ru' => 'Savol matni (ru)',
    'answer_text_uz' => 'Javob matni (uz)',
    'answer_text_ru' => 'Javob matni (ru)',
    'answer_correct' => 'To\'g\'ri javob',
    'question_added' => 'Savol qo\'shildi',
    'certificates_title' => 'Berilgan sertifikatlar',
    'certificate_number' => 'Sertifikat raqami',
    'certificate_download' => 'Yuklab olish',
    'dashboard_pending_applications' => 'Kutilayotgan arizalar',
    'dashboard_active_groups' => 'Faol guruhlar',
    'dashboard_certificates_month' => 'Shu oy berilgan sertifikatlar',
];
```

- [ ] **Step 7: Create `resources/lang/ru.php`**

```php
<?php

declare(strict_types=1);

return [
    'app_title' => 'Учебный центр — Панель управления',
    'nav_dashboard' => 'Панель управления',
    'nav_applications' => 'Заявки',
    'nav_groups' => 'Группы',
    'nav_teachers' => 'Преподаватели',
    'nav_tests' => 'Тесты',
    'nav_certificates' => 'Сертификаты',
    'logout' => 'Выход',
    'login_title' => 'Вход в систему',
    'login_phone' => 'Номер телефона',
    'login_password' => 'Пароль',
    'login_submit' => 'Войти',
    'login_invalid' => 'Неверный номер телефона или пароль',
    'theme_toggle' => 'Тема',
    'lang_uz' => 'O\'zbekcha',
    'lang_ru' => 'Русский',
    'status_pending' => 'В ожидании',
    'status_approved' => 'Подтверждена',
    'status_rejected' => 'Отклонена',
    'approve' => 'Подтвердить',
    'reject' => 'Отклонить',
    'application_approved' => 'Заявка подтверждена',
    'application_rejected' => 'Заявка отклонена',
    'group_create' => 'Новая группа',
    'group_name' => 'Название группы',
    'group_profession' => 'Профессия',
    'group_teacher' => 'Преподаватель',
    'group_start_date' => 'Дата начала',
    'group_end_date' => 'Дата окончания',
    'group_weekdays' => 'Дни недели',
    'group_time_start' => 'Время начала',
    'group_time_end' => 'Время окончания',
    'group_room' => 'Кабинет',
    'group_created' => 'Группа создана',
    'group_enroll' => 'Добавить в группу',
    'group_enrolled' => 'Студент добавлен в группу',
    'weekday_1' => 'Понедельник',
    'weekday_2' => 'Вторник',
    'weekday_3' => 'Среда',
    'weekday_4' => 'Четверг',
    'weekday_5' => 'Пятница',
    'weekday_6' => 'Суббота',
    'weekday_7' => 'Воскресенье',
    'teacher_create' => 'Новый преподаватель',
    'teacher_name' => 'Ф.И.О',
    'teacher_phone' => 'Номер телефона',
    'teacher_password' => 'Пароль',
    'teacher_created' => 'Преподаватель добавлен',
    'test_create' => 'Новый тест',
    'test_title_uz' => 'Заголовок (uz)',
    'test_title_ru' => 'Заголовок (ru)',
    'test_passing_score' => 'Проходной балл (%)',
    'test_created' => 'Тест создан',
    'question_add' => 'Добавить вопрос',
    'question_text_uz' => 'Текст вопроса (uz)',
    'question_text_ru' => 'Текст вопроса (ru)',
    'answer_text_uz' => 'Текст ответа (uz)',
    'answer_text_ru' => 'Текст ответа (ru)',
    'answer_correct' => 'Правильный ответ',
    'question_added' => 'Вопрос добавлен',
    'certificates_title' => 'Выданные сертификаты',
    'certificate_number' => 'Номер сертификата',
    'certificate_download' => 'Скачать',
    'dashboard_pending_applications' => 'Заявки в ожидании',
    'dashboard_active_groups' => 'Активные группы',
    'dashboard_certificates_month' => 'Сертификаты за этот месяц',
];
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Unit/CsrfTest.php tests/Unit/LangTest.php`
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add backend/src/Core/Request.php backend/src/Core/Csrf.php backend/src/Core/Lang.php backend/resources/lang/uz.php backend/resources/lang/ru.php backend/tests/Unit/CsrfTest.php backend/tests/Unit/LangTest.php
git commit -m "feat: add Request::formBody(), Csrf, and Lang for the admin panel"
```

---

### Task 2: `AdminAuthMiddleware` + admin seeder

**Files:**
- Create: `backend/src/Middleware/AdminAuthMiddleware.php`
- Create: `backend/database/seeders/seed_admin.php`
- Test: `backend/tests/Unit/AdminAuthMiddlewareTest.php`

**Interfaces:**
- Consumes: nothing new (pure `$_SESSION` read).
- Produces: `AdminAuthMiddleware::authenticate(): ?array` — used by every protected handler in Tasks 3-10.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Middleware\AdminAuthMiddleware;
use PHPUnit\Framework\TestCase;

final class AdminAuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testReturnsNullWhenSessionIsEmpty(): void
    {
        $this->assertNull(AdminAuthMiddleware::authenticate());
    }

    public function testReturnsClaimsWhenSessionIsSet(): void
    {
        $_SESSION['admin_user_id'] = 5;
        $_SESSION['admin_role'] = 'admin';

        $this->assertSame(['user_id' => 5, 'role' => 'admin'], AdminAuthMiddleware::authenticate());
    }

    public function testReturnsNullWhenOnlyOneKeyIsSet(): void
    {
        $_SESSION['admin_user_id'] = 5;

        $this->assertNull(AdminAuthMiddleware::authenticate());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/AdminAuthMiddlewareTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `AdminAuthMiddleware`**

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

final class AdminAuthMiddleware
{
    /**
     * @return array{user_id:int, role:string}|null
     */
    public static function authenticate(): ?array
    {
        if (!isset($_SESSION['admin_user_id'], $_SESSION['admin_role'])) {
            return null;
        }

        return [
            'user_id' => (int) $_SESSION['admin_user_id'],
            'role' => (string) $_SESSION['admin_role'],
        ];
    }
}
```

- [ ] **Step 4: Implement `database/seeders/seed_admin.php`**

```php
<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Env;
use App\Repositories\UserRepository;

$envFile = $argv[1] ?? '.env';
Env::load(dirname(__DIR__, 2), $envFile);

$phone = '+998900000000';
$password = 'admin12345';

$existing = (new UserRepository())->findByPhone($phone);

if ($existing !== null) {
    echo "Admin already exists (phone {$phone}).\n";
    exit(0);
}

(new UserRepository())->create('Bosh administrator', $phone, Auth::hashPassword($password), 'admin');

echo "Admin created. Login: {$phone}  Password: {$password}\n";
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/AdminAuthMiddlewareTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add backend/src/Middleware/AdminAuthMiddleware.php backend/database/seeders/seed_admin.php backend/tests/Unit/AdminAuthMiddlewareTest.php
git commit -m "feat: add AdminAuthMiddleware and admin account seeder"
```

---

### Task 3: `View`, `layout.php`, `login.php`, `public/admin.php`, `AuthController`

**Files:**
- Create: `backend/src/Core/View.php`
- Create: `backend/resources/views/layout.php`
- Create: `backend/resources/views/login.php`
- Create: `backend/public/admin.php`
- Create: `backend/src/Controllers/Admin/AuthController.php`
- Test: `backend/tests/Feature/AdminAuthEndpointTest.php`

**Interfaces:**
- Consumes: `Csrf`, `Lang`, `AdminAuthMiddleware`, `Auth`, `UserRepository` (all existing).
- Produces: `View::render()`; the admin controller return-array contract (`rendered`/`redirect`/`file`) that every later task's controllers follow.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\AuthController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminAuthEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM users WHERE phone = '+998911111111'");
        (new UserRepository())->create('Admin Test', '+998911111111', Auth::hashPassword('adminpass1'), 'admin');
    }

    public function testLoginFormRendersWithoutCrashing(): void
    {
        $router = new Router();
        (new AuthController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/login', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString(\App\Core\Lang::t('login_title'), $html);
        $this->assertStringContainsString('<form', $html);
    }

    public function testLoginWithCorrectCredentialsSetsSessionAndRedirects(): void
    {
        $router = new Router();
        (new AuthController())->register($router);

        $csrfToken = \App\Core\Csrf::token();

        ob_start();
        $result = $router->dispatch(new Request('POST', '/admin/login', [], [], [
            'phone' => '+998911111111',
            'password' => 'adminpass1',
            'csrf_token' => $csrfToken,
        ]));
        ob_end_clean();

        $this->assertSame(['redirect' => '/admin'], $result);
        $this->assertSame('admin', $_SESSION['admin_role']);
    }

    public function testLoginWithWrongPasswordDoesNotSetSession(): void
    {
        $router = new Router();
        (new AuthController())->register($router);
        $csrfToken = \App\Core\Csrf::token();

        ob_start();
        $result = $router->dispatch(new Request('POST', '/admin/login', [], [], [
            'phone' => '+998911111111',
            'password' => 'wrong',
            'csrf_token' => $csrfToken,
        ]));
        ob_end_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertArrayNotHasKey('admin_user_id', $_SESSION);
    }

    public function testLogoutClearsSession(): void
    {
        $_SESSION['admin_user_id'] = 1;
        $_SESSION['admin_role'] = 'admin';

        $router = new Router();
        (new AuthController())->register($router);

        $result = $router->dispatch(new Request('POST', '/admin/logout', [], [], []));

        $this->assertSame(['redirect' => '/admin/login'], $result);
        $this->assertArrayNotHasKey('admin_user_id', $_SESSION);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/AdminAuthEndpointTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Implement `View`**

```php
<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $viewsDir = dirname(__DIR__, 2) . '/resources/views';

        $renderFile = static function (string $file, array $vars): string {
            extract($vars);
            ob_start();
            require $file;
            return (string) ob_get_clean();
        };

        $content = $renderFile($viewsDir . '/' . $template . '.php', $data);

        echo $renderFile($viewsDir . '/layout.php', array_merge($data, ['content' => $content]));
    }
}
```

- [ ] **Step 4: Implement `resources/views/layout.php`**

```php
<?php
/** @var string $content */
use App\Core\Csrf;
use App\Core\Lang;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$isLoggedIn = isset($_SESSION['admin_user_id']);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Lang::current()) ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(Lang::t('app_title')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand" href="/admin"><?= htmlspecialchars(Lang::t('app_title')) ?></a>
        <?php if ($isLoggedIn): ?>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="/admin/applications"><?= htmlspecialchars(Lang::t('nav_applications')) ?></a>
            <a class="btn btn-sm btn-outline-secondary" href="/admin/groups"><?= htmlspecialchars(Lang::t('nav_groups')) ?></a>
            <a class="btn btn-sm btn-outline-secondary" href="/admin/teachers"><?= htmlspecialchars(Lang::t('nav_teachers')) ?></a>
            <a class="btn btn-sm btn-outline-secondary" href="/admin/tests"><?= htmlspecialchars(Lang::t('nav_tests')) ?></a>
            <a class="btn btn-sm btn-outline-secondary" href="/admin/certificates"><?= htmlspecialchars(Lang::t('nav_certificates')) ?></a>
            <button type="button" id="theme-toggle" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('theme_toggle')) ?></button>
            <form method="post" action="/admin/lang" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <input type="hidden" name="back" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/admin') ?>">
                <input type="hidden" name="locale" value="<?= Lang::current() === 'uz' ? 'ru' : 'uz' ?>">
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <?= Lang::current() === 'uz' ? htmlspecialchars(Lang::t('lang_ru')) : htmlspecialchars(Lang::t('lang_uz')) ?>
                </button>
            </form>
            <form method="post" action="/admin/logout" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(Lang::t('logout')) ?></button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</nav>
<div class="container">
    <?php if ($flash !== null): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?= $content ?>
</div>
<script>
(function () {
    var stored = localStorage.getItem('admin-theme');
    var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-bs-theme', theme);
    var btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-bs-theme');
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            localStorage.setItem('admin-theme', next);
        });
    }
})();
</script>
</body>
</html>
```

- [ ] **Step 5: Implement `resources/views/login.php`**

```php
<?php
/** @var string|null $error */
use App\Core\Csrf;
use App\Core\Lang;
?>
<div class="row justify-content-center">
    <div class="col-md-4">
        <h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('login_title')) ?></h1>
        <?php if ($error !== null): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/admin/login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars(Lang::t('login_phone')) ?></label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars(Lang::t('login_password')) ?></label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars(Lang::t('login_submit')) ?></button>
        </form>
    </div>
</div>
```

- [ ] **Step 6: Implement `AuthController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\UserRepository;

final class AuthController
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/login', fn (Request $req) => $this->loginForm($req));
        $router->post('/admin/login', fn (Request $req) => $this->login($req));
        $router->post('/admin/logout', fn (Request $req) => $this->logout($req));
        $router->post('/admin/lang', fn (Request $req) => $this->switchLang($req));
    }

    private function loginForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() !== null) {
            return ['redirect' => '/admin'];
        }

        View::render('login', ['error' => null]);
        return ['rendered' => true];
    }

    private function login(Request $request): array
    {
        $body = $request->formBody();

        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $phone = trim((string) ($body['phone'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $user = $this->users->findByPhone($phone);

        if ($user === null
            || !in_array($user['role'], ['admin', 'teacher'], true)
            || !Auth::verifyPassword($password, $user['password_hash'])
        ) {
            View::render('login', ['error' => Lang::t('login_invalid')]);
            return ['rendered' => true];
        }

        $_SESSION['admin_user_id'] = (int) $user['id'];
        $_SESSION['admin_role'] = $user['role'];

        return ['redirect' => '/admin'];
    }

    private function logout(Request $request): array
    {
        unset($_SESSION['admin_user_id'], $_SESSION['admin_role']);
        return ['redirect' => '/admin/login'];
    }

    private function switchLang(Request $request): array
    {
        $body = $request->formBody();
        Lang::set((string) ($body['locale'] ?? 'uz'));
        $back = (string) ($body['back'] ?? '/admin');

        return ['redirect' => $back];
    }
}
```

- [ ] **Step 7: Implement `public/admin.php`**

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\AuthController;

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

Env::load(dirname(__DIR__));

$router = new Router();
(new AuthController())->register($router);

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
```

(This registers only `AuthController` for now — Tasks 4-10 each add their controller's registration here.)

- [ ] **Step 8: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/AdminAuthEndpointTest.php`
Expected: PASS

- [ ] **Step 9: Commit**

```bash
git add backend/src/Core/View.php backend/resources/views/layout.php backend/resources/views/login.php backend/public/admin.php backend/src/Controllers/Admin/AuthController.php backend/tests/Feature/AdminAuthEndpointTest.php
git commit -m "feat: add admin panel entry point, layout, and login/logout"
```

---

### Task 4: `DashboardController`

**Files:**
- Create: `backend/src/Controllers/Admin/DashboardController.php`
- Create: `backend/resources/views/dashboard.php`
- Modify: `backend/public/admin.php` (register `DashboardController`)
- Test: `backend/tests/Feature/AdminDashboardTest.php`

**Interfaces:**
- Consumes: `AdminAuthMiddleware::authenticate()`, `Database::pdo()`, `View::render()`.
- Produces: `GET /admin` — dashboard with 3 counts.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\DashboardController;
use PHPUnit\Framework\TestCase;

final class AdminDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testRedirectsToLoginWhenNotAuthenticated(): void
    {
        $router = new Router();
        (new DashboardController())->register($router);

        $result = $router->dispatch(new Request('GET', '/admin', [], [], []));

        $this->assertSame(['redirect' => '/admin/login'], $result);
    }

    public function testRendersCountsWhenAuthenticated(): void
    {
        $_SESSION['admin_user_id'] = 1;
        $_SESSION['admin_role'] = 'admin';

        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM applications WHERE phone = '+998922222222'");
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO applications (full_name, phone, profession_id, status) VALUES (?, ?, ?, "pending")')
            ->execute(['Dash Test', '+998922222222', $professionId]);

        $router = new Router();
        (new DashboardController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString((string) $pdo->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn(), $html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/AdminDashboardTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `DashboardController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;

final class DashboardController
{
    public function register(Router $router): void
    {
        $router->get('/admin', fn (Request $req) => $this->index($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $pdo = Database::pdo();

        $pendingApplications = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
        $activeGroups = (int) $pdo->query('SELECT COUNT(*) FROM `groups` WHERE CURDATE() BETWEEN start_date AND end_date')->fetchColumn();
        $certificatesThisMonth = (int) $pdo->query(
            'SELECT COUNT(*) FROM certificates WHERE MONTH(issue_date) = MONTH(CURDATE()) AND YEAR(issue_date) = YEAR(CURDATE())'
        )->fetchColumn();

        View::render('dashboard', [
            'pendingApplications' => $pendingApplications,
            'activeGroups' => $activeGroups,
            'certificatesThisMonth' => $certificatesThisMonth,
        ]);

        return ['rendered' => true];
    }
}
```

- [ ] **Step 4: Implement `resources/views/dashboard.php`**

```php
<?php
/**
 * @var int $pendingApplications
 * @var int $activeGroups
 * @var int $certificatesThisMonth
 */
use App\Core\Lang;
?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars(Lang::t('dashboard_pending_applications')) ?></h5>
                <p class="display-6"><?= $pendingApplications ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars(Lang::t('dashboard_active_groups')) ?></h5>
                <p class="display-6"><?= $activeGroups ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars(Lang::t('dashboard_certificates_month')) ?></h5>
                <p class="display-6"><?= $certificatesThisMonth ?></p>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 5: Register `DashboardController` in `public/admin.php`**

Add `use App\Controllers\Admin\DashboardController;` and `(new DashboardController())->register($router);` (after `AuthController`'s registration).

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/AdminDashboardTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/src/Controllers/Admin/DashboardController.php backend/resources/views/dashboard.php backend/public/admin.php backend/tests/Feature/AdminDashboardTest.php
git commit -m "feat: add admin dashboard with pending/active/certificate counts"
```

---

### Task 5: `ApplicationController` (list, approve, reject)

**Files:**
- Modify: `backend/src/Repositories/ApplicationRepository.php` (add `allWithProfession()`, `reject()`)
- Create: `backend/src/Controllers/Admin/ApplicationController.php`
- Create: `backend/resources/views/applications/index.php`
- Modify: `backend/public/admin.php`
- Test: `backend/tests/Feature/AdminApplicationsTest.php`

**Interfaces:**
- Consumes: `ApplicationRepository::approve()` (exists from sub-project 1), `AdminAuthMiddleware`, `Csrf`.
- Produces: `ApplicationRepository::allWithProfession(?string $status): array`, `ApplicationRepository::reject(int $id): bool` (returns whether a pending row was actually updated).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\ApplicationController;
use PHPUnit\Framework\TestCase;

final class AdminApplicationsTest extends TestCase
{
    private int $professionId;
    private int $applicationId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec("DELETE FROM applications WHERE phone = '+998933333333'");
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO applications (full_name, phone, profession_id, status) VALUES (?, ?, ?, "pending")')
            ->execute(['App Test', '+998933333333', $this->professionId]);
        $this->applicationId = (int) $pdo->lastInsertId();
    }

    public function testListRendersPendingApplication(): void
    {
        $router = new Router();
        (new ApplicationController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/applications', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('App Test', $html);
    }

    public function testApproveCreatesUserAndRedirects(): void
    {
        $router = new Router();
        (new ApplicationController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/applications/{$this->applicationId}/approve", [], [], [
            'password' => 'temp12345',
            'csrf_token' => $token,
        ]));

        $this->assertSame('/admin/applications', $result['redirect']);
        $status = Database::pdo()->query("SELECT status FROM applications WHERE id = {$this->applicationId}")->fetchColumn();
        $this->assertSame('approved', $status);
    }

    public function testRejectUpdatesStatusAndRedirects(): void
    {
        $router = new Router();
        (new ApplicationController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/applications/{$this->applicationId}/reject", [], [], [
            'csrf_token' => $token,
        ]));

        $this->assertSame('/admin/applications', $result['redirect']);
        $status = Database::pdo()->query("SELECT status FROM applications WHERE id = {$this->applicationId}")->fetchColumn();
        $this->assertSame('rejected', $status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/AdminApplicationsTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Extend `ApplicationRepository`**

Read the current file (from sub-project 1) first, then add these two methods (keep `create()`, `find()`, `approve()` unchanged):

```php
    public function allWithProfession(?string $status = null): array
    {
        $sql = 'SELECT a.*, p.name_uz AS profession_name_uz, p.name_ru AS profession_name_ru
                FROM applications a JOIN professions p ON p.id = a.profession_id';
        $params = [];

        if ($status !== null) {
            $sql .= ' WHERE a.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY a.created_at DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function reject(int $id): bool
    {
        $stmt = Database::pdo()->prepare("UPDATE applications SET status = 'rejected' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
```

- [ ] **Step 4: Implement `ApplicationController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\ApplicationRepository;

final class ApplicationController
{
    public function __construct(private readonly ApplicationRepository $repository = new ApplicationRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/applications', fn (Request $req) => $this->index($req));
        $router->post('/admin/applications/{id}/approve', fn (Request $req) => $this->approve($req));
        $router->post('/admin/applications/{id}/reject', fn (Request $req) => $this->reject($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $status = $request->formBody()['status'] ?? null;
        $status = in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : null;

        View::render('applications/index', [
            'applications' => $this->repository->allWithProfession($status),
            'statusFilter' => $status,
        ]);

        return ['rendered' => true];
    }

    private function approve(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $id = (int) $request->param('id');
        $password = (string) ($body['password'] ?? '');

        if (strlen($password) < 6) {
            return ['redirect' => '/admin/applications', 'flash' => 'Parol kamida 6 belgidan iborat bo\'lishi kerak'];
        }

        try {
            $this->repository->approve($id, $password);
        } catch (\RuntimeException) {
            return ['redirect' => '/admin/applications', 'flash' => 'Ariza allaqachon ko\'rib chiqilgan'];
        }

        return ['redirect' => '/admin/applications', 'flash' => Lang::t('application_approved')];
    }

    private function reject(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        if (!Csrf::verify($request->formBody()['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $this->repository->reject((int) $request->param('id'));

        return ['redirect' => '/admin/applications', 'flash' => Lang::t('application_rejected')];
    }
}
```

- [ ] **Step 5: Implement `resources/views/applications/index.php`**

```php
<?php
/** @var array $applications */
/** @var string|null $statusFilter */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('nav_applications')) ?></h1>
<table class="table table-striped">
    <thead>
        <tr><th>F.I.Sh</th><th>Telefon</th><th>Kasb</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($applications as $app): ?>
        <tr>
            <td><?= htmlspecialchars($app['full_name']) ?></td>
            <td><?= htmlspecialchars($app['phone']) ?></td>
            <td><?= htmlspecialchars($app['profession_name_uz']) ?></td>
            <td><?= htmlspecialchars(Lang::t('status_' . $app['status'])) ?></td>
            <td>
                <?php if ($app['status'] === 'pending'): ?>
                <form method="post" action="/admin/applications/<?= (int) $app['id'] ?>/approve" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <input type="password" name="password" placeholder="<?= htmlspecialchars(Lang::t('teacher_password')) ?>" required minlength="6" class="form-control form-control-sm d-inline w-auto">
                    <button type="submit" class="btn btn-sm btn-success"><?= htmlspecialchars(Lang::t('approve')) ?></button>
                </form>
                <form method="post" action="/admin/applications/<?= (int) $app['id'] ?>/reject" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <button type="submit" class="btn btn-sm btn-danger"><?= htmlspecialchars(Lang::t('reject')) ?></button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
```

- [ ] **Step 6: Register `ApplicationController` in `public/admin.php`**

Add the `use` statement and `(new ApplicationController())->register($router);`.

- [ ] **Step 7: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/AdminApplicationsTest.php`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add backend/src/Repositories/ApplicationRepository.php backend/src/Controllers/Admin/ApplicationController.php backend/resources/views/applications/index.php backend/public/admin.php backend/tests/Feature/AdminApplicationsTest.php
git commit -m "feat: add admin applications list, approve, and reject"
```

---

### Task 6: `GroupRepository` + `TeacherRepository` + `ScheduleGenerator`

**Files:**
- Create: `backend/src/Repositories/GroupRepository.php`
- Create: `backend/src/Repositories/TeacherRepository.php`
- Create: `backend/src/Services/ScheduleGenerator.php`
- Test: `backend/tests/Unit/ScheduleGeneratorTest.php`

**Interfaces:**
- Consumes: `Database::pdo()`.
- Produces: `GroupRepository::all()/find()/create()/studentsIn()/unassignedApprovedStudents()/enrollStudent()`; `TeacherRepository::all(): array` (used by both `GroupController`'s teacher dropdown in Task 7 and `TeacherController`'s list in Task 8); `ScheduleGenerator::generateForGroup(int $groupId, \DateTimeImmutable $start, \DateTimeImmutable $end, array $weeklyTemplate): int`.

`TeacherRepository` has no test of its own in this task — it's a single one-line query, exercised end-to-end by Task 7's and Task 8's feature tests.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Services\ScheduleGenerator;
use PHPUnit\Framework\TestCase;

final class ScheduleGeneratorTest extends TestCase
{
    private int $groupId;

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM schedule');
        $pdo->exec('DELETE FROM `groups`');
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "SchedGen", "2026-01-05", "2026-01-18")')
            ->execute([$professionId]);
        $this->groupId = (int) $pdo->lastInsertId();
    }

    public function testGeneratesOneRowPerMatchingWeekdayInRange(): void
    {
        // 2026-01-05 is a Monday. Range is exactly 2 weeks (Mon 5 - Sun 18).
        // Template: Monday (1) and Wednesday (3) -> 2 occurrences each = 4 rows.
        $generator = new ScheduleGenerator();

        $count = $generator->generateForGroup(
            $this->groupId,
            new \DateTimeImmutable('2026-01-05'),
            new \DateTimeImmutable('2026-01-18'),
            [
                ['weekday' => 1, 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'room' => '101'],
                ['weekday' => 3, 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'room' => '101'],
            ]
        );

        $this->assertSame(4, $count);

        $dates = Database::pdo()
            ->query("SELECT lesson_date FROM schedule WHERE group_id = {$this->groupId} ORDER BY lesson_date")
            ->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertSame(['2026-01-05', '2026-01-07', '2026-01-12', '2026-01-14'], $dates);
    }

    public function testEmptyTemplateGeneratesNothing(): void
    {
        $generator = new ScheduleGenerator();

        $count = $generator->generateForGroup(
            $this->groupId,
            new \DateTimeImmutable('2026-01-05'),
            new \DateTimeImmutable('2026-01-18'),
            []
        );

        $this->assertSame(0, $count);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/ScheduleGeneratorTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `ScheduleGenerator`**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use DateInterval;
use DateTimeImmutable;

final class ScheduleGenerator
{
    /**
     * @param array<int, array{weekday:int, start_time:string, end_time:string, room:string}> $weeklyTemplate
     */
    public function generateForGroup(int $groupId, DateTimeImmutable $start, DateTimeImmutable $end, array $weeklyTemplate): int
    {
        if ($weeklyTemplate === []) {
            return 0;
        }

        $byWeekday = [];
        foreach ($weeklyTemplate as $entry) {
            $byWeekday[$entry['weekday']][] = $entry;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO schedule (group_id, lesson_date, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)'
        );

        $count = 0;
        $current = $start;
        $oneDay = new DateInterval('P1D');

        while ($current <= $end) {
            $weekday = (int) $current->format('N');

            foreach ($byWeekday[$weekday] ?? [] as $entry) {
                $stmt->execute([$groupId, $current->format('Y-m-d'), $entry['start_time'], $entry['end_time'], $entry['room']]);
                $count++;
            }

            $current = $current->add($oneDay);
        }

        return $count;
    }
}
```

- [ ] **Step 4: Implement `GroupRepository`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class GroupRepository
{
    public function all(): array
    {
        $sql = 'SELECT g.*, p.name_uz AS profession_name_uz, t.full_name AS teacher_name,
                       (SELECT COUNT(*) FROM enrollments e WHERE e.group_id = g.id AND e.status = "active") AS student_count
                FROM `groups` g
                JOIN professions p ON p.id = g.profession_id
                LEFT JOIN users t ON t.id = g.teacher_id
                ORDER BY g.start_date DESC';

        return Database::pdo()->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT g.*, p.name_uz AS profession_name_uz, t.full_name AS teacher_name
             FROM `groups` g
             JOIN professions p ON p.id = g.profession_id
             LEFT JOIN users t ON t.id = g.teacher_id
             WHERE g.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(int $professionId, ?int $teacherId, string $name, string $startDate, string $endDate): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO `groups` (profession_id, teacher_id, name, start_date, end_date) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$professionId, $teacherId, $name, $startDate, $endDate]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function studentsIn(int $groupId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.full_name, u.phone
             FROM enrollments e JOIN users u ON u.id = e.user_id
             WHERE e.group_id = ? AND e.status = "active"
             ORDER BY u.full_name'
        );
        $stmt->execute([$groupId]);

        return $stmt->fetchAll();
    }

    public function unassignedApprovedStudents(): array
    {
        $sql = "SELECT u.id, u.full_name, u.phone
                FROM users u
                LEFT JOIN enrollments e ON e.user_id = u.id AND e.status = 'active'
                WHERE u.role = 'student' AND e.id IS NULL
                ORDER BY u.full_name";

        return Database::pdo()->query($sql)->fetchAll();
    }

    public function enrollStudent(int $groupId, int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            "INSERT INTO enrollments (user_id, group_id, status) VALUES (?, ?, 'active')"
        );
        $stmt->execute([$userId, $groupId]);
    }
}
```

- [ ] **Step 5: Implement `TeacherRepository`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class TeacherRepository
{
    public function all(): array
    {
        $stmt = Database::pdo()->query("SELECT id, full_name, phone, created_at FROM users WHERE role = 'teacher' ORDER BY full_name");
        return $stmt->fetchAll();
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/ScheduleGeneratorTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/src/Repositories/GroupRepository.php backend/src/Repositories/TeacherRepository.php backend/src/Services/ScheduleGenerator.php backend/tests/Unit/ScheduleGeneratorTest.php
git commit -m "feat: add GroupRepository, TeacherRepository, and ScheduleGenerator"
```

---

### Task 7: `GroupController` (list, create, show, enroll)

**Files:**
- Create: `backend/src/Controllers/Admin/GroupController.php`
- Create: `backend/resources/views/groups/index.php`
- Create: `backend/resources/views/groups/create.php`
- Create: `backend/resources/views/groups/show.php`
- Modify: `backend/public/admin.php`
- Test: `backend/tests/Feature/AdminGroupsTest.php`

**Interfaces:**
- Consumes: `GroupRepository`, `TeacherRepository` (both from Task 6), `ScheduleGenerator`, `ProfessionRepository` (exists from sub-project 1).
- Produces: `GET/POST /admin/groups`, `GET /admin/groups/create`, `GET /admin/groups/{id}`, `POST /admin/groups/{id}/enroll`.

**CRITICAL ordering note:** `/admin/groups/create` (a literal path) and `/admin/groups/{id}` (a wildcard pattern) can both match a GET to `/admin/groups/create`. `Router::dispatch()` tries routes in registration order and returns the first match. You MUST register `GET /admin/groups/create` BEFORE `GET /admin/groups/{id}` inside `register()`, or requests to the create form will be swallowed by the `{id}` handler (which would try to look up a group named "create" and 404 incorrectly).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\GroupController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminGroupsTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM enrollments');
        $pdo->exec('DELETE FROM schedule');
        $pdo->exec('DELETE FROM `groups`');
        $pdo->exec("DELETE FROM users WHERE phone = '+998944444444'");
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        (new UserRepository())->create('Group Student', '+998944444444', Auth::hashPassword('x'), 'student');
    }

    public function testCreateFormRouteIsNotSwallowedByIdRoute(): void
    {
        $router = new Router();
        (new GroupController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/groups/create', [], [], []));
        ob_end_clean();

        $this->assertSame(['rendered' => true], $result);
    }

    public function testCreateGeneratesScheduleForWholeCourse(): void
    {
        $router = new Router();
        (new GroupController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/groups', [], [], [
            'csrf_token' => $token,
            'profession_id' => (string) $this->professionId,
            'teacher_id' => '',
            'name' => 'Test Group A',
            'start_date' => '2026-02-02',
            'end_date' => '2026-02-15',
            'weekdays' => ['1', '3'],
            'start_time' => '09:00',
            'end_time' => '11:00',
            'room' => '201',
        ]));

        $this->assertArrayHasKey('redirect', $result);
        $groupId = (int) Database::pdo()->query("SELECT id FROM `groups` WHERE name = 'Test Group A'")->fetchColumn();
        $scheduleCount = (int) Database::pdo()->query("SELECT COUNT(*) FROM schedule WHERE group_id = {$groupId}")->fetchColumn();
        $this->assertSame(4, $scheduleCount);
    }

    public function testEnrollAddsStudentToGroup(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO `groups` (profession_id, name, start_date, end_date) VALUES (?, "Enroll Group", "2026-01-01", "2026-01-01")')
            ->execute([$this->professionId]);
        $groupId = (int) $pdo->lastInsertId();
        $studentId = (int) $pdo->query("SELECT id FROM users WHERE phone = '+998944444444'")->fetchColumn();

        $router = new Router();
        (new GroupController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', "/admin/groups/{$groupId}/enroll", [], [], [
            'csrf_token' => $token,
            'user_id' => (string) $studentId,
        ]));

        $this->assertArrayHasKey('redirect', $result);
        $count = (int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE group_id = {$groupId} AND user_id = {$studentId}")->fetchColumn();
        $this->assertSame(1, $count);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/AdminGroupsTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `GroupController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\GroupRepository;
use App\Repositories\ProfessionRepository;
use App\Repositories\TeacherRepository;
use App\Services\ScheduleGenerator;
use DateTimeImmutable;

final class GroupController
{
    public function __construct(
        private readonly GroupRepository $groups = new GroupRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository(),
        private readonly TeacherRepository $teachers = new TeacherRepository(),
        private readonly ScheduleGenerator $scheduleGenerator = new ScheduleGenerator()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/groups', fn (Request $req) => $this->index($req));
        // MUST be registered before the /admin/groups/{id} route below.
        $router->get('/admin/groups/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/groups', fn (Request $req) => $this->create($req));
        $router->get('/admin/groups/{id}', fn (Request $req) => $this->show($req));
        $router->post('/admin/groups/{id}/enroll', fn (Request $req) => $this->enroll($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('groups/index', ['groups' => $this->groups->all()]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('groups/create', [
            'professions' => $this->professions->all(),
            'teachers' => $this->teachers->all(),
        ]);
        return ['rendered' => true];
    }

    private function create(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $professionId = (int) ($body['profession_id'] ?? 0);
        $teacherId = ($body['teacher_id'] ?? '') !== '' ? (int) $body['teacher_id'] : null;
        $name = trim((string) ($body['name'] ?? ''));
        $startDate = (string) ($body['start_date'] ?? '');
        $endDate = (string) ($body['end_date'] ?? '');
        $weekdays = array_map('intval', $body['weekdays'] ?? []);
        $startTime = (string) ($body['start_time'] ?? '09:00');
        $endTime = (string) ($body['end_time'] ?? '11:00');
        $room = (string) ($body['room'] ?? '');

        if ($professionId <= 0 || $name === '' || $startDate === '' || $endDate === '') {
            return ['redirect' => '/admin/groups/create', 'flash' => 'Barcha maydonlarni to\'ldiring'];
        }

        $groupId = $this->groups->create($professionId, $teacherId, $name, $startDate, $endDate);

        $template = array_map(
            static fn (int $weekday) => ['weekday' => $weekday, 'start_time' => $startTime . ':00', 'end_time' => $endTime . ':00', 'room' => $room],
            $weekdays
        );

        $this->scheduleGenerator->generateForGroup($groupId, new DateTimeImmutable($startDate), new DateTimeImmutable($endDate), $template);

        return ['redirect' => "/admin/groups/{$groupId}", 'flash' => Lang::t('group_created')];
    }

    private function show(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $group = $this->groups->find((int) $request->param('id'));

        if ($group === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        View::render('groups/show', [
            'group' => $group,
            'students' => $this->groups->studentsIn((int) $group['id']),
            'unassigned' => $this->groups->unassignedApprovedStudents(),
        ]);
        return ['rendered' => true];
    }

    private function enroll(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $groupId = (int) $request->param('id');
        $userId = (int) ($body['user_id'] ?? 0);

        if ($userId > 0) {
            $this->groups->enrollStudent($groupId, $userId);
        }

        return ['redirect' => "/admin/groups/{$groupId}", 'flash' => Lang::t('group_enrolled')];
    }
}
```

- [ ] **Step 4: Implement `resources/views/groups/index.php`**

```php
<?php
/** @var array $groups */
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_groups')) ?></h1>
    <a href="/admin/groups/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('group_create')) ?></a>
</div>
<table class="table table-striped">
    <thead><tr><th><?= htmlspecialchars(Lang::t('group_name')) ?></th><th><?= htmlspecialchars(Lang::t('group_profession')) ?></th><th><?= htmlspecialchars(Lang::t('group_teacher')) ?></th><th></th></tr></thead>
    <tbody>
    <?php foreach ($groups as $g): ?>
        <tr>
            <td><a href="/admin/groups/<?= (int) $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></a></td>
            <td><?= htmlspecialchars($g['profession_name_uz']) ?></td>
            <td><?= htmlspecialchars($g['teacher_name'] ?? '-') ?></td>
            <td><?= (int) $g['student_count'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
```

- [ ] **Step 5: Implement `resources/views/groups/create.php`**

```php
<?php
/** @var array $professions */
/** @var array $teachers */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('group_create')) ?></h1>
<form method="post" action="/admin/groups">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_name')) ?></label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_profession')) ?></label>
        <select name="profession_id" class="form-select" required>
            <?php foreach ($professions as $p): ?>
                <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name_uz']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_teacher')) ?></label>
        <select name="teacher_id" class="form-select">
            <option value="">-</option>
            <?php foreach ($teachers as $t): ?>
                <option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row">
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_start_date')) ?></label>
            <input type="date" name="start_date" class="form-control" required>
        </div>
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_end_date')) ?></label>
            <input type="date" name="end_date" class="form-control" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_weekdays')) ?></label>
        <div class="d-flex gap-3 flex-wrap">
            <?php for ($d = 1; $d <= 7; $d++): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="weekdays[]" value="<?= $d ?>" id="wd<?= $d ?>">
                    <label class="form-check-label" for="wd<?= $d ?>"><?= htmlspecialchars(Lang::t('weekday_' . $d)) ?></label>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    <div class="row">
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_time_start')) ?></label>
            <input type="time" name="start_time" class="form-control" value="09:00" required>
        </div>
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_time_end')) ?></label>
            <input type="time" name="end_time" class="form-control" value="11:00" required>
        </div>
        <div class="col mb-3">
            <label class="form-label"><?= htmlspecialchars(Lang::t('group_room')) ?></label>
            <input type="text" name="room" class="form-control" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('group_create')) ?></button>
</form>
```

- [ ] **Step 6: Implement `resources/views/groups/show.php`**

```php
<?php
/** @var array $group */
/** @var array $students */
/** @var array $unassigned */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars($group['name']) ?></h1>
<p><?= htmlspecialchars($group['profession_name_uz']) ?> — <?= htmlspecialchars($group['teacher_name'] ?? '-') ?></p>

<h2 class="h6">Talabalar</h2>
<ul class="list-group mb-4">
    <?php foreach ($students as $s): ?>
        <li class="list-group-item"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['phone']) ?>)</li>
    <?php endforeach; ?>
</ul>

<h2 class="h6"><?= htmlspecialchars(Lang::t('group_enroll')) ?></h2>
<form method="post" action="/admin/groups/<?= (int) $group['id'] ?>/enroll" class="d-flex gap-2">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <select name="user_id" class="form-select" required>
        <?php foreach ($unassigned as $u): ?>
            <option value="<?= (int) $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['phone']) ?>)</option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('group_enroll')) ?></button>
</form>
```

- [ ] **Step 7: Register `GroupController` in `public/admin.php`**

Add the `use` statement and `(new GroupController())->register($router);`.

- [ ] **Step 8: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/AdminGroupsTest.php`
Expected: PASS — in particular `testCreateFormRouteIsNotSwallowedByIdRoute` confirms the ordering note in this task's header was followed correctly.

- [ ] **Step 9: Commit**

```bash
git add backend/src/Controllers/Admin/GroupController.php backend/resources/views/groups backend/public/admin.php backend/tests/Feature/AdminGroupsTest.php
git commit -m "feat: add admin groups list, create (with auto schedule), show, and enroll"
```

---

### Task 8: `TeacherController`

**Files:**
- Create: `backend/src/Controllers/Admin/TeacherController.php`
- Create: `backend/resources/views/teachers/index.php`
- Create: `backend/resources/views/teachers/create.php`
- Modify: `backend/public/admin.php`
- Test: `backend/tests/Feature/AdminTeachersTest.php`

**Interfaces:**
- Consumes: `UserRepository::create()` (exists), `Auth::hashPassword()` (exists), `TeacherRepository::all()` (created in Task 6).

**Ordering note (same hazard as Task 7):** register `GET /admin/teachers/create` before any future `{id}` route on this controller (none exists yet in this plan, but keep the literal-before-wildcard rule in mind if extended later).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\TeacherController;
use PHPUnit\Framework\TestCase;

final class AdminTeachersTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998955555555'");
    }

    public function testCreateAddsTeacherAndRedirects(): void
    {
        $router = new Router();
        (new TeacherController())->register($router);
        $token = Csrf::token();

        $result = $router->dispatch(new Request('POST', '/admin/teachers', [], [], [
            'csrf_token' => $token,
            'full_name' => 'New Teacher',
            'phone' => '+998955555555',
            'password' => 'teachpass1',
        ]));

        $this->assertArrayHasKey('redirect', $result);
        $role = Database::pdo()->query("SELECT role FROM users WHERE phone = '+998955555555'")->fetchColumn();
        $this->assertSame('teacher', $role);
    }

    public function testListRendersTeacher(): void
    {
        $pdo = Database::pdo();
        $pdo->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES ('List Teacher', '+998955555555', 'x', 'teacher')")->execute();

        $router = new Router();
        (new TeacherController())->register($router);

        ob_start();
        $result = $router->dispatch(new Request('GET', '/admin/teachers', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $result);
        $this->assertStringContainsString('List Teacher', $html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/AdminTeachersTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `TeacherController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;

final class TeacherController
{
    public function __construct(
        private readonly TeacherRepository $teachers = new TeacherRepository(),
        private readonly UserRepository $users = new UserRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/teachers', fn (Request $req) => $this->index($req));
        $router->get('/admin/teachers/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/teachers', fn (Request $req) => $this->create($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('teachers/index', ['teachers' => $this->teachers->all()]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('teachers/create', []);
        return ['rendered' => true];
    }

    private function create(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $fullName = trim((string) ($body['full_name'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($fullName === '' || $phone === '' || strlen($password) < 6) {
            return ['redirect' => '/admin/teachers/create', 'flash' => 'Barcha maydonlarni to\'g\'ri to\'ldiring'];
        }

        $this->users->create($fullName, $phone, Auth::hashPassword($password), 'teacher');

        return ['redirect' => '/admin/teachers', 'flash' => Lang::t('teacher_created')];
    }
}
```

- [ ] **Step 4: Implement `resources/views/teachers/index.php`**

```php
<?php
/** @var array $teachers */
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_teachers')) ?></h1>
    <a href="/admin/teachers/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('teacher_create')) ?></a>
</div>
<table class="table table-striped">
    <thead><tr><th><?= htmlspecialchars(Lang::t('teacher_name')) ?></th><th><?= htmlspecialchars(Lang::t('teacher_phone')) ?></th></tr></thead>
    <tbody>
    <?php foreach ($teachers as $t): ?>
        <tr><td><?= htmlspecialchars($t['full_name']) ?></td><td><?= htmlspecialchars($t['phone']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
```

- [ ] **Step 5: Implement `resources/views/teachers/create.php`**

```php
<?php
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('teacher_create')) ?></h1>
<form method="post" action="/admin/teachers">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_name')) ?></label>
        <input type="text" name="full_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_phone')) ?></label>
        <input type="text" name="phone" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('teacher_password')) ?></label>
        <input type="password" name="password" class="form-control" minlength="6" required>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('teacher_create')) ?></button>
</form>
```

- [ ] **Step 6: Register `TeacherController` in `public/admin.php`**

Add the `use` statement and `(new TeacherController())->register($router);`.

- [ ] **Step 7: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/AdminTeachersTest.php`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add backend/src/Controllers/Admin/TeacherController.php backend/resources/views/teachers backend/public/admin.php backend/tests/Feature/AdminTeachersTest.php
git commit -m "feat: add admin teachers list and create"
```

---

### Task 9: `QuestionRepository` + `TestRepository::create()` + admin `TestController` + `QuestionController`

**Files:**
- Create: `backend/src/Repositories/QuestionRepository.php`
- Modify: `backend/src/Repositories/TestRepository.php` (add `create()`)
- Create: `backend/src/Controllers/Admin/TestController.php`
- Create: `backend/src/Controllers/Admin/QuestionController.php`
- Create: `backend/resources/views/tests/index.php`
- Create: `backend/resources/views/tests/create.php`
- Create: `backend/resources/views/tests/questions.php`
- Modify: `backend/public/admin.php`
- Test: `backend/tests/Feature/AdminTestsTest.php`

**Interfaces:**
- Consumes: `ProfessionRepository::all()` (exists).
- Produces: `TestRepository::create(int $professionId, string $titleUz, string $titleRu, int $passingScore): int`; `QuestionRepository::createWithAnswers(int $testId, string $textUz, string $textRu, array $answers): int` where `$answers` is a list of `['text_uz'=>string,'text_ru'=>string,'is_correct'=>bool]`; `QuestionRepository::forTestWithCorrectFlag(int $testId): array` (unlike the API's `TestRepository::questionsWithAnswers()`, this INCLUDES `is_correct` — it's for staff, not students).

**Namespace note:** `App\Controllers\Admin\TestController` is a distinct class from the existing `App\Controllers\Api\TestController` (sub-project 1) — different namespace, no collision. Do not modify the API one in this task (that happens in Task 11).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\TestController;
use App\Controllers\Admin\QuestionController;
use PHPUnit\Framework\TestCase;

final class AdminTestsTest extends TestCase
{
    private int $professionId;

    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');
        $this->professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
    }

    public function testCreateTestAndAddQuestionWithAnswers(): void
    {
        $router = new Router();
        (new TestController())->register($router);
        (new QuestionController())->register($router);
        $token = Csrf::token();

        $createResult = $router->dispatch(new Request('POST', '/admin/tests', [], [], [
            'csrf_token' => $token,
            'profession_id' => (string) $this->professionId,
            'title_uz' => 'Yakuniy',
            'title_ru' => 'Финал',
            'passing_score' => '60',
        ]));

        $this->assertArrayHasKey('redirect', $createResult);
        $testId = (int) Database::pdo()->query("SELECT id FROM tests WHERE title_uz = 'Yakuniy'")->fetchColumn();
        $this->assertGreaterThan(0, $testId);

        $questionResult = $router->dispatch(new Request('POST', "/admin/tests/{$testId}/questions", [], [], [
            'csrf_token' => $token,
            'text_uz' => 'Savol?',
            'text_ru' => 'Вопрос?',
            'answer_text_uz' => ['A', 'B'],
            'answer_text_ru' => ['А', 'Б'],
            'correct_index' => '0',
        ]));

        $this->assertArrayHasKey('redirect', $questionResult);

        $questionId = (int) Database::pdo()->query("SELECT id FROM questions WHERE test_id = {$testId}")->fetchColumn();
        $answers = Database::pdo()->query("SELECT text_uz, is_correct FROM answers WHERE question_id = {$questionId} ORDER BY id")->fetchAll();

        $this->assertCount(2, $answers);
        $this->assertSame(1, (int) $answers[0]['is_correct']);
        $this->assertSame(0, (int) $answers[1]['is_correct']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/AdminTestsTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Extend `TestRepository`**

Read the current `backend/src/Repositories/TestRepository.php` (from sub-project 1) first, then add (keep every existing method unchanged):

```php
    public function create(int $professionId, string $titleUz, string $titleRu, int $passingScore): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO tests (profession_id, title_uz, title_ru, passing_score) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$professionId, $titleUz, $titleRu, $passingScore]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function allWithProfession(): array
    {
        $sql = 'SELECT t.*, p.name_uz AS profession_name_uz,
                       (SELECT COUNT(*) FROM questions q WHERE q.test_id = t.id) AS question_count
                FROM tests t JOIN professions p ON p.id = t.profession_id
                ORDER BY p.name_uz, t.id';

        return Database::pdo()->query($sql)->fetchAll();
    }
```

- [ ] **Step 4: Implement `QuestionRepository`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class QuestionRepository
{
    /**
     * @param array<int, array{text_uz:string, text_ru:string, is_correct:bool}> $answers
     */
    public function createWithAnswers(int $testId, string $textUz, string $textRu, array $answers): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('INSERT INTO questions (test_id, text_uz, text_ru) VALUES (?, ?, ?)');
            $stmt->execute([$testId, $textUz, $textRu]);
            $questionId = (int) $pdo->lastInsertId();

            $answerStmt = $pdo->prepare(
                'INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, ?, ?, ?)'
            );
            foreach ($answers as $answer) {
                $answerStmt->execute([$questionId, $answer['text_uz'], $answer['text_ru'], $answer['is_correct'] ? 1 : 0]);
            }

            $pdo->commit();

            return $questionId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function forTestWithCorrectFlag(int $testId): array
    {
        $stmt = Database::pdo()->prepare('SELECT id, text_uz, text_ru FROM questions WHERE test_id = ?');
        $stmt->execute([$testId]);
        $questions = $stmt->fetchAll();

        foreach ($questions as &$question) {
            $answerStmt = Database::pdo()->prepare('SELECT id, text_uz, text_ru, is_correct FROM answers WHERE question_id = ?');
            $answerStmt->execute([$question['id']]);
            $question['answers'] = $answerStmt->fetchAll();
        }

        return $questions;
    }
}
```

- [ ] **Step 5: Implement `Controllers/Admin/TestController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\ProfessionRepository;
use App\Repositories\TestRepository;

final class TestController
{
    public function __construct(
        private readonly TestRepository $tests = new TestRepository(),
        private readonly ProfessionRepository $professions = new ProfessionRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/tests', fn (Request $req) => $this->index($req));
        $router->get('/admin/tests/create', fn (Request $req) => $this->createForm($req));
        $router->post('/admin/tests', fn (Request $req) => $this->create($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('tests/index', ['tests' => $this->tests->allWithProfession()]);
        return ['rendered' => true];
    }

    private function createForm(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('tests/create', ['professions' => $this->professions->all()]);
        return ['rendered' => true];
    }

    private function create(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $professionId = (int) ($body['profession_id'] ?? 0);
        $titleUz = trim((string) ($body['title_uz'] ?? ''));
        $titleRu = trim((string) ($body['title_ru'] ?? ''));
        $passingScore = (int) ($body['passing_score'] ?? 70);

        if ($professionId <= 0 || $titleUz === '' || $titleRu === '') {
            return ['redirect' => '/admin/tests/create', 'flash' => 'Barcha maydonlarni to\'ldiring'];
        }

        $testId = $this->tests->create($professionId, $titleUz, $titleRu, $passingScore);

        return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('test_created')];
    }
}
```

- [ ] **Step 6: Implement `QuestionController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Lang;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\QuestionRepository;

final class QuestionController
{
    public function __construct(private readonly QuestionRepository $questions = new QuestionRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/tests/{id}/questions', fn (Request $req) => $this->index($req));
        $router->post('/admin/tests/{id}/questions', fn (Request $req) => $this->store($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $testId = (int) $request->param('id');

        View::render('tests/questions', [
            'testId' => $testId,
            'questions' => $this->questions->forTestWithCorrectFlag($testId),
        ]);
        return ['rendered' => true];
    }

    private function store(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $body = $request->formBody();
        if (!Csrf::verify($body['csrf_token'] ?? null)) {
            return ['redirect' => '/admin/login'];
        }

        $testId = (int) $request->param('id');
        $textUz = trim((string) ($body['text_uz'] ?? ''));
        $textRu = trim((string) ($body['text_ru'] ?? ''));
        $answerTextsUz = $body['answer_text_uz'] ?? [];
        $answerTextsRu = $body['answer_text_ru'] ?? [];
        $correctIndex = (int) ($body['correct_index'] ?? -1);

        if ($textUz === '' || $textRu === '' || count($answerTextsUz) < 2 || $correctIndex < 0 || $correctIndex >= count($answerTextsUz)) {
            return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => 'Savol va kamida 2 ta javob kiriting'];
        }

        $answers = [];
        foreach ($answerTextsUz as $i => $textUzAnswer) {
            $answers[] = [
                'text_uz' => (string) $textUzAnswer,
                'text_ru' => (string) ($answerTextsRu[$i] ?? ''),
                'is_correct' => $i === $correctIndex,
            ];
        }

        $this->questions->createWithAnswers($testId, $textUz, $textRu, $answers);

        return ['redirect' => "/admin/tests/{$testId}/questions", 'flash' => Lang::t('question_added')];
    }
}
```

- [ ] **Step 7: Implement `resources/views/tests/index.php`**

```php
<?php
/** @var array $tests */
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_tests')) ?></h1>
    <a href="/admin/tests/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('test_create')) ?></a>
</div>
<table class="table table-striped">
    <thead><tr><th>Sarlavha</th><th>Kasb</th><th>Savollar</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($tests as $t): ?>
        <tr>
            <td><?= htmlspecialchars($t['title_uz']) ?></td>
            <td><?= htmlspecialchars($t['profession_name_uz']) ?></td>
            <td><?= (int) $t['question_count'] ?></td>
            <td><a href="/admin/tests/<?= (int) $t['id'] ?>/questions" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(Lang::t('question_add')) ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
```

- [ ] **Step 8: Implement `resources/views/tests/create.php`**

```php
<?php
/** @var array $professions */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('test_create')) ?></h1>
<form method="post" action="/admin/tests">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('group_profession')) ?></label>
        <select name="profession_id" class="form-select" required>
            <?php foreach ($professions as $p): ?>
                <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name_uz']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_title_uz')) ?></label>
        <input type="text" name="title_uz" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_title_ru')) ?></label>
        <input type="text" name="title_ru" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('test_passing_score')) ?></label>
        <input type="number" name="passing_score" class="form-control" min="1" max="100" value="70" required>
    </div>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('test_create')) ?></button>
</form>
```

- [ ] **Step 9: Implement `resources/views/tests/questions.php`**

```php
<?php
/** @var int $testId */
/** @var array $questions */
use App\Core\Csrf;
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('question_add')) ?></h1>

<?php foreach ($questions as $q): ?>
    <div class="card mb-2"><div class="card-body">
        <p class="fw-bold"><?= htmlspecialchars($q['text_uz']) ?></p>
        <ul>
        <?php foreach ($q['answers'] as $a): ?>
            <li><?= htmlspecialchars($a['text_uz']) ?><?= ((int) $a['is_correct'] === 1) ? ' ✓' : '' ?></li>
        <?php endforeach; ?>
        </ul>
    </div></div>
<?php endforeach; ?>

<form method="post" action="/admin/tests/<?= $testId ?>/questions" class="mt-4">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('question_text_uz')) ?></label>
        <input type="text" name="text_uz" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label"><?= htmlspecialchars(Lang::t('question_text_ru')) ?></label>
        <input type="text" name="text_ru" class="form-control" required>
    </div>
    <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="row mb-2 align-items-center">
            <div class="col">
                <input type="text" name="answer_text_uz[]" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('answer_text_uz')) ?> <?= $i + 1 ?>" <?= $i < 2 ? 'required' : '' ?>>
            </div>
            <div class="col">
                <input type="text" name="answer_text_ru[]" class="form-control" placeholder="<?= htmlspecialchars(Lang::t('answer_text_ru')) ?> <?= $i + 1 ?>" <?= $i < 2 ? 'required' : '' ?>>
            </div>
            <div class="col-auto">
                <input type="radio" name="correct_index" value="<?= $i ?>" <?= $i === 0 ? 'checked' : '' ?>> <?= htmlspecialchars(Lang::t('answer_correct')) ?>
            </div>
        </div>
    <?php endfor; ?>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(Lang::t('question_add')) ?></button>
</form>
```

- [ ] **Step 10: Register both controllers in `public/admin.php`**

Add `use` statements for `App\Controllers\Admin\TestController` and `App\Controllers\Admin\QuestionController`, then `(new TestController())->register($router);` and `(new QuestionController())->register($router);`.

- [ ] **Step 11: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/AdminTestsTest.php`
Expected: PASS

- [ ] **Step 12: Commit**

```bash
git add backend/src/Repositories/QuestionRepository.php backend/src/Repositories/TestRepository.php backend/src/Controllers/Admin/TestController.php backend/src/Controllers/Admin/QuestionController.php backend/resources/views/tests backend/public/admin.php backend/tests/Feature/AdminTestsTest.php
git commit -m "feat: add admin test creation and question/answer management"
```

---

### Task 10: `CertificateController` (Admin) — list and download

**Files:**
- Modify: `backend/src/Repositories/CertificateRepository.php` (add `allWithDetails()`)
- Create: `backend/src/Controllers/Admin/CertificateController.php`
- Create: `backend/resources/views/certificates/index.php`
- Modify: `backend/public/admin.php`
- Test: `backend/tests/Feature/AdminCertificatesTest.php`

**Interfaces:**
- Consumes: `CertificateRepository::find()` (exists, still returns `pdf_path` — that's fine here, this is staff-only, unlike the API's student-facing list which intentionally hides it).
- Produces: `CertificateRepository::allWithDetails(): array` (joins `users`+`professions`, includes `pdf_path` is NOT needed in this list view either — omit it here too, staff download via the dedicated download route, not by reading the list JSON/HTML directly).

**Ordering note:** register `GET /admin/certificates` before `GET /admin/certificates/{id}/download` — same literal-vs-wildcard hazard as Tasks 7-8, though here the paths differ enough (`/download` suffix) that it's not strictly required; register in the order shown below regardless, for consistency with the rest of the codebase.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Admin\CertificateController;
use App\Repositories\CertificateRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AdminCertificatesTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['admin_user_id' => 1, 'admin_role' => 'admin'];
    }

    public function testListAndDownloadCertificate(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM certificates');
        $pdo->exec("DELETE FROM users WHERE phone = '+998966666666'");
        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $userId = (new UserRepository())->create('Cert Admin Test', '+998966666666', Auth::hashPassword('x'), 'student');
        $certificate = (new CertificateRepository())->issue($userId, $professionId);

        $router = new Router();
        (new CertificateController())->register($router);

        ob_start();
        $listResult = $router->dispatch(new Request('GET', '/admin/certificates', [], [], []));
        $html = ob_get_clean();

        $this->assertSame(['rendered' => true], $listResult);
        $this->assertStringContainsString('Cert Admin Test', $html);

        $downloadResult = $router->dispatch(new Request('GET', "/admin/certificates/{$certificate['id']}/download", [], [], []));

        $this->assertArrayHasKey('file', $downloadResult);
        $this->assertFileExists($downloadResult['file']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/AdminCertificatesTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Extend `CertificateRepository`**

Read the current file (from sub-project 1) first, then add (keep `forUser()`, `find()`, `issue()` unchanged):

```php
    public function allWithDetails(): array
    {
        $sql = 'SELECT c.id, c.certificate_number, c.issue_date, u.full_name AS student_name, p.name_uz AS profession_name_uz
                FROM certificates c
                JOIN users u ON u.id = c.user_id
                JOIN professions p ON p.id = c.profession_id
                ORDER BY c.issue_date DESC';

        return Database::pdo()->query($sql)->fetchAll();
    }
```

- [ ] **Step 4: Implement `CertificateController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Router;
use App\Core\View;
use App\Middleware\AdminAuthMiddleware;
use App\Repositories\CertificateRepository;

final class CertificateController
{
    public function __construct(private readonly CertificateRepository $repository = new CertificateRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/admin/certificates', fn (Request $req) => $this->index($req));
        $router->get('/admin/certificates/{id}/download', fn (Request $req) => $this->download($req));
    }

    private function index(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        View::render('certificates/index', ['certificates' => $this->repository->allWithDetails()]);
        return ['rendered' => true];
    }

    private function download(Request $request): array
    {
        if (AdminAuthMiddleware::authenticate() === null) {
            return ['redirect' => '/admin/login'];
        }

        $certificate = $this->repository->find((int) $request->param('id'));

        if ($certificate === null) {
            http_response_code(404);
            echo '<h1>404</h1>';
            return ['rendered' => true];
        }

        return ['file' => $certificate['pdf_path']];
    }
}
```

- [ ] **Step 5: Implement `resources/views/certificates/index.php`**

```php
<?php
/** @var array $certificates */
use App\Core\Lang;
?>
<h1 class="h4 mb-3"><?= htmlspecialchars(Lang::t('certificates_title')) ?></h1>
<table class="table table-striped">
    <thead><tr><th>F.I.Sh</th><th>Kasb</th><th><?= htmlspecialchars(Lang::t('certificate_number')) ?></th><th>Sana</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($certificates as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['student_name']) ?></td>
            <td><?= htmlspecialchars($c['profession_name_uz']) ?></td>
            <td><?= htmlspecialchars($c['certificate_number']) ?></td>
            <td><?= htmlspecialchars($c['issue_date']) ?></td>
            <td><a href="/admin/certificates/<?= (int) $c['id'] ?>/download" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(Lang::t('certificate_download')) ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
```

- [ ] **Step 6: Register `CertificateController` in `public/admin.php`**

Add the `use` statement and `(new CertificateController())->register($router);`.

- [ ] **Step 7: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/AdminCertificatesTest.php`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add backend/src/Repositories/CertificateRepository.php backend/src/Controllers/Admin/CertificateController.php backend/resources/views/certificates backend/public/admin.php backend/tests/Feature/AdminCertificatesTest.php
git commit -m "feat: add admin certificates list and download"
```

---

### Task 11: Auto-issue certificate when a student passes a test

**Files:**
- Modify: `backend/src/Controllers/Api/TestController.php` (from sub-project 1)
- Test: `backend/tests/Feature/AutoCertificateOnPassTest.php`

**Interfaces:**
- Consumes: `CertificateRepository::issue()` (exists), `TestRepository` (exists, need `profession_id` for a test — add a small lookup).
- Produces: passing `POST /api/v1/me/tests/{id}/submit` for the first time now also creates a `certificates` row (silently — the JSON response shape for this endpoint is unchanged, per the backend spec; this is a side effect, not a new field).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\TestController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AutoCertificateOnPassTest extends TestCase
{
    public function testPassingSubmitIssuesCertificateExactlyOnce(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM certificates');
        $pdo->exec('DELETE FROM test_attempts');
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');
        $pdo->exec("DELETE FROM users WHERE phone = '+998977777777'");

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (id, profession_id, title_uz, title_ru, passing_score) VALUES (1, ?, "T", "T", 50)')
            ->execute([$professionId]);
        $pdo->exec('INSERT INTO questions (id, test_id, text_uz, text_ru) VALUES (1, 1, "Q", "Q")');
        $pdo->exec('INSERT INTO answers (id, question_id, text_uz, text_ru, is_correct) VALUES (1, 1, "A", "A", 1)');

        $userId = (new UserRepository())->create('Cert Auto', '+998977777777', Auth::hashPassword('x'), 'student');
        $token = Auth::issueToken($userId, 'student');

        $router = new Router();
        (new TestController())->register($router);

        // First submit: passes, should issue a certificate.
        $router->dispatch(new Request('POST', '/api/v1/me/tests/1/submit', ['AUTHORIZATION' => "Bearer {$token}"], ['answers' => [1]]));

        $count = (int) $pdo->query("SELECT COUNT(*) FROM certificates WHERE user_id = {$userId} AND profession_id = {$professionId}")->fetchColumn();
        $this->assertSame(1, $count);

        // Second submit: passes again, must NOT issue a second certificate.
        $router->dispatch(new Request('POST', '/api/v1/me/tests/1/submit', ['AUTHORIZATION' => "Bearer {$token}"], ['answers' => [1]]));

        $countAfterSecondSubmit = (int) $pdo->query("SELECT COUNT(*) FROM certificates WHERE user_id = {$userId} AND profession_id = {$professionId}")->fetchColumn();
        $this->assertSame(1, $countAfterSecondSubmit);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/AutoCertificateOnPassTest.php`
Expected: FAIL — no certificate is issued yet.

- [ ] **Step 3: Modify `App\Controllers\Api\TestController::submit()`**

Read the current file (from sub-project 1, then as modified by that sub-project's final-review fix wave for the scoring/validation logic) first. Add the certificate-issuing side effect after `recordAttempt()`, before `return $result;`:

```php
        $result = $this->repository->score($testId, $answerIds);
        $this->repository->recordAttempt($claims['user_id'], $testId, $result['score'], $result['passed']);

        if ($result['passed']) {
            $alreadyIssuedStmt = \App\Core\Database::pdo()->prepare(
                'SELECT COUNT(*) FROM certificates c
                 JOIN tests t ON t.profession_id = c.profession_id
                 WHERE c.user_id = ? AND t.id = ?'
            );
            $alreadyIssuedStmt->execute([$claims['user_id'], $testId]);

            if ((int) $alreadyIssuedStmt->fetchColumn() === 0) {
                $professionStmt = \App\Core\Database::pdo()->prepare('SELECT profession_id FROM tests WHERE id = ?');
                $professionStmt->execute([$testId]);
                $professionId = (int) $professionStmt->fetchColumn();

                (new \App\Repositories\CertificateRepository())->issue($claims['user_id'], $professionId);
            }
        }

        return $result;
```

(Add `use App\Core\Database;` and `use App\Repositories\CertificateRepository;` to the top of the file instead of the fully-qualified names above, matching the file's existing import style — check the current imports first and follow the same convention.)

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/AutoCertificateOnPassTest.php`
Expected: PASS

- [ ] **Step 5: Run the full suite to confirm no regression in sub-project 1's tests**

Run: `vendor/bin/phpunit`
Expected: all tests pass, including `TestSubmitEndpointTest` from sub-project 1 (which doesn't assert on certificates, so it should be unaffected).

- [ ] **Step 6: Commit**

```bash
git add backend/src/Controllers/Api/TestController.php backend/tests/Feature/AutoCertificateOnPassTest.php
git commit -m "feat: auto-issue a certificate the first time a student passes a test"
```

---

### Task 12: Full suite run, README update, manual smoke-test checklist

**Files:**
- Modify: `backend/README.md`

**Interfaces:**
- Consumes: nothing new — this task verifies and documents Tasks 1-11.

- [ ] **Step 1: Run the entire suite twice in a row**

Run: `vendor/bin/phpunit` (twice, consecutively)
Expected: All Unit and Feature tests PASS both times (0 failures) — this confirms the admin panel's tests don't leave state that breaks a second run, same requirement sub-project 1 already established for its own tests.

- [ ] **Step 2: Update `backend/README.md`**

Read the current README (from sub-project 1, as amended by that sub-project's final fix wave) first, then add a new section after the existing "API" section:

```markdown
## Admin panel

Server-rendered at `/admin` (session-authenticated). Setup:

1. After running migrations and seeders (see Setup above), also run: `php database/seeders/seed_admin.php .env`
   This prints the admin login phone and a one-time password — save it, it is not shown again.
2. Serve alongside the API: `php -S localhost:8080 -t public` (both `public/index.php` and `public/admin.php` are served from the same document root).
3. Open `http://localhost:8080/admin/login`.

### Manual smoke test (after `composer install` + migrate + seed + seed_admin)

1. Log in at `/admin/login` with the seeded admin credentials.
2. `/admin/applications` — submit a `POST /api/v1/applications` via curl first (see API section), then approve it here with a password you choose; confirm the student can then log in via `POST /api/v1/auth/login` with that password.
3. `/admin/teachers/create` — add a teacher.
4. `/admin/groups/create` — create a group for the approved student's profession, pick the new teacher, set a 2-week date range and two weekdays; confirm `/admin/groups/{id}` shows the expected number of schedule rows (visible indirectly via `GET /api/v1/me/schedule` once the student is enrolled).
5. On the group's show page, enroll the approved student.
6. `/admin/tests/create` — create a test for the same profession, then add at least one question with 2+ answers and mark one correct.
7. As the student (via the API, `POST /api/v1/me/tests/{id}/submit`), submit the correct answer and confirm a 100% pass.
8. `/admin/certificates` — confirm the certificate now appears (auto-issued by Task 11's change) and the download link serves a real PDF.

### Known gaps carried over from sub-project 1

See the "Known gaps" section already in this README (no PHP available in the development environment, `Authorization` header stripping under some server configs, etc.) — those apply equally to the admin panel's own testing status: every test in this sub-project was also written and statically reviewed without ever being executed, until the human user's first real `composer install` + `vendor/bin/phpunit` run.
```

- [ ] **Step 3: Commit**

```bash
git add backend/README.md
git commit -m "docs: document admin panel setup and manual smoke-test checklist"
```

---

## Explicitly out of scope for this plan

- Editing `professions` (name/price/description) through the admin UI — seed/SQL only for now.
- Android app UI (sub-project 3).
- Any SMS/email delivery of credentials — admin relays the password they typed, in person.
