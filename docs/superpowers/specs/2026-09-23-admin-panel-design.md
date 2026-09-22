# Veb-admin panel — dizayn hujjati

**Loyiha:** "Учебный центр" — Ekskavator mashinisti, Burg'ilash stanogi mashinisti, Avtosamosval haydovchisi kasblari bo'yicha o'quv markazi uchun dastur va sayt (diplom loyihasi).

**Sub-loyiha:** 2/3 — Veb-admin panel (backend kod bazasi ustiga, `/admin/...`).

**Sana:** 2026-09-23

## 1. Maqsad va ko'lam

Markaz administratori (va o'qituvchilar) uchun server-rendered veb-panel: kelgan onlayn arizalarni ko'rib chiqish/tasdiqlash, guruhlar va dars jadvalini yuritish, o'qituvchilarni boshqarish, testlar/savollarni kiritish, va berilgan sertifikatlarni ko'rish.

Bu backend kod bazasi (`backend/`) ustiga qo'shiladi — bitta MySQL baza, bitta repository qatlami, lekin alohida kirish nuqtasi (`public/admin.php`) va autentifikatsiya usuli (JWT emas, PHP session).

Ko'lamdan tashqarida: Android ilova UI (3-sub-loyiha); SMS orqali parol yuborish (yo'q integratsiya — admin parolni o'zi kiritadi va talabaga og'zaki aytadi).

## 2. Arxitektura

```
                    ┌─────────────────────────┐
                    │      MySQL baza          │
                    └────────────┬─────────────┘
                                 │
                    ┌────────────┴─────────────┐
                    │   Repositories qatlami     │
                    │ (backend/src/Repositories) │
                    └──────┬─────────────┬──────┘
                           │             │
              ┌────────────┴───┐   ┌─────┴────────────┐
              │  public/index.php│  │  public/admin.php │
              │  JSON REST API  │   │  HTML sahifalar   │
              │  auth: JWT      │   │  auth: PHP session│
              └─────────────────┘   └───────────────────┘
```

- `public/admin.php` — admin panelning yagona kirish nuqtasi. O'zining `Router` nusxasini quradi (mavjud `App\Core\Router` qayta ishlatiladi), lekin handlerlar JSON array o'rniga to'g'ridan-to'g'ri HTML chiqaradi (`echo`) va `null` qaytaradi.
- **Autentifikatsiya:** PHP session (`session_start()`, `$_SESSION['admin_user_id']`, `$_SESSION['admin_role']`). `AdminAuthMiddleware::require(Request)` sessiyani tekshiradi, bo'lmasa `/admin/login`ga `Location` header bilan yo'naltiradi.
- **CSRF himoyasi:** har bir formada yashirin `csrf_token` maydoni (`$_SESSION['csrf_token']` bilan solishtiriladi); mos kelmasa 403.
- **Shablonlar:** oddiy PHP include asosidagi `View::render(string $template, array $data)` — freymvorksiz, `backend/resources/views/` papkasidagi `.php` fayllarni `layout.php` bilan o'raydi.
- **Dizayn:** Bootstrap 5 (CDN orqali `<link>`/`<script>`), light/dark tugma (`localStorage` + `prefers-color-scheme` boshlang'ich qiymat sifatida), uz/ru til almashtirish (sessionda saqlanadi, `backend/resources/lang/uz.php` va `ru.php` massivlari orqali).

## 3. Sahifalar va marshrutlar (`public/admin.php`)

**Autentifikatsiyasiz:**
- `GET /admin/login`, `POST /admin/login` — `{phone, password}`, `role IN ('admin','teacher')` bo'lgan userlar kira oladi
- `POST /admin/logout`

**Himoyalangan (session, faqat `role=admin`, agar boshqacha ko'rsatilmasa):**
- `GET /admin` — dashboard: kutilayotgan arizalar soni, faol guruhlar soni, shu oy berilgan sertifikatlar soni
- `GET /admin/applications` — ro'yxat, `?status=pending|approved|rejected` filtri
- `POST /admin/applications/{id}/approve` — forma: `password` (admin kiritadi) → `ApplicationRepository::approve($id, $password)`
- `POST /admin/applications/{id}/reject` — `applications.status = 'rejected'`
- `GET /admin/groups` — ro'yxat (kasb, o'qituvchi, talabalar soni)
- `GET /admin/groups/create`, `POST /admin/groups` — forma: kasb, o'qituvchi, nom, `start_date`/`end_date`, haftalik shablon (hafta kunlari checkbox + `start_time`/`end_time` + `room`) → guruh yaratiladi va `ScheduleGenerator` orqali butun kurs davomiyligiga darslar avtomatik yoziladi
- `GET /admin/groups/{id}` — detal: dars jadvali, biriktirilgan talabalar ro'yxati, "tasdiqlangan-lekin-guruhsiz" talabalarni shu guruhga qo'shish formasi (`enrollments` yozuvi yaratiladi)
- `GET /admin/teachers` — ro'yxat (`users` jadvalidan `role='teacher'`)
- `GET /admin/teachers/create`, `POST /admin/teachers` — F.I.Sh, telefon, parol → `UserRepository::create(..., 'teacher')`
- `GET /admin/tests` — ro'yxat (kasb bo'yicha guruhlangan)
- `GET /admin/tests/create`, `POST /admin/tests` — kasb, sarlavha (uz/ru), o'tish balli
- `GET /admin/tests/{id}/questions`, `POST /admin/tests/{id}/questions` — savol matni (uz/ru) + kamida 2 ta javob varianti (uz/ru), bittasi "to'g'ri" deb belgilanadi
- `GET /admin/certificates` — barcha berilgan sertifikatlar (talaba F.I.Sh, kasb, sana, raqam), PDF yuklab olish havolasi (`/api/v1/certificates/{id}/download` — mavjud API endpointi qayta ishlatiladi, lekin admin session orqali emas, shuning uchun admin panel PDF faylni to'g'ridan-to'g'ri o'zi xizmat qiladi: `GET /admin/certificates/{id}/download`, `CertificateRepository::find()` orqali `pdf_path` olib, `readfile()`)

**Xatolik/ruxsat:** sessiyasiz himoyalangan sahifaga kirish — 302 redirect `/admin/login`ga; CSRF mos kelmasa — 403 oddiy xato sahifasi; topilmagan marshrut — 404 sahifa.

## 4. Backend o'zgarishi: avtomatik sertifikat

`backend/src/Controllers/Api/TestController.php::submit()` — agar `$result['passed'] === true` bo'lsa va bu foydalanuvchining shu test bo'yicha birinchi muvaffaqiyatli urinishi bo'lsa (avval `test_attempts`da shu `user_id`+`test_id` uchun `passed=1` yozuv yo'q edi), `CertificateRepository::issue($userId, $professionId)` avtomatik chaqiriladi (`profession_id` — `tests.profession_id` orqali topiladi). Bu o'zgarish backend kod bazasida, lekin shu sub-loyiha rejasi doirasida amalga oshiriladi, chunki admin panelning sertifikatlar sahifasi shunga bog'liq.

