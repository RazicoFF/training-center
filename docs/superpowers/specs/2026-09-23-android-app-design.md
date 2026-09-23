# Android ilova — dizayn hujjati

**Loyiha:** "Учебный центр" — Ekskavator mashinisti, Burg'ilash stanogi mashinisti, Avtosamosval haydovchisi kasblari bo'yicha o'quv markazi uchun dastur va sayt (diplom loyihasi).

**Sub-loyiha:** 3/3 — Android ilova (talabalar uchun).

**Sana:** 2026-09-23

## 1. Maqsad va ko'lam

Talabalar uchun to'liq o'quv platformasi: ariza berish, tizimga kirish, dars jadvalini ko'rish, testlarni topshirish va sertifikatlarni yuklab olish. Ilova mavjud backend REST API (`docs/superpowers/specs/2026-09-22-backend-db-design.md`, sub-loyiha 1) bilan ishlaydi — hech qanday yangi backend endpoint kerak emas, `GET /me/tests/{id}` (savollarni olish) sub-loyiha 2 (admin panel) davomida allaqachon qo'shilgan.

Ko'lamdan tashqarida: yangi backend endpointlar, admin panel funksiyalari, push-bildirishnomalar, offline rejim.

## 2. Arxitektura

```
                    ┌──────────────────────────┐
                    │   Backend REST API        │
                    │  (/api/v1/..., JWT auth)  │
                    └────────────┬──────────────┘
                                 │ HTTPS/HTTP (Retrofit)
              ┌──────────────────┴───────────────────┐
              │              Android ilova             │
              │  ┌────────┐  ┌────────────┐  ┌──────┐ │
              │  │  UI     │←→│ ViewModel  │←→│ Repo │ │
              │  │(Compose)│  │ (StateFlow)│  │      │ │
              │  └────────┘  └────────────┘  └──┬───┘ │
              │                          ┌───────┴───┐ │
              │                          │ Retrofit  │ │
              │                          │ DataStore │ │
              │                          └───────────┘ │
              └────────────────────────────────────────┘
```

- **Til/UI:** Kotlin, Jetpack Compose, Material 3.
- **Arxitektura:** MVVM — bitta `MainActivity` + Navigation Compose, har ekran uchun `ViewModel` (`StateFlow<UiState>`), `Repository` qatlami.
- **Tarmoq:** Retrofit + OkHttp. Server manzili (`base_url`) DataStore'da saqlanadi; `BaseUrlInterceptor` har bir so'rovning hostini joriy saqlangan manzilga almashtiradi — sozlamalarda manzil o'zgartirilsa, ilova qayta ishga tushmasdan yangi manzilga so'rov yuboradi. JWT token bor bo'lsa, `AuthInterceptor` `Authorization: Bearer <token>` headerini qo'shadi. `401` javobi kelsa, token va sessiya tozalanadi, foydalanuvchi Login ekraniga yo'naltiriladi.
- **Saqlash:** Jetpack DataStore (Preferences) — `jwt_token`, `base_url`, `language` (uz/ru), `theme` (light/dark/system).
- **Til:** Android standart resurs tizimi — `values/strings.xml` (uz, default) va `values-ru/strings.xml` (ru). Ilova ichida til tanlash `AppCompatDelegate.setApplicationLocales()` orqali amalga oshiriladi va DataStore'da saqlanadi (keyingi ishga tushirishda ham eslab qoladi).
- **Tema:** Material 3 `ColorScheme`, `isSystemInDarkTheme()` boshlang'ich qiymat, DataStore'dagi qo'lda tanlov (`light`/`dark`/`system`) ustunlik qiladi.

## 3. Ekranlar va navigatsiya

```
Splash
  ├─ token bor va yaroqli → Home
  └─ token yo'q/yaroqsiz → Login

Login ──(ariza berish havolasi)──→ Register
Login ──(muvaffaqiyatli)──→ Home

Register ──(ariza yuborildi)──→ Login (xabar bilan)

Home (Bottom Navigation: Jadval | Testlar | Sertifikatlar | Profil)
  ├─ Jadval — dars jadvali ro'yxati
  ├─ Testlar — mavjud testlar ro'yxati
  │    └─ Test tanlansa → TestTaking (savol-savol) → TestResult → Testlar ro'yxatiga qaytish
  ├─ Sertifikatlar — sertifikatlar ro'yxati, bosilganda PDF yuklab olinadi va tashqi dastur bilan ochiladi
  └─ Profil — F.I.Sh/telefon (o'qish uchun), til, tema, server manzili, "Chiqish" tugmasi
```

### Ekranlar tavsifi

