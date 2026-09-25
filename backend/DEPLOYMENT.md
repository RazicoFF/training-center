# Railway'ga joylashtirish

Bu loyihada Railway uchun kerakli fayllar tayyor: `Dockerfile`, `docker/apache-vhost.conf`,
`public/.htaccess`, `docker-entrypoint.sh`. Quyidagi qadamlarni bajaring.

## 1. Railway'da loyiha yaratish

1. https://railway.app ga GitHub hisobingiz bilan kiring.
2. **New Project → Deploy from GitHub repo** ni tanlang va ushbu repozitoriyni bog'lang.
3. Railway `backend/Dockerfile`ni avtomatik topishi uchun **Root Directory**ni `backend` qilib
   belgilang (loyiha sozlamalarida, "Settings → Root Directory").

## 2. MySQL qo'shish

1. Loyiha ichida **+ New → Database → MySQL** ni bosing.
2. Railway avtomatik `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`
   nomli o'zgaruvchilarni yaratadi.

## 3. Backend xizmatiga environment o'zgaruvchilarini qo'shish

Backend xizmatining **Variables** bo'limida quyidagilarni qo'shing (MySQL xizmatidan
referens sifatida):

```
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_NAME=${{MySQL.MYSQLDATABASE}}
DB_USER=${{MySQL.MYSQLUSER}}
DB_PASS=${{MySQL.MYSQLPASSWORD}}
JWT_SECRET=<uzun tasodifiy satr, masalan: openssl rand -hex 32>
JWT_TTL_DAYS=30
```

## 4. Doimiy disk (Volume) qo'shish

Rasmlar, PDF sertifikatlar va video fayllar konteyner qayta ishga tushganda yo'qolib
ketmasligi uchun **Volume** qo'shing:

1. Backend xizmatida **Settings → Volumes → New Volume**.
2. Mount path: `/var/www/html/public/uploads`
3. Yana bittasini qo'shing: mount path `/var/www/html/storage`

## 5. Deploy va migratsiya

Railway `git push` qilinganda avtomatik deploy qiladi. Konteyner ishga tushganda
`docker-entrypoint.sh` skripti `database/migrate.php`ni avtomatik ishga tushiradi -
alohida qo'l bilan migratsiya qilish shart emas.

## 6. Domen

Backend xizmatida **Settings → Networking → Generate Domain** tugmasini bosing -
`https://<nom>.up.railway.app` ko'rinishidagi bepul domen beriladi. Google Play/App Store
uchun API manzili sifatida shu domendan foydalaning.

## Eslatmalar

- Railway'ning bepul rejasi oyiga $5 kredit beradi - kichik/o'rta trafikli loyiha uchun
  odatda yetadi, lekin haqiqiy foydalanuvchi ko'payganda pullik rejaga o'tish kerak bo'lishi
  mumkin.
- `.env` fayli productionga yuklanmaydi (`.dockerignore`da chetlab o'tilgan) - barcha
  sozlamalar Railway Variables orqali beriladi.
