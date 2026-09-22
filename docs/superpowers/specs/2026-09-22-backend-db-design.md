# Backend + SQL baza — dizayn hujjati

**Loyiha:** "Учебный центр" — Ekskavator mashinisti, Burg'ilash stanogi mashinisti, Avtosamosval haydovchisi kasblari bo'yicha o'quv markazi uchun dastur va sayt (diplom loyihasi).

**Sub-loyiha:** 1/3 — Backend + SQL baza (Android ilova va Veb-admin panel shu backendga tayanadi).

**Sana:** 2026-09-22

## 1. Maqsad va ko'lam

O'quv markazi uch kasb bo'yicha o'quvchilarni qabul qiladi, guruhlarga taqsimlaydi, dars jadvalini yuritadi, kurs oxirida onlayn test o'tkazadi va muvaffaqiyatli topshirganlarga sertifikat (PDF) beradi. Ushbu backend:

- Android ilova uchun JSON REST API taqdim etadi (`/api/v1/...`).
- Kelajakdagi veb-admin panel uchun umumiy asos (DB, model/repository qatlami) bo'lib xizmat qiladi (`/admin/...` marshrutlari keyingi sub-loyihada qo'shiladi).
- Barcha ma'lumotlarni bitta MySQL bazasida saqlaydi — Android va veb-admin bir xil ma'lumotni ko'radi.

Ko'lamdan tashqarida: veb-admin sahifalarining o'zi, Android ilovaning UI qismi — bular alohida sub-loyihalar.

## 2. Arxitektura

```
                    ┌─────────────────────────┐
                    │      MySQL baza          │
                    └────────────┬─────────────┘
                                 │
                    ┌────────────┴─────────────┐
                    │   PHP "core" qatlami       │
                    │ (model / repository classlar)│
                    └──────┬─────────────┬──────┘
                           │             │
              ┌────────────┴───┐   ┌─────┴────────────┐
              │  /api/v1/...    │   │   /admin/...      │
              │  JSON REST API  │   │  server-rendered  │
              │  auth: JWT      │   │  auth: PHP session│
              │  (Android uchun)│   │  (keyingi bosqich)│
              └─────────────────┘   └───────────────────┘
```

- Til/freymvork: PHP (qo'shimcha freymvorksiz yoki yengil router, masalan `nikic/fast-route`; ORM sifatida PDO + oddiy repository classlar — og'ir freymvork shart emas, diplom uchun tushunarli va o'qilishi oson bo'lishi kerak).
- Baza: MySQL, UTF-8 (`utf8mb4`), chunki uz+ru matnlar saqlanadi.
- Konfiguratsiya: `.env` fayl orqali (DB credentials, JWT secret) — repo'ga commit qilinmaydi, `.env.example` beriladi.

## 3. Ma'lumotlar bazasi sxemasi

### `users`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| full_name | VARCHAR(191) | |
| phone | VARCHAR(20) UNIQUE | login sifatida ham ishlatiladi |
| password_hash | VARCHAR(255) | bcrypt |
| role | ENUM('student','teacher','admin') | |
| language | ENUM('uz','ru') DEFAULT 'uz' | interfeys tili sozlamasi |
| created_at | DATETIME | |

### `professions`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| name_uz, name_ru | VARCHAR(191) | |
| description_uz, description_ru | TEXT | |
| duration_days | INT | kurs davomiyligi |
| price | DECIMAL(12,2) | |

### `applications`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| full_name | VARCHAR(191) | |
| phone | VARCHAR(20) | |
| profession_id | INT FK → professions | |
| status | ENUM('pending','approved','rejected') DEFAULT 'pending' | |
| created_user_id | INT FK → users NULLABLE | tasdiqlangach yaratilgan user |
| created_at | DATETIME | |

### `groups`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| profession_id | INT FK → professions | |
| teacher_id | INT FK → users (role=teacher) | |
| name | VARCHAR(100) | masalan "Ekskavator-12" |
| start_date, end_date | DATE | |

### `schedule`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| group_id | INT FK → groups | |
| lesson_date | DATE | |
| start_time, end_time | TIME | |
| room | VARCHAR(50) | |

### `enrollments`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| user_id | INT FK → users | |
| group_id | INT FK → groups | |
| status | ENUM('active','completed','dropped') DEFAULT 'active' | |
| joined_at | DATETIME | |

### `tests`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| profession_id | INT FK → professions | |
| title_uz, title_ru | VARCHAR(191) | |
| passing_score | INT | masalan 70 (%) |