## 5. Yangi repository/servicelar

| Fayl | Vazifasi |
|---|---|
| `Repositories/GroupRepository.php` | `all()`, `find($id)`, `create(...)`, `studentsIn($groupId)`, `unassignedApprovedStudents()` (guruhsiz, tasdiqlangan talabalar) |
| `Repositories/TeacherRepository.php` | `all()` (`role='teacher'`), `create(...)` (ichida `UserRepository`dan foydalanadi) |
| `Services/ScheduleGenerator.php` | `generateForGroup(int $groupId, DateTimeImmutable $start, DateTimeImmutable $end, array $weeklyTemplate): int` — har bir kun uchun `weeklyTemplate`dagi hafta kuniga mos kelsa `schedule` yozuvi yaratadi, yaratilgan yozuvlar sonini qaytaradi |
| `Repositories/QuestionRepository.php` | `createWithAnswers(int $testId, string $textUz, string $textRu, array $answers)` — savol + javoblarni bitta tranzaksiyada yaratadi |

Mavjud `ApplicationRepository`, `TestRepository`, `CertificateRepository`, `ProfessionRepository`, `UserRepository`, `ScheduleRepository` qayta ishlatiladi.

## 6. Xavfsizlik

- Barcha admin sahifalari `AdminAuthMiddleware` orqali himoyalangan (login/logout bundan mustasno).
- Har bir `POST` formada CSRF token.
- Parollar `Auth::hashPassword()` orqali (backenddagi bilan bir xil), hech qachon log/sessionda saqlanmaydi (faqat bitta so'rov davomida admin ekranida ko'rsatiladi).
- SQL — barcha yangi repositorylar ham PDO prepared statement ishlatadi (backend Global Constraints bilan bir xil qoida).
- Session cookie: `httponly`, `samesite=Lax`.

## 7. Testlash

- **PHPUnit** bilan: `ScheduleGenerator::generateForGroup()` uchun unit test (haftalik shablon → to'g'ri sanalar generatsiya qilinishini tekshirish, masalan 2 haftalik kurs + "dushanba,chorshanba" shabloni = 4 ta dars), `GroupRepository`/`TeacherRepository`/`QuestionRepository` uchun asosiy CRUD testlari, `AdminAuthMiddleware` uchun sessiyasiz/session bilan testlar.
- **Qo'lda tekshirish** (`php -S` bilan): login → ariza tasdiqlash → guruh yaratish (jadval avtomatik generatsiya bo'lishini tekshirish) → talaba biriktirish → test+savol qo'shish → (Android/API orqali test topshirilgach) sertifikat ro'yxatda ko'rinishini tekshirish.

## 8. Papka tuzilishi (qo'shimcha)

```
backend/
  public/
    admin.php                     # yangi kirish nuqtasi
  src/
    Core/
      View.php                    # PHP shablon render helper
      Csrf.php                    # token generatsiya/tekshirish
    Middleware/
      AdminAuthMiddleware.php
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
    Repositories/
      GroupRepository.php
      TeacherRepository.php
      QuestionRepository.php
    Services/
      ScheduleGenerator.php
  resources/
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
    lang/
      uz.php
      ru.php
  database/
    seeders/
      seed_admin.php              # 1 ta admin hisobi yaratadi
```

## 9. Chegaralar / keyingi sub-loyihaga bog'liqlik

- Android ilova UI — 3-sub-loyihada.
- Admin panelda kasb (`professions`) tahrirlash formasi — ko'lamdan tashqarida (hozircha 3 kasb seed orqali beriladi, kerak bo'lsa SQL orqali o'zgartiriladi); agar kelajakda kerak bo'lsa, alohida qo'shiladi.
