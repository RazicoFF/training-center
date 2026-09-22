# Backend + SQL API Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the PHP + MySQL backend (JSON REST API under `/api/v1`) that the Android app will consume: professions listing, online applications, login, JWT-protected profile/schedule/tests/certificates.

**Architecture:** Plain PHP (no heavy framework), PSR-4 autoload via Composer. A thin `Core` layer (Router, Request, Response, Database/PDO, Auth/JWT) sits under `Repositories` (raw SQL via PDO) and `Controllers/Api` (one per resource). `public/index.php` is the single entry point. `/admin/...` routes are explicitly out of scope for this plan (next sub-project).

**Tech Stack:** PHP 8.1+, PDO (MySQL, `utf8mb4`), Composer, PHPUnit, `firebase/php-jwt`, `dompdf/dompdf`, `vlucas/phpdotenv`.

**Spec:** `docs/superpowers/specs/2026-09-22-backend-db-design.md`

## Global Constraints

- PHP >= 8.1, strict_types declared in every file.
- All DB access through PDO with prepared statements — no string-concatenated SQL.
- All API responses are JSON; errors use `{"error": {"code": "...", "message": "..."}}` with the HTTP status from the spec (400/401/403/404/422/500).
- Passwords hashed with `password_hash()` (bcrypt), never stored/logged in plaintext.
- JWT secret and DB credentials come from `.env` (never committed); `.env.example` documents required keys.
- Every repository/service with business logic (password check, JWT, scoring, application approval) gets a PHPUnit test before the feature is considered done.
- Tables/columns exactly as named in the spec (`users`, `professions`, `applications`, `groups`, `schedule`, `enrollments`, `tests`, `questions`, `answers`, `test_attempts`, `certificates`).

---

## File Structure

```
backend/
  composer.json
  .env.example
  phpunit.xml
  public/
    index.php                    # entry point, builds Router, dispatches
  src/
    Core/
      Env.php                    # loads .env via phpdotenv
      Database.php                # PDO singleton factory
      Router.php                  # route registration + dispatch
      Request.php                 # parses method/path/JSON body/headers
      Response.php                # json()/error() helpers + status codes
      Auth.php                    # password hash/verify, JWT encode/decode
    Repositories/
      UserRepository.php
      ProfessionRepository.php
      ApplicationRepository.php
      ScheduleRepository.php
      TestRepository.php
      CertificateRepository.php
    Services/
      CertificatePdfService.php   # dompdf wrapper
    Controllers/
      Api/
        ProfessionController.php
        ApplicationController.php
        AuthController.php
        MeController.php
        TestController.php
        CertificateController.php
    Middleware/
      AuthMiddleware.php          # verifies JWT, attaches user_id/role to Request
  database/
    migrations/
      001_create_schema.sql
    seeders/
      seed.php                    # inserts 3 professions
    migrate.php                   # runs migrations/*.sql in order
  tests/
    Unit/
      AuthTest.php
      TestScoringTest.php
    Feature/
      ProfessionsEndpointTest.php
      ApplicationsEndpointTest.php
      AuthEndpointTest.php
      MeEndpointTest.php
      TestSubmitEndpointTest.php
      CertificateEndpointTest.php
    bootstrap.php                 # loads .env.testing, resets test DB schema
```

**Interfaces contract (used across tasks):**
- `Database::pdo(): PDO` — one shared connection per request, reads config from `Env`.
- `Auth::hashPassword(string $plain): string`, `Auth::verifyPassword(string $plain, string $hash): bool`
- `Auth::issueToken(int $userId, string $role): string`, `Auth::verifyToken(string $token): ?array` (returns `['user_id' => int, 'role' => string]` or `null`)
- `Response::json(array $data, int $status = 200): void`, `Response::error(string $code, string $message, int $status): void`
- `Request::method(): string`, `Request::path(): string`, `Request::jsonBody(): array`, `Request::header(string $name): ?string`, `Request::param(string $name): mixed` (route params), `Request::user(): ?array` (set by `AuthMiddleware`)

---

### Task 1: Project scaffolding

**Files:**
- Create: `backend/composer.json`
- Create: `backend/.env.example`
- Create: `backend/phpunit.xml`
- Create: `backend/tests/bootstrap.php`
- Create: `backend/public/index.php` (placeholder)