| Ekran | API chaqiruvi | Asosiy elementlar |
|---|---|---|
| Splash | — (faqat DataStore'dan token o'qiladi) | Logo, yuklanish indikatori |
| Login | `POST /auth/login` | Telefon, parol maydonlari, "Kirish" tugmasi, "Ariza berish" havolasi, xatolik xabari |
| Register | `GET /professions`, `POST /applications` | F.I.Sh, telefon, kasb tanlash (dropdown, kasb rasmi bilan), "Yuborish" tugmasi |
| Jadval | `GET /me/schedule` | Sana, vaqt, xona, guruh nomi ro'yxati |
| Testlar | `GET /me/tests` | Test sarlavhasi, o'tish balli, "Boshlash" tugmasi |
| TestTaking | `GET /me/tests/{id}` | Bitta savol, javob variantlari (radio button), "Keyingi"/"Yakunlash" tugmasi, progress indikatori (masalan "3/5") |
| TestResult | (TestTaking'dan olingan natija, qo'shimcha so'rov yo'q) | Ball, "O'tdingiz"/"O'tmadingiz" xabari, "Testlarga qaytish" tugmasi |
| Sertifikatlar | `GET /me/certificates`, `GET /certificates/{id}/download` | Sertifikat raqami, sanasi, kasb nomi, "Yuklab olish" tugmasi |
| Profil | `GET /me` | F.I.Sh, telefon (faqat ko'rish), til tanlash, tema tanlash, server manzili input, "Chiqish" tugmasi |

## 4. Ma'lumotlar oqimi va xatoliklarni boshqarish

- Har bir ViewModel holati `sealed interface UiState { Loading, Success(data), Error(message) }` shaklida ifodalanadi.
- Tarmoq xatoliklari (timeout, ulanish yo'q) uz/ru tarjima qilingan umumiy xabarga aylantiriladi ("Serverga ulanib bo'lmadi. Internetni tekshiring.").
- `401 Unauthorized` javobi — `AuthInterceptor`/`Authenticator` orqali aniqlanadi, DataStore'dagi token tozalanadi, navigatsiya Login ekraniga qaytariladi.
- `422`/`400` validatsiya xatoliklari — backend qaytargan `message` maydoni to'g'ridan-to'g'ri ko'rsatiladi (backend xabarlari lotin/kiril aralash bo'lishi mumkin, alohida tarjima qilinmaydi — bu chegaradan tashqarida).
- Test topshirish paytida tarmoq uzilsa — xato xabari ko'rsatiladi, "Qayta urinish" tugmasi bilan, javoblar mahalliy holatda saqlanib qoladi (yo'qolmaydi).

## 5. Sertifikat yuklab olish

`GET /certificates/{id}/download` — Retrofit orqali `ResponseBody` (binary) sifatida so'raladi, ilova uni qurilmaning `Downloads` papkasiga (yoki ilova ichki cache papkasiga, `FileProvider` orqali) saqlaydi, so'ng `Intent.ACTION_VIEW` + `application/pdf` MIME turi bilan tashqi PDF ko'ruvchi dasturga ochish so'rovi yuboriladi.

## 6. Testlash

- **Unit testlar (JUnit + MockK + Turbine):** har bir ViewModel uchun (Loading→Success, Loading→Error holatlari), Repository qatlami uchun (Retrofit chaqiruvlarini mock qilib, to'g'ri parametr uzatilishini va javob mapping'ini tekshirish), `BaseUrlInterceptor`/`AuthInterceptor` uchun.
- **MockWebServer:** Retrofit interfeysi haqiqiy HTTP so'rov/javob formatini to'g'ri parse qilishini tekshirish uchun (kamida 2-3 asosiy endpoint: login, professions, test submit).
- **Qo'lda tekshirish:** bu mashinada Android SDK cmdline-tools o'rnatiladi, `./gradlew assembleDebug` va `./gradlew test` orqali compile va unit testlar tekshiriladi. Emulyator o'rnatilmaydi — UI/instrumentatsiya testlari va jonli ekran sinovi ushbu sessiyada amalga oshirilmaydi, buni foydalanuvchi Android Studio orqali keyinroq bajaradi.

## 7. Papka tuzilishi (taxminiy)

```
android-app/
  app/
    build.gradle.kts
    src/
      main/
        AndroidManifest.xml
        java/uz/edu/trainingcenter/
          MainActivity.kt
          navigation/
            AppNavHost.kt
          data/
            local/
              PreferencesDataStore.kt
            remote/
              ApiService.kt
              AuthInterceptor.kt
              BaseUrlInterceptor.kt
              dto/                     # so'rov/javob data class'lari
            repository/
              AuthRepository.kt
              ProfessionRepository.kt
              ScheduleRepository.kt
              TestRepository.kt
              CertificateRepository.kt
          ui/
            theme/
              Theme.kt
              Color.kt
            screens/
              splash/
              login/
              register/
              home/
              schedule/
              tests/
              testtaking/
              testresult/
              certificates/
              profile/
        res/
          values/strings.xml
          values-ru/strings.xml
      test/
        java/uz/edu/trainingcenter/     # unit testlar
  build.gradle.kts
  settings.gradle.kts
  gradle/wrapper/
```

## 8. Chegaralar / bog'liqlik

- Backend API (sub-loyiha 1) va admin panel (sub-loyiha 2) allaqachon tayyor — ularga hech qanday o'zgartirish kiritilmaydi.
- Ariza tasdiqlangach berilgan parolni talaba SMS orqali olmaydi (backend'da SMS integratsiyasi yo'q) — bu ilova doirasidan tashqarida, admin talabaga og'zaki/qog'ozda aytadi.
- Emulyator/real qurilmada jonli sinov — foydalanuvchi tomonidan keyinroq Android Studio orqali amalga oshiriladi.
