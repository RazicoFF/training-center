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
APP_URL=https://<nom>.up.railway.app
```

`APP_URL` arizani tasdiqlagandan keyin talabaga yuboriladigan login xabaridagi sayt
manzili uchun ishlatiladi.

## 4. Yuklangan fayllar (Volume shart emas)

Konteyner diski har `git push`dan keyin tozalanadi, lekin bu endi fayllarni yo'qotmaydi:

- Admin panel va sayt orqali yuklangan har bir fayl (kasb, yangilik, media, o'qituvchi,
  talaba va ariza rasmlari, kasb PDF'lari) diskka ham, MySQL'dagi `uploaded_files`
  jadvaliga ham yoziladi. Diskda fayl topilmasa, `public/upload.php` uni bazadan beradi
  va diskka qayta yozib qo'yadi.
- Sertifikat PDF'lari (`storage/certificates`) yuklab olinayotganda topilmasa, bazadagi
  ma'lumotdan avtomatik qayta yaratiladi.
- Rasmlar yuklash paytida uzun tomoni 1600 px gacha (odam rasmlari 800 px) kichraytirilib,
  WebP formatga o'tkaziladi, shuning uchun baza tez to'lib qolmaydi.

Volume (Hobby tarif) ixtiyoriy: ulasangiz, fayllar bazadan qayta tiklanishi ham shart
bo'lmay qoladi. Mount path: `/var/www/html/public/uploads` va `/var/www/html/storage`.

### Zaxira nusxa (backup)

Admin panel → **Sozlamalar** sahifasining pastidagi **"Zaxira nusxani yuklab olish"**
tugmasi butun bazani (yuklangan rasmlar bilan birga) `backup-YYYY-MM-DD-HHMMSS.sql.gz`
fayliga yuklab beradi. Haftada bir marta yuklab, kompyuter yoki Google Drive'da saqlang.

Tiklash (Railway MySQL'ning public ulanish ma'lumotlari bilan):

```
gunzip backup-....sql.gz
mysql -h <host> -P <port> -u root -p railway < backup-....sql
```

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