**Interfaces:**
- Produces: PSR-4 autoload root `App\` → `backend/src/`, autoload-dev root `Tests\` → `backend/tests/`.

- [ ] **Step 1: Create `composer.json`**

```json
{
    "name": "training-center/backend",
    "type": "project",
    "require": {
        "php": ">=8.1",
        "vlucas/phpdotenv": "^5.6",
        "firebase/php-jwt": "^6.10",
        "dompdf/dompdf": "^3.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5"
    },
    "autoload": {
        "psr-4": { "App\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "Tests\\": "tests/" }
    }
}
```

- [ ] **Step 2: Run `composer install`**

Run (from `backend/`): `composer install`
Expected: `vendor/` created, `composer.lock` written, no errors.

- [ ] **Step 3: Create `.env.example`**

```
DB_HOST=127.0.0.1
DB_NAME=training_center
DB_USER=root
DB_PASS=
JWT_SECRET=change-me-in-production
JWT_TTL_DAYS=30
```

Copy it to `.env` (untracked) and `.env.testing` (untracked, `DB_NAME=training_center_test`) with real local credentials.

- [ ] **Step 4: Create `phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php" colors="true">
    <testsuites>
        <testsuite name="Unit"><directory>tests/Unit</directory></testsuite>
        <testsuite name="Feature"><directory>tests/Feature</directory></testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 5: Create `tests/bootstrap.php`**

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;

Env::load(dirname(__DIR__), '.env.testing');
```

- [ ] **Step 6: Create placeholder `public/index.php`**

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

echo "Backend scaffolding OK";
```

- [ ] **Step 7: Add `.gitignore`**

```
/vendor/
.env
.env.testing
```

- [ ] **Step 8: Commit**

```bash
git add backend/composer.json backend/composer.lock backend/.env.example backend/phpunit.xml backend/tests/bootstrap.php backend/public/index.php backend/.gitignore
git commit -m "chore: scaffold backend PHP project"
```

---

### Task 2: `Env` and `Database` core classes

**Files:**
- Create: `backend/src/Core/Env.php`
- Create: `backend/src/Core/Database.php`
- Test: `backend/tests/Unit/DatabaseTest.php`

**Interfaces:**
- Consumes: nothing (foundation).
- Produces: `Env::load(string $rootPath, string $file = '.env'): void`, `Env::get(string $key, ?string $default = null): ?string`; `Database::pdo(): PDO`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase
{
    public function testPdoReturnsSameInstanceAndCanQuery(): void
    {
        $pdo1 = Database::pdo();
        $pdo2 = Database::pdo();

        $this->assertSame($pdo1, $pdo2);
        $this->assertSame(1, (int) $pdo1->query('SELECT 1')->fetchColumn());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/DatabaseTest.php`
Expected: FAIL — class `App\Core\Database` not found.

- [ ] **Step 3: Implement `Env`**

```php
<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

final class Env
{
    public static function load(string $rootPath, string $file = '.env'): void
    {
        Dotenv::createImmutable($rootPath, $file)->load();
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);
        return $value === false || $value === null ? $default : (string) $value;
    }
}
```

- [ ] **Step 4: Implement `Database`**

```php
<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $instance = null;

    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            $host = Env::get('DB_HOST', '127.0.0.1');
            $name = Env::get('DB_NAME', 'training_center');
            $user = Env::get('DB_USER', 'root');
            $pass = Env::get('DB_PASS', '');

            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/DatabaseTest.php`
Expected: PASS (requires a reachable MySQL server per `.env.testing`; create an empty `training_center_test` database first: `CREATE DATABASE training_center_test CHARACTER SET utf8mb4;`).

- [ ] **Step 6: Commit**

```bash
git add backend/src/Core/Env.php backend/src/Core/Database.php backend/tests/Unit/DatabaseTest.php
git commit -m "feat: add Env and Database core classes"
```

---

### Task 3: Database schema migration + seeders

**Files:**
- Create: `backend/database/migrations/001_create_schema.sql`
- Create: `backend/database/migrate.php`
- Create: `backend/database/seeders/seed.php`

**Interfaces:**
- Consumes: `App\Core\Database::pdo()`.
- Produces: all 10 tables from the spec, plus 3 seeded professions rows readable by later tasks/tests.

- [ ] **Step 1: Write `001_create_schema.sql`**

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(191) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
    language ENUM('uz','ru') NOT NULL DEFAULT 'uz',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE professions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_uz VARCHAR(191) NOT NULL,
    name_ru VARCHAR(191) NOT NULL,
    description_uz TEXT NOT NULL,
    description_ru TEXT NOT NULL,
    duration_days INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    image_url VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(191) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    profession_id INT NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (profession_id) REFERENCES professions(id),
    FOREIGN KEY (created_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `groups` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profession_id INT NOT NULL,
    teacher_id INT NULL,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    FOREIGN KEY (profession_id) REFERENCES professions(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    lesson_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(50) NOT NULL,
    FOREIGN KEY (group_id) REFERENCES `groups`(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    status ENUM('active','completed','dropped') NOT NULL DEFAULT 'active',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (group_id) REFERENCES `groups`(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profession_id INT NOT NULL,
    title_uz VARCHAR(191) NOT NULL,
    title_ru VARCHAR(191) NOT NULL,
    passing_score INT NOT NULL DEFAULT 70,
    FOREIGN KEY (profession_id) REFERENCES professions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    text_uz TEXT NOT NULL,
    text_ru TEXT NOT NULL,
    FOREIGN KEY (test_id) REFERENCES tests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    text_uz VARCHAR(255) NOT NULL,
    text_ru VARCHAR(255) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE test_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_id INT NOT NULL,
    score INT NOT NULL,
    passed TINYINT(1) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (test_id) REFERENCES tests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    profession_id INT NOT NULL,
    certificate_number VARCHAR(50) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    pdf_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (profession_id) REFERENCES professions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: Write `database/migrate.php`**

```php
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
```

- [ ] **Step 3: Run migrations against dev and test databases**

Run: `php database/migrate.php .env` and `php database/migrate.php .env.testing`
Expected: "Migrations complete." with no errors on both.

- [ ] **Step 4: Write `database/seeders/seed.php`**

```php
<?php

declare(strict_types=1);

require dirname(__DIR__, 1) . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

$envFile = $argv[1] ?? '.env';
Env::load(dirname(__DIR__), $envFile);

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
```

- [ ] **Step 5: Run seeder against dev and test databases**

Run: `php database/seeders/seed.php .env` and `php database/seeders/seed.php .env.testing`
Expected: "Seeded 3 professions." on both.

- [ ] **Step 6: Commit**

```bash
git add backend/database
git commit -m "feat: add DB schema migration, migration runner, and profession seeder"
```

---

### Task 4: `Request`, `Response`, `Router` core classes

**Files:**
- Create: `backend/src/Core/Request.php`
- Create: `backend/src/Core/Response.php`
- Create: `backend/src/Core/Router.php`
- Test: `backend/tests/Unit/RouterTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `Request` (built from PHP globals via `Request::fromGlobals()`, or manually via constructor for tests), `Response::json()`/`Response::error()`, `Router::get/post(string $path, callable $handler)`, `Router::dispatch(Request $request): void`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesMatchingRouteWithParams(): void
    {
        $router = new Router();
        $router->get('/professions/{id}', function (Request $req) {
            return ['id' => $req->param('id')];
        });

        $request = new Request('GET', '/professions/42', [], []);
        $result = $router->dispatch($request);

        $this->assertSame(['id' => '42'], $result);
    }

    public function testReturnsNullWhenNoRouteMatches(): void
    {
        $router = new Router();
        $request = new Request('GET', '/unknown', [], []);

        $this->assertNull($router->dispatch($request));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/RouterTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Implement `Request`**

```php
<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @var array<string,string> */
    private array $params = [];

    /** @var array<string,mixed>|null */
    private ?array $userClaims = null;

    /**
     * @param array<string,string> $headers
     * @param array<string,mixed> $body
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $headers = [],
        private readonly array $body = []
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string) $value;
            }
        }

        $raw = file_get_contents('php://input') ?: '';
        $body = json_decode($raw, true);
        $body = is_array($body) ? $body : [];

        return new self($method, $path, $headers, $body);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function jsonBody(): array
    {
        return $this->body;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtoupper($name)] ?? null;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $name): ?string
    {
        return $this->params[$name] ?? null;
    }

    public function setUser(array $claims): void
    {
        $this->userClaims = $claims;
    }

    public function user(): ?array
    {
        return $this->userClaims;
    }
}
```

- [ ] **Step 4: Implement `Response`**

```php
<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function error(string $code, string $message, int $status): void
    {
        self::json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
```

- [ ] **Step 5: Implement `Router`**

```php
<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, paramNames:array<int,string>, handler:callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $paramNames = [];
        $pattern = preg_replace_callback('#\{(\w+)\}#', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $path);

        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $pattern . '$#',
            'paramNames' => $paramNames,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): mixed
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (preg_match($route['pattern'], $request->path(), $matches)) {
                array_shift($matches);
                $params = array_combine($route['paramNames'], $matches);
                $request->setParams($params ?: []);

                return ($route['handler'])($request);
            }
        }

        return null;
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/RouterTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/src/Core/Request.php backend/src/Core/Response.php backend/src/Core/Router.php backend/tests/Unit/RouterTest.php
git commit -m "feat: add Request, Response, and Router core classes"
```

---

### Task 5: `Auth` (password hashing + JWT)

**Files:**
- Create: `backend/src/Core/Auth.php`
- Test: `backend/tests/Unit/AuthTest.php`

**Interfaces:**
- Consumes: `Env::get()`.
- Produces: `Auth::hashPassword(string): string`, `Auth::verifyPassword(string, string): bool`, `Auth::issueToken(int $userId, string $role): string`, `Auth::verifyToken(string $token): ?array`.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Auth;
use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    public function testHashAndVerifyPassword(): void
    {
        $hash = Auth::hashPassword('secret123');

        $this->assertTrue(Auth::verifyPassword('secret123', $hash));
        $this->assertFalse(Auth::verifyPassword('wrong', $hash));
    }

    public function testIssueAndVerifyToken(): void
    {
        $token = Auth::issueToken(7, 'student');
        $claims = Auth::verifyToken($token);

        $this->assertSame(7, $claims['user_id']);
        $this->assertSame('student', $claims['role']);
    }

    public function testVerifyTokenReturnsNullForGarbage(): void
    {
        $this->assertNull(Auth::verifyToken('not-a-real-token'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/AuthTest.php`
Expected: FAIL — class `App\Core\Auth` not found.

- [ ] **Step 3: Implement `Auth`**

```php
<?php

declare(strict_types=1);

namespace App\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class Auth
{
    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function issueToken(int $userId, string $role): string
    {
        $ttlDays = (int) Env::get('JWT_TTL_DAYS', '30');

        $payload = [
            'user_id' => $userId,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + $ttlDays * 86400,
        ];

        return JWT::encode($payload, (string) Env::get('JWT_SECRET'), 'HS256');
    }

    /**
     * @return array{user_id:int, role:string}|null
     */
    public static function verifyToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key((string) Env::get('JWT_SECRET'), 'HS256'));
            return [
                'user_id' => (int) $decoded->user_id,
                'role' => (string) $decoded->role,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/AuthTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add backend/src/Core/Auth.php backend/tests/Unit/AuthTest.php
git commit -m "feat: add Auth class for password hashing and JWT"
```

---

### Task 6: `ProfessionRepository` + `GET /professions`

**Files:**
- Create: `backend/src/Repositories/ProfessionRepository.php`
- Create: `backend/src/Controllers/Api/ProfessionController.php`
- Modify: `backend/public/index.php` (wire router + this route)
- Test: `backend/tests/Feature/ProfessionsEndpointTest.php`

**Interfaces:**
- Consumes: `Database::pdo()`, `Router::get()`, `Response::json()`.
- Produces: `ProfessionRepository::all(): array<int, array>` (each row: id, name_uz, name_ru, description_uz, description_ru, duration_days, price, image_url).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Router;
use App\Core\Request;
use App\Controllers\Api\ProfessionController;
use PHPUnit\Framework\TestCase;

final class ProfessionsEndpointTest extends TestCase
{
    public function testListsSeededProfessions(): void
    {
        $router = new Router();
        (new ProfessionController())->register($router);

        $request = new Request('GET', '/api/v1/professions', [], []);
        $result = $router->dispatch($request);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result['professions']));
        $this->assertArrayHasKey('name_uz', $result['professions'][0]);
    }
}
```

(Controllers return the array to dispatch in tests instead of echoing directly — see Step 3's `handle()` design — so tests can assert on data without capturing stdout.)

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/ProfessionsEndpointTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Implement `ProfessionRepository`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ProfessionRepository
{
    public function all(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT id, name_uz, name_ru, description_uz, description_ru, duration_days, price, image_url FROM professions ORDER BY id'
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name_uz, name_ru, description_uz, description_ru, duration_days, price, image_url FROM professions WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
```

- [ ] **Step 4: Implement `ProfessionController`**

Controllers expose `register(Router $router)` to wire their routes, and each handler returns a plain array. `public/index.php` is the only place that turns that array into an actual `Response::json()` call — this keeps controllers testable without output buffering.

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Repositories\ProfessionRepository;

final class ProfessionController
{
    public function __construct(private readonly ProfessionRepository $repository = new ProfessionRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/professions', fn (Request $req) => $this->index($req));
    }

    private function index(Request $request): array
    {
        return ['professions' => $this->repository->all()];
    }
}
```

- [ ] **Step 5: Wire it in `public/index.php`**

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Controllers\Api\ProfessionController;

Env::load(dirname(__DIR__));

$router = new Router();
(new ProfessionController())->register($router);

$request = Request::fromGlobals();
$result = $router->dispatch($request);

if ($result === null) {
    Response::error('NOT_FOUND', 'Route not found', 404);
    return;
}

Response::json($result);
```

- [ ] **Step 6: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/ProfessionsEndpointTest.php`
Expected: PASS (requires seeded `.env.testing` DB from Task 3).

- [ ] **Step 7: Manual smoke test**

Run: `php -S localhost:8080 -t public` then in another terminal `curl http://localhost:8080/api/v1/professions`
Expected: JSON with 3 professions.

- [ ] **Step 8: Commit**

```bash
git add backend/src/Repositories/ProfessionRepository.php backend/src/Controllers/Api/ProfessionController.php backend/public/index.php backend/tests/Feature/ProfessionsEndpointTest.php
git commit -m "feat: add GET /api/v1/professions endpoint"
```

---

### Task 7: `UserRepository`, `ApplicationRepository` + `POST /applications`

**Files:**
- Create: `backend/src/Repositories/UserRepository.php`
- Create: `backend/src/Repositories/ApplicationRepository.php`
- Create: `backend/src/Controllers/Api/ApplicationController.php`
- Modify: `backend/public/index.php`
- Test: `backend/tests/Feature/ApplicationsEndpointTest.php`

**Interfaces:**
- Consumes: `Database::pdo()`, `Auth::hashPassword()`.
- Produces: `UserRepository::create(string $fullName, string $phone, string $passwordHash, string $role): int` (returns new id), `UserRepository::findByPhone(string $phone): ?array`, `UserRepository::find(int $id): ?array`; `ApplicationRepository::create(string $fullName, string $phone, int $professionId): int`, `ApplicationRepository::approve(int $applicationId, string $temporaryPassword): array{user_id:int, phone:string}` (creates the `users` row and links `applications.created_user_id` — used later by the admin panel sub-project, exercised here by its unit test).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Router;
use App\Core\Request;
use App\Controllers\Api\ApplicationController;
use App\Core\Database;
use PHPUnit\Framework\TestCase;

final class ApplicationsEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        Database::pdo()->exec('DELETE FROM applications');
    }

    public function testCreatesApplicationForExistingProfession(): void
    {
        $professionId = (int) Database::pdo()->query('SELECT id FROM professions LIMIT 1')->fetchColumn();

        $router = new Router();
        (new ApplicationController())->register($router);

        $request = new Request('POST', '/api/v1/applications', [], [
            'full_name' => 'Aziz Karimov',
            'phone' => '+998901112233',
            'profession_id' => $professionId,
        ]);

        $result = $router->dispatch($request);

        $this->assertArrayHasKey('id', $result);

        $row = Database::pdo()->query('SELECT status FROM applications WHERE id = ' . (int) $result['id'])->fetch();
        $this->assertSame('pending', $row['status']);
    }

    public function testRejectsMissingFields(): void
    {
        $router = new Router();
        (new ApplicationController())->register($router);

        $request = new Request('POST', '/api/v1/applications', [], ['full_name' => 'No Phone']);
        $result = $router->dispatch($request);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame(422, $result['status']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/ApplicationsEndpointTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Implement `UserRepository`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class UserRepository
{
    public function create(string $fullName, string $phone, string $passwordHash, string $role = 'student'): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (full_name, phone, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$fullName, $phone, $passwordHash, $role]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE phone = ?');
        $stmt->execute([$phone]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
```

- [ ] **Step 4: Implement `ApplicationRepository`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;

final class ApplicationRepository
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function create(string $fullName, string $phone, int $professionId): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO applications (full_name, phone, profession_id) VALUES (?, ?, ?)'
        );
        $stmt->execute([$fullName, $phone, $professionId]);

        return (int) Database::pdo()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM applications WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array{user_id:int, phone:string}
     */
    public function approve(int $applicationId, string $temporaryPassword): array
    {
        $application = $this->find($applicationId);
        if ($application === null || $application['status'] !== 'pending') {
            throw new \RuntimeException('Application not pending');
        }

        $userId = $this->users->create(
            $application['full_name'],
            $application['phone'],
            Auth::hashPassword($temporaryPassword),
            'student'
        );

        $stmt = Database::pdo()->prepare(
            "UPDATE applications SET status = 'approved', created_user_id = ? WHERE id = ?"
        );
        $stmt->execute([$userId, $applicationId]);

        return ['user_id' => $userId, 'phone' => $application['phone']];
    }
}
```

- [ ] **Step 5: Implement `ApplicationController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Repositories\ApplicationRepository;

final class ApplicationController
{
    public function __construct(private readonly ApplicationRepository $repository = new ApplicationRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->post('/api/v1/applications', fn (Request $req) => $this->store($req));
    }

    private function store(Request $request): array
    {
        $body = $request->jsonBody();
        $fullName = trim((string) ($body['full_name'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));
        $professionId = (int) ($body['profession_id'] ?? 0);

        if ($fullName === '' || $phone === '' || $professionId <= 0) {
            return [
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'full_name, phone, profession_id required'],
                'status' => 422,
            ];
        }

        $id = $this->repository->create($fullName, $phone, $professionId);

        return ['id' => $id, 'status' => 201];
    }
}
```

- [ ] **Step 6: Update `public/index.php` to handle the `status` field and register the new route**

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Controllers\Api\ProfessionController;
use App\Controllers\Api\ApplicationController;

Env::load(dirname(__DIR__));

$router = new Router();
(new ProfessionController())->register($router);
(new ApplicationController())->register($router);

$request = Request::fromGlobals();
$result = $router->dispatch($request);

if ($result === null) {
    Response::error('NOT_FOUND', 'Route not found', 404);
    return;
}

if (isset($result['error'])) {
    Response::error($result['error']['code'], $result['error']['message'], $result['status']);
    return;
}

$status = $result['status'] ?? 200;
unset($result['status']);
Response::json($result, $status);
```

- [ ] **Step 7: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/ApplicationsEndpointTest.php`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add backend/src/Repositories/UserRepository.php backend/src/Repositories/ApplicationRepository.php backend/src/Controllers/Api/ApplicationController.php backend/public/index.php backend/tests/Feature/ApplicationsEndpointTest.php
git commit -m "feat: add POST /api/v1/applications endpoint with approve() for future admin use"
```

---

### Task 8: `POST /auth/login` + `AuthMiddleware` + `GET /me`

**Files:**
- Create: `backend/src/Controllers/Api/AuthController.php`
- Create: `backend/src/Middleware/AuthMiddleware.php`
- Create: `backend/src/Controllers/Api/MeController.php`
- Modify: `backend/public/index.php`
- Test: `backend/tests/Feature/AuthEndpointTest.php`
- Test: `backend/tests/Feature/MeEndpointTest.php`

**Interfaces:**
- Consumes: `UserRepository::findByPhone()`, `Auth::verifyPassword()`, `Auth::issueToken()`, `Auth::verifyToken()`.
- Produces: `AuthMiddleware::authenticate(Request $request): ?array` (returns claims or null; also calls `$request->setUser($claims)` on success) — reused by every protected controller (`Me`, `Test`, `Certificate`).

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/AuthEndpointTest.php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\AuthController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class AuthEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998900000001'");
        (new UserRepository())->create('Test Student', '+998900000001', Auth::hashPassword('pass1234'), 'student');
    }

    public function testLoginWithCorrectCredentialsReturnsToken(): void
    {
        $router = new Router();
        (new AuthController())->register($router);

        $request = new Request('POST', '/api/v1/auth/login', [], [
            'phone' => '+998900000001',
            'password' => 'pass1234',
        ]);

        $result = $router->dispatch($request);

        $this->assertArrayHasKey('token', $result);
        $this->assertNotNull(Auth::verifyToken($result['token']));
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        $router = new Router();
        (new AuthController())->register($router);

        $request = new Request('POST', '/api/v1/auth/login', [], [
            'phone' => '+998900000001',
            'password' => 'wrong',
        ]);

        $result = $router->dispatch($request);

        $this->assertSame(401, $result['status']);
    }
}
```

```php
<?php
// tests/Feature/MeEndpointTest.php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\MeController;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class MeEndpointTest extends TestCase
{
    public function testReturnsProfileForValidToken(): void
    {
        Database::pdo()->exec("DELETE FROM users WHERE phone = '+998900000002'");
        $userId = (new UserRepository())->create('Me Student', '+998900000002', Auth::hashPassword('pass1234'), 'student');
        $token = Auth::issueToken($userId, 'student');

        $router = new Router();
        (new MeController())->register($router);

        $request = new Request('GET', '/api/v1/me', ['AUTHORIZATION' => "Bearer {$token}"], []);
        $result = $router->dispatch($request);

        $this->assertSame('Me Student', $result['full_name']);
    }

    public function testRejectsMissingToken(): void
    {
        $router = new Router();
        (new MeController())->register($router);

        $request = new Request('GET', '/api/v1/me', [], []);
        $result = $router->dispatch($request);

        $this->assertSame(401, $result['status']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit tests/Feature/AuthEndpointTest.php tests/Feature/MeEndpointTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Implement `AuthController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Router;
use App\Repositories\UserRepository;

final class AuthController
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->post('/api/v1/auth/login', fn (Request $req) => $this->login($req));
    }

    private function login(Request $request): array
    {
        $body = $request->jsonBody();
        $phone = trim((string) ($body['phone'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        $user = $this->users->findByPhone($phone);

        if ($user === null || !Auth::verifyPassword($password, $user['password_hash'])) {
            return [
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'Phone or password incorrect'],
                'status' => 401,
            ];
        }

        return ['token' => Auth::issueToken((int) $user['id'], $user['role'])];
    }
}
```

- [ ] **Step 4: Implement `AuthMiddleware`**

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;

final class AuthMiddleware
{
    public static function authenticate(Request $request): ?array
    {
        $header = $request->header('AUTHORIZATION') ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);
        $claims = Auth::verifyToken($token);

        if ($claims !== null) {
            $request->setUser($claims);
        }

        return $claims;
    }
}
```

- [ ] **Step 5: Implement `MeController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Repositories\UserRepository;

final class MeController
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/me', fn (Request $req) => $this->show($req));
    }

    private function show(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        $user = $this->users->find($claims['user_id']);
        unset($user['password_hash']);

        return $user;
    }
}
```

- [ ] **Step 6: Register both routes in `public/index.php`**

Add `use App\Controllers\Api\AuthController;` and `use App\Controllers\Api\MeController;`, then:

```php
(new AuthController())->register($router);
(new MeController())->register($router);
```

(inserted alongside the existing `ProfessionController`/`ApplicationController` registrations, before `Request::fromGlobals()`).

- [ ] **Step 7: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/AuthEndpointTest.php tests/Feature/MeEndpointTest.php`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add backend/src/Controllers/Api/AuthController.php backend/src/Middleware/AuthMiddleware.php backend/src/Controllers/Api/MeController.php backend/public/index.php backend/tests/Feature/AuthEndpointTest.php backend/tests/Feature/MeEndpointTest.php
git commit -m "feat: add login, JWT auth middleware, and GET /api/v1/me"
```

---

### Task 9: `ScheduleRepository` + `GET /me/schedule`

**Files:**
- Create: `backend/src/Repositories/ScheduleRepository.php`
- Modify: `backend/src/Controllers/Api/MeController.php`
- Modify: `backend/public/index.php` (no change needed if route added in same controller — see Step 3)
- Test: `backend/tests/Feature/MeEndpointTest.php` (extend)

**Interfaces:**
- Consumes: `Database::pdo()`, `AuthMiddleware::authenticate()`.
- Produces: `ScheduleRepository::forUser(int $userId): array<int, array>` (each row: lesson_date, start_time, end_time, room, group name) — via the student's active `enrollments` row.

- [ ] **Step 1: Extend `MeEndpointTest` with the failing test**

```php
    public function testScheduleReturnsLessonsForEnrolledGroup(): void
    {
        $pdo = \App\Core\Database::pdo();
        $pdo->exec("DELETE FROM schedule");
        $pdo->exec("DELETE FROM enrollments");
        $pdo->exec("DELETE FROM `groups`");
        $pdo->exec("DELETE FROM users WHERE phone = '+998900000003'");

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $userId = (new UserRepository())->create('Schedule Student', '+998900000003', Auth::hashPassword('pass1234'), 'student');

        $pdo->prepare('INSERT INTO `groups` (id, profession_id, name, start_date, end_date) VALUES (1, ?, ?, CURDATE(), CURDATE())')
            ->execute([$professionId, 'Test Group']);
        $pdo->prepare('INSERT INTO enrollments (user_id, group_id, status) VALUES (?, 1, "active")')->execute([$userId]);
        $pdo->prepare("INSERT INTO schedule (group_id, lesson_date, start_time, end_time, room) VALUES (1, CURDATE(), '09:00:00', '11:00:00', '101')")->execute();

        $token = Auth::issueToken($userId, 'student');

        $router = new Router();
        (new MeController())->register($router);

        $request = new Request('GET', '/api/v1/me/schedule', ['AUTHORIZATION' => "Bearer {$token}"], []);
        $result = $router->dispatch($request);

        $this->assertCount(1, $result['schedule']);
        $this->assertSame('101', $result['schedule'][0]['room']);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/MeEndpointTest.php`
Expected: FAIL — no `/api/v1/me/schedule` route.

- [ ] **Step 3: Implement `ScheduleRepository`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ScheduleRepository
{
    public function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT s.lesson_date, s.start_time, s.end_time, s.room, g.name AS group_name
             FROM schedule s
             JOIN `groups` g ON g.id = s.group_id
             JOIN enrollments e ON e.group_id = g.id
             WHERE e.user_id = ? AND e.status = 'active'
             ORDER BY s.lesson_date, s.start_time"
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }
}
```

- [ ] **Step 4: Add the route to `MeController`**

```php
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly \App\Repositories\ScheduleRepository $scheduleRepository = new \App\Repositories\ScheduleRepository()
    ) {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/me', fn (Request $req) => $this->show($req));
        $router->get('/api/v1/me/schedule', fn (Request $req) => $this->schedule($req));
    }

    private function schedule(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        return ['schedule' => $this->scheduleRepository->forUser($claims['user_id'])];
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/MeEndpointTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add backend/src/Repositories/ScheduleRepository.php backend/src/Controllers/Api/MeController.php backend/tests/Feature/MeEndpointTest.php
git commit -m "feat: add GET /api/v1/me/schedule endpoint"
```

---

### Task 10: `TestRepository` + `GET /me/tests` + `POST /me/tests/{id}/submit` (scoring)

**Files:**
- Create: `backend/src/Repositories/TestRepository.php`
- Create: `backend/src/Controllers/Api/TestController.php`
- Modify: `backend/public/index.php`
- Test: `backend/tests/Unit/TestScoringTest.php`
- Test: `backend/tests/Feature/TestSubmitEndpointTest.php`

**Interfaces:**
- Consumes: `Database::pdo()`, `AuthMiddleware::authenticate()`.
- Produces: `TestRepository::availableForUser(int $userId): array` (tests for the user's enrolled profession not yet attempted), `TestRepository::questionsWithAnswers(int $testId): array`, `TestRepository::score(int $testId, array $submittedAnswerIds): array{score:int, passed:bool}` (pure function, no DB writes — used directly by the unit test), `TestRepository::recordAttempt(int $userId, int $testId, int $score, bool $passed): int`.

- [ ] **Step 1: Write the failing unit test for scoring**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Database;
use App\Repositories\TestRepository;
use PHPUnit\Framework\TestCase;

final class TestScoringTest extends TestCase
{
    private int $testId;
    private array $correctAnswerIds = [];

    protected function setUp(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (id, profession_id, title_uz, title_ru, passing_score) VALUES (1, ?, "T", "T", 70)')
            ->execute([$professionId]);
        $this->testId = 1;

        for ($i = 1; $i <= 2; $i++) {
            $pdo->prepare('INSERT INTO questions (id, test_id, text_uz, text_ru) VALUES (?, 1, "Q", "Q")')->execute([$i]);
            $pdo->prepare('INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, "A", "A", 1)')->execute([$i]);
            $this->correctAnswerIds[] = (int) Database::pdo()->lastInsertId();
            $pdo->prepare('INSERT INTO answers (question_id, text_uz, text_ru, is_correct) VALUES (?, "B", "B", 0)')->execute([$i]);
        }
    }

    public function testAllCorrectAnswersGivesFullScoreAndPasses(): void
    {
        $repo = new TestRepository();
        $result = $repo->score($this->testId, $this->correctAnswerIds);

        $this->assertSame(100, $result['score']);
        $this->assertTrue($result['passed']);
    }

    public function testZeroCorrectAnswersFails(): void
    {
        $repo = new TestRepository();
        $result = $repo->score($this->testId, [999999, 999998]);

        $this->assertSame(0, $result['score']);
        $this->assertFalse($result['passed']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/TestScoringTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement `TestRepository`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class TestRepository
{
    public function availableForUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.title_uz, t.title_ru, t.passing_score
             FROM tests t
             JOIN professions p ON p.id = t.profession_id
             JOIN `groups` g ON g.profession_id = p.id
             JOIN enrollments e ON e.group_id = g.id
             WHERE e.user_id = ? AND e.status = 'active'
               AND t.id NOT IN (SELECT test_id FROM test_attempts WHERE user_id = ? AND passed = 1)
             GROUP BY t.id"
        );
        $stmt->execute([$userId, $userId]);

        return $stmt->fetchAll();
    }

    public function questionsWithAnswers(int $testId): array
    {
        $stmt = Database::pdo()->prepare('SELECT id, text_uz, text_ru FROM questions WHERE test_id = ?');
        $stmt->execute([$testId]);
        $questions = $stmt->fetchAll();

        foreach ($questions as &$question) {
            $answerStmt = Database::pdo()->prepare('SELECT id, text_uz, text_ru FROM answers WHERE question_id = ?');
            $answerStmt->execute([$question['id']]);
            $question['answers'] = $answerStmt->fetchAll();
        }

        return $questions;
    }

    /**
     * @param int[] $submittedAnswerIds one selected answer id per question
     * @return array{score:int, passed:bool}
     */
    public function score(int $testId, array $submittedAnswerIds): array
    {
        $pdo = Database::pdo();

        $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE test_id = ?');
        $totalStmt->execute([$testId]);
        $total = (int) $totalStmt->fetchColumn();

        if ($total === 0 || $submittedAnswerIds === []) {
            return ['score' => 0, 'passed' => false];
        }

        $placeholders = implode(',', array_fill(0, count($submittedAnswerIds), '?'));
        $correctStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM answers WHERE id IN ({$placeholders}) AND is_correct = 1"
        );
        $correctStmt->execute($submittedAnswerIds);
        $correct = (int) $correctStmt->fetchColumn();

        $passingScoreStmt = $pdo->prepare('SELECT passing_score FROM tests WHERE id = ?');
        $passingScoreStmt->execute([$testId]);
        $passingScore = (int) $passingScoreStmt->fetchColumn();

        $score = (int) round(($correct / $total) * 100);

        return ['score' => $score, 'passed' => $score >= $passingScore];
    }

    public function recordAttempt(int $userId, int $testId, int $score, bool $passed): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO test_attempts (user_id, test_id, score, passed) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $testId, $score, $passed ? 1 : 0]);

        return (int) Database::pdo()->lastInsertId();
    }
}
```

- [ ] **Step 4: Run unit test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/TestScoringTest.php`
Expected: PASS

- [ ] **Step 5: Write the failing feature test for the endpoints**

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

final class TestSubmitEndpointTest extends TestCase
{
    public function testSubmitReturnsScoreAndRecordsAttempt(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM test_attempts');
        $pdo->exec('DELETE FROM answers');
        $pdo->exec('DELETE FROM questions');
        $pdo->exec('DELETE FROM tests');
        $pdo->exec("DELETE FROM users WHERE phone = '+998900000004'");

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $pdo->prepare('INSERT INTO tests (id, profession_id, title_uz, title_ru, passing_score) VALUES (1, ?, "T", "T", 50)')
            ->execute([$professionId]);
        $pdo->exec('INSERT INTO questions (id, test_id, text_uz, text_ru) VALUES (1, 1, "Q", "Q")');
        $pdo->exec('INSERT INTO answers (id, question_id, text_uz, text_ru, is_correct) VALUES (1, 1, "A", "A", 1)');

        $userId = (new UserRepository())->create('Test Taker', '+998900000004', Auth::hashPassword('pass1234'), 'student');
        $token = Auth::issueToken($userId, 'student');

        $router = new Router();
        (new TestController())->register($router);

        $request = new Request('POST', '/api/v1/me/tests/1/submit', ['AUTHORIZATION' => "Bearer {$token}"], [
            'answers' => [1],
        ]);

        $result = $router->dispatch($request);

        $this->assertSame(100, $result['score']);
        $this->assertTrue($result['passed']);

        $count = (int) $pdo->query("SELECT COUNT(*) FROM test_attempts WHERE user_id = {$userId}")->fetchColumn();
        $this->assertSame(1, $count);
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/TestSubmitEndpointTest.php`
Expected: FAIL — `TestController` not found.

- [ ] **Step 7: Implement `TestController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Repositories\TestRepository;

final class TestController
{
    public function __construct(private readonly TestRepository $repository = new TestRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/me/tests', fn (Request $req) => $this->index($req));
        $router->post('/api/v1/me/tests/{id}/submit', fn (Request $req) => $this->submit($req));
    }

    private function index(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        return ['tests' => $this->repository->availableForUser($claims['user_id'])];
    }

    private function submit(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        $testId = (int) $request->param('id');
        $answerIds = array_map('intval', $request->jsonBody()['answers'] ?? []);

        $result = $this->repository->score($testId, $answerIds);
        $this->repository->recordAttempt($claims['user_id'], $testId, $result['score'], $result['passed']);

        return $result;
    }
}
```

- [ ] **Step 8: Register `TestController` in `public/index.php`**

Add `use App\Controllers\Api\TestController;` and `(new TestController())->register($router);`.

- [ ] **Step 9: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/TestSubmitEndpointTest.php`
Expected: PASS

- [ ] **Step 10: Commit**

```bash
git add backend/src/Repositories/TestRepository.php backend/src/Controllers/Api/TestController.php backend/public/index.php backend/tests/Unit/TestScoringTest.php backend/tests/Feature/TestSubmitEndpointTest.php
git commit -m "feat: add test listing, submission, and scoring endpoints"
```

---

### Task 11: `CertificateRepository`, `CertificatePdfService` + certificate endpoints

**Files:**
- Create: `backend/src/Repositories/CertificateRepository.php`
- Create: `backend/src/Services/CertificatePdfService.php`
- Create: `backend/src/Controllers/Api/CertificateController.php`
- Modify: `backend/public/index.php`
- Test: `backend/tests/Feature/CertificateEndpointTest.php`

**Interfaces:**
- Consumes: `Database::pdo()`, `AuthMiddleware::authenticate()`, `dompdf/dompdf`.
- Produces: `CertificateRepository::forUser(int $userId): array`, `CertificateRepository::issue(int $userId, int $professionId): array` (generates certificate number, calls `CertificatePdfService::generate()`, inserts row, returns it), `CertificatePdfService::generate(array $data): string` (writes a PDF file under `storage/certificates/` and returns its path).

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Controllers\Api\CertificateController;
use App\Repositories\CertificateRepository;
use App\Repositories\UserRepository;
use PHPUnit\Framework\TestCase;

final class CertificateEndpointTest extends TestCase
{
    public function testIssuedCertificateAppearsInListAndDownloads(): void
    {
        $pdo = Database::pdo();
        $pdo->exec('DELETE FROM certificates');
        $pdo->exec("DELETE FROM users WHERE phone = '+998900000005'");

        $professionId = (int) $pdo->query('SELECT id FROM professions LIMIT 1')->fetchColumn();
        $userId = (new UserRepository())->create('Cert Student', '+998900000005', Auth::hashPassword('pass1234'), 'student');

        $certificate = (new CertificateRepository())->issue($userId, $professionId);
        $this->assertFileExists($certificate['pdf_path']);

        $token = Auth::issueToken($userId, 'student');
        $router = new Router();
        (new CertificateController())->register($router);

        $listRequest = new Request('GET', '/api/v1/me/certificates', ['AUTHORIZATION' => "Bearer {$token}"], []);
        $listResult = $router->dispatch($listRequest);
        $this->assertCount(1, $listResult['certificates']);

        $downloadRequest = new Request('GET', "/api/v1/certificates/{$certificate['id']}/download", ['AUTHORIZATION' => "Bearer {$token}"], []);
        $downloadResult = $router->dispatch($downloadRequest);
        $this->assertFileExists($downloadResult['file_path']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/CertificateEndpointTest.php`
Expected: FAIL — classes not found.

- [ ] **Step 3: Implement `CertificatePdfService`**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;

final class CertificatePdfService
{
    public function generate(array $data): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/certificates';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $html = sprintf(
            '<h1>Sertifikat</h1><p>%s</p><p>%s</p><p>%s</p><p>%s</p>',
            htmlspecialchars($data['full_name']),
            htmlspecialchars($data['profession_name']),
            htmlspecialchars($data['issue_date']),
            htmlspecialchars($data['certificate_number'])
        );

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();

        $path = $dir . '/' . $data['certificate_number'] . '.pdf';
        file_put_contents($path, $dompdf->output());

        return $path;
    }
}
```

- [ ] **Step 4: Implement `CertificateRepository`**

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\CertificatePdfService;

final class CertificateRepository
{
    public function __construct(private readonly CertificatePdfService $pdfService = new CertificatePdfService())
    {
    }

    public function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM certificates WHERE user_id = ? ORDER BY issue_date DESC');
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM certificates WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function issue(int $userId, int $professionId): array
    {
        $pdo = Database::pdo();

        $user = (new UserRepository())->find($userId);
        $profession = (new ProfessionRepository())->find($professionId);

        $certificateNumber = 'CERT-' . date('Y') . '-' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT) . '-' . random_int(100, 999);
        $issueDate = date('Y-m-d');

        $pdfPath = $this->pdfService->generate([
            'full_name' => $user['full_name'],
            'profession_name' => $profession['name_uz'],
            'issue_date' => $issueDate,
            'certificate_number' => $certificateNumber,
        ]);

        $stmt = $pdo->prepare(
            'INSERT INTO certificates (user_id, profession_id, certificate_number, issue_date, pdf_path) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $professionId, $certificateNumber, $issueDate, $pdfPath]);

        return [
            'id' => (int) $pdo->lastInsertId(),
            'certificate_number' => $certificateNumber,
            'issue_date' => $issueDate,
            'pdf_path' => $pdfPath,
        ];
    }
}
```

- [ ] **Step 5: Implement `CertificateController`**

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Repositories\CertificateRepository;

final class CertificateController
{
    public function __construct(private readonly CertificateRepository $repository = new CertificateRepository())
    {
    }

    public function register(Router $router): void
    {
        $router->get('/api/v1/me/certificates', fn (Request $req) => $this->index($req));
        $router->get('/api/v1/certificates/{id}/download', fn (Request $req) => $this->download($req));
    }

    private function index(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        return ['certificates' => $this->repository->forUser($claims['user_id'])];
    }

    private function download(Request $request): array
    {
        $claims = AuthMiddleware::authenticate($request);
        if ($claims === null) {
            return ['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Missing or invalid token'], 'status' => 401];
        }

        $certificate = $this->repository->find((int) $request->param('id'));

        if ($certificate === null || (int) $certificate['user_id'] !== $claims['user_id']) {
            return ['error' => ['code' => 'NOT_FOUND', 'message' => 'Certificate not found'], 'status' => 404];
        }

        return ['file_path' => $certificate['pdf_path']];
    }
}
```

`public/index.php` currently assumes every successful result is JSON; the download route instead returns a `file_path`. Note this as a known follow-up for the real HTTP entry point (see Step 7) rather than solving it inside this plan's test-only dispatch path.

- [ ] **Step 6: Register `CertificateController` in `public/index.php`**

Add `use App\Controllers\Api\CertificateController;` and `(new CertificateController())->register($router);`.

- [ ] **Step 7: Handle file responses in `public/index.php`**

Replace the final response block with:

```php
if (isset($result['error'])) {
    Response::error($result['error']['code'], $result['error']['message'], $result['status']);
    return;
}

if (isset($result['file_path'])) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($result['file_path']) . '"');
    readfile($result['file_path']);
    return;
}

$status = $result['status'] ?? 200;
unset($result['status']);
Response::json($result, $status);
```

- [ ] **Step 8: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/CertificateEndpointTest.php`
Expected: PASS

- [ ] **Step 9: Add `storage/` to `.gitignore` and commit**

```bash
echo "backend/storage/" >> .gitignore
git add backend/src/Repositories/CertificateRepository.php backend/src/Services/CertificatePdfService.php backend/src/Controllers/Api/CertificateController.php backend/public/index.php backend/tests/Feature/CertificateEndpointTest.php .gitignore
git commit -m "feat: add certificate issuance, listing, and PDF download endpoints"
```

---

### Task 12: Full test suite run + README

**Files:**
- Create: `backend/README.md`

**Interfaces:**
- Consumes: nothing new — this task only verifies and documents what Tasks 1–11 built.

- [ ] **Step 1: Run the entire suite**

Run: `vendor/bin/phpunit`
Expected: All Unit and Feature tests PASS (0 failures).

- [ ] **Step 2: Write `backend/README.md`**

```markdown
# Training Center Backend

PHP + MySQL REST API for the training center Android app.

## Setup

1. `composer install`
2. Copy `.env.example` to `.env` and `.env.testing`, fill in DB credentials.
3. Create databases: `training_center` and `training_center_test` (utf8mb4).
4. Run migrations: `php database/migrate.php .env` and `php database/migrate.php .env.testing`
5. Seed professions: `php database/seeders/seed.php .env` and `php database/seeders/seed.php .env.testing`
6. Serve locally: `php -S localhost:8080 -t public`

## Tests

`vendor/bin/phpunit`

## API

See `docs/superpowers/specs/2026-09-22-backend-db-design.md` for the full endpoint list and DB schema.
```

- [ ] **Step 3: Commit**

```bash
git add backend/README.md
git commit -m "docs: add backend README with setup and test instructions"
```

---

## Explicitly out of scope for this plan

- `/admin/...` routes and pages (Veb-admin panel sub-project).
- Android app UI (Android ilova sub-project).
- Application-approval HTTP endpoint (the `ApplicationRepository::approve()` method exists and is usable, but no admin-facing route calls it yet — that route belongs to the admin panel sub-project).