### `questions`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| test_id | INT FK → tests | |
| text_uz, text_ru | TEXT | |

### `answers`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| question_id | INT FK → questions | |
| text_uz, text_ru | VARCHAR(255) | |
| is_correct | BOOLEAN | |

### `test_attempts`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| user_id | INT FK → users | |
| test_id | INT FK → tests | |
| score | INT | foizda |
| passed | BOOLEAN | |
| attempted_at | DATETIME | |

### `certificates`
| Ustun | Tip | Izoh |
|---|---|---|
| id | INT PK AI | |
| user_id | INT FK → users | |
| profession_id | INT FK → professions | |
| certificate_number | VARCHAR(50) UNIQUE | |
| issue_date | DATE | |
| pdf_path | VARCHAR(255) | serverda saqlangan fayl yo'li |

## 4. API endpointlar (`/api/v1`)

**Public (autentifikatsiyasiz):**
- `GET /professions` — kasblar ro'yxati
- `POST /applications` — onlayn ariza yuborish
- `POST /auth/login` — `{phone, password}` → `{token}` (JWT)

**Himoyalangan (JWT, `Authorization: Bearer <token>`):**
- `GET /me` — profil
- `GET /me/schedule` — o'zining faol guruhi dars jadvali
- `GET /me/tests` — mavjud (hali topshirilmagan) testlar
- `POST /me/tests/{id}/submit` — `{answers: [...]}` → `{score, passed}`
- `GET /me/certificates` — sertifikatlar ro'yxati
- `GET /certificates/{id}/download` — PDF fayl

**Xatolik formati:** `{"error": {"code": "STRING_CODE", "message": "..."}}`, mos HTTP status kodlari bilan (400/401/403/404/422/500).

**JWT:** `firebase/php-jwt` yoki shunga o'xshash kutubxona bilan, muddat (masalan 30 kun), payload'da `user_id`, `role`.

## 5. Sertifikat generatsiyasi

Talaba testdan `passing_score`dan yuqori ball olsa: `test_attempts.passed = true` bo'ladi. Keyin (avtomatik yoki admin tasdig'idan so'ng — bu admin panel sub-loyihasida hal qilinadi) `certificates` yozuvi yaratiladi va PDF generatsiya qilinadi (kutubxona: `dompdf/dompdf` yoki `mpdf/mpdf`). PDF'da: F.I.Sh, kasb nomi, sana, sertifikat raqami, markaz nomi.

## 6. Xatoliklarni boshqarish

- Validatsiya xatoliklari — 422, maydon nomlari bilan.
- Auth xatoliklari (token yo'q/eskirgan) — 401.
- Ruxsat yo'q (masalan boshqa role) — 403.
- Topilmadi — 404.
- Kutilmagan server xatosi — 500, loglarga yoziladi (`logs/app.log`), foydalanuvchiga umumiy xabar qaytariladi (ichki tafsilotlar sizib chiqmaydi).

## 7. Testlash

- **PHPUnit** bilan unit testlar: parolni hash qilish/tekshirish, JWT yaratish/tekshirish, test balli hisoblash logikasi, ariza tasdiqlanganda user yaratish logikasi.
- **Qo'lda/Postman** orqali API endpointlarni integratsion tekshirish (asosiy oqim: ariza → login → jadval → test topshirish → sertifikat).
- Test bazasi: alohida `.env.testing` bilan test MySQL bazasi (yoki SQLite in-memory, agar PDO orqali mos kelsa).

## 8. Papka tuzilishi (taxminiy)

```
backend/
  public/           # index.php — entry point
  src/
    Core/           # Router, Request, Response, DB, Auth (JWT)
    Models/         # User, Profession, Application, Group, ...
    Repositories/    # DB bilan ishlash
    Controllers/     # Api/... controllerlar
  database/
    migrations/      # SQL migratsiya fayllari
    seeders/         # boshlang'ich ma'lumotlar (3 ta kasb va h.k.)
  tests/             # PHPUnit testlar
  .env.example
  composer.json
```

## 9. Chegaralar / keyingi sub-loyihalarga bog'liqlik

- `/admin/...` marshrutlari va sahifalari — **Veb-admin panel** sub-loyihasida qo'shiladi (shu backend kod bazasi ustiga).
- Android ilovaning o'zi (UI, ekranlar) — **Android ilova** sub-loyihasida.
- Sertifikat PDF shablonining aniq dizayni (logotip, joylashuv) — keyinroq admin panel bilan birga aniqlashtiriladi.
