# TINTAPENA — Deployment Specification V1

Dokumen ini menetapkan prosedur deployment TINTAPENA V1 ke Hostinger Premium Shared Hosting.

Target utama:

- Laravel 13
- PHP 8.4
- MySQL
- Blade + Tailwind CSS + Alpine.js + Livewire
- Filament
- Hostinger Premium Shared Hosting
- deployment tanpa Redis wajib
- deployment tanpa long-running queue worker
- frontend asset dibuild sebelum production

Dokumen terkait:

- `docs/03-ARCHITECTURE.md`
- `docs/04-DATABASE.md`
- `docs/05-ROUTES.md`
- `docs/08-TEST-PLAN.md`
- `docs/09-SECURITY.md`

---

# 1. Deployment Principles

1. Production bukan tempat development.
2. Source code harus sudah lolos test sebelum deploy.
3. `.env` production dibuat di server dan tidak masuk Git.
4. `APP_DEBUG=false` di production.
5. Database migration dilakukan melalui Artisan.
6. Frontend asset dibuild sebelum deployment.
7. Jangan menjalankan `composer update` sebagai prosedur release normal.
8. Gunakan `composer.lock`.
9. Backup database sebelum migration berisiko.
10. Deployment harus dapat diverifikasi dan, bila perlu, di-rollback.

---

# 2. Production Architecture

Target sederhana:

```text
Internet
   |
   v
Hostinger Web Server
   |
   v
public_html
(Laravel public files only)
   |
   v
Laravel Application
(outside public_html)
   |
   +--> MySQL
   |
   +--> storage/app/public
   |
   +--> Laravel Scheduler via Cron
```

Tujuan utama struktur ini adalah agar source Laravel seperti `.env`, `vendor`, `storage`, dan file aplikasi lain tidak menjadi file publik.

---

# 3. Important Hostinger Constraint

Pada Hostinger Web/Shared Hosting, document root website umumnya adalah:

```text
public_html
```

Hostinger tidak mengizinkan perubahan home/document root pada paket Web hosting melalui hPanel.

Karena itu deployment Laravel harus disusun agar:

```text
public_html
```

hanya memuat file yang seharusnya berasal dari folder Laravel:

```text
public/
```

Sedangkan source aplikasi Laravel disimpan di luar `public_html` jika struktur hosting mengizinkan.

---

# 4. Recommended Production Layout

Contoh:

```text
/home/u123456789/domains/tintapena.id/

├── tintapena/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   ├── artisan
│   ├── composer.json
│   └── composer.lock
│
└── public_html/
    ├── index.php
    ├── .htaccess
    ├── build/
    ├── storage -> ../tintapena/storage/app/public
    └── public assets lainnya
```

Nama folder `tintapena/` dapat berbeda.

Yang penting:

- application root tidak menjadi document root publik;
- hanya public entry point/assets berada di `public_html`.

---

# 5. public/index.php Adjustment

Jika isi Laravel `public/` dipindahkan ke Hostinger `public_html/`, path pada `index.php` harus menunjuk ke application root yang benar.

Contoh konsep:

```php
require __DIR__.'/../tintapena/vendor/autoload.php';

$app = require_once __DIR__.'/../tintapena/bootstrap/app.php';
```

Path final wajib disesuaikan dengan struktur folder production sebenarnya.

Jangan copy contoh path tanpa memverifikasi lokasi server.

---

# 6. Local Pre-Deployment Requirements

Sebelum deploy:

```text
Git clean / perubahan sudah direview
Composer dependencies valid
Frontend build berhasil
Automated tests PASS
Migration direview
.env.example diperbarui bila ada variable baru
```

Minimum:

```bash
composer install
npm install
npm run build
php artisan test
```

Gunakan workflow project yang sebenarnya jika command package manager berbeda.

---

# 7. Frontend Production Build

TINTAPENA tidak membutuhkan Node.js backend di production.

Vite digunakan untuk menghasilkan static production assets.

Build:

```bash
npm run build
```

Hasil umum:

```text
public/build/
```

Folder hasil build harus ikut deployment.

Production tidak boleh bergantung pada:

```bash
npm run dev
```

---

# 8. What Must Be Deployed

Deploy:

```text
app/
bootstrap/
config/
database/
public build/assets
resources/
routes/
composer.json
composer.lock
artisan
package metadata bila diperlukan
```

Vendor dapat:

1. di-install melalui Composer di server; atau
2. disiapkan dengan strategi deployment lain yang tervalidasi.

Preferred:

```bash
composer2 install --no-dev --optimize-autoloader
```

di server jika environment Hostinger mendukung dan resource mencukupi.

---

# 9. What Must NOT Be Deployed

Jangan deploy sebagai production secret/source artifact:

```text
.env local
node_modules/
tests output
IDE cache
temporary ZIP
database dump development
debug log
local credentials
```

`.git/` tidak diperlukan untuk deployment berbasis upload manual.

Jika deployment memakai Git, repository metadata dapat ada sesuai workflow, tetapi secret tetap dilarang.

---

# 10. SSH Access

Hostinger Premium menyediakan SSH access yang dapat diaktifkan dari hPanel.

Sebelum deployment command-line:

1. buka hPanel;
2. aktifkan SSH Access;
3. copy SSH connection command;
4. login menggunakan PowerShell/Terminal/SSH client;
5. verifikasi path dengan:

```bash
pwd
```

Gunakan absolute path ketika membuat cron.

---

# 11. PHP Version

Laravel 13 membutuhkan PHP minimal 8.3.

Target TINTAPENA:

```text
PHP 8.4
```

Set PHP website di hPanel ke PHP 8.4.

Verifikasi browser/runtime dan CLI karena PHP website dan PHP SSH/CLI dapat berbeda.

Cek:

```bash
php -v
```

Jika CLI memakai versi berbeda, gunakan binary PHP spesifik Hostinger saat diperlukan.

Contoh konsep:

```bash
/opt/alt/php84/usr/bin/php artisan about
```

Path aktual harus diverifikasi di akun hosting.

---

# 12. Composer

Hostinger Web Premium menyediakan Composer melalui SSH.

Untuk release normal gunakan:

```bash
composer2 install --no-dev --optimize-autoloader
```

Jangan gunakan:

```bash
composer update
```

sebagai langkah deployment rutin karena dapat mengganti versi dependency dari yang sudah dikunci di `composer.lock`.

Jika PHP CLI default tidak sesuai dengan requirement:

```bash
/opt/alt/php84/usr/bin/php /usr/local/bin/composer2 install --no-dev --optimize-autoloader
```

Path server harus diverifikasi terlebih dahulu.

---

# 13. Production .env

Buat `.env` production langsung di application root server.

Minimum konsep:

```env
APP_NAME=TINTAPENA
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://domain-produksi

APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database

CACHE_STORE=database

FILESYSTEM_DISK=public
```

Nilai final mengikuti config Laravel yang benar-benar digunakan project.

Jangan menyimpan credential production pada dokumentasi ini.

---

# 14. APP_KEY

Untuk fresh production environment:

```bash
php artisan key:generate
```

Jalankan sekali pada `.env` production yang benar.

Jangan regenerate APP_KEY sembarangan setelah aplikasi aktif.

---

# 15. MySQL Production Database

Di hPanel:

1. buat database MySQL;
2. buat user database;
3. catat database host/name/user;
4. masukkan ke `.env`;
5. jangan memasukkan password ke Git.

Setelah config benar:

```bash
php artisan migrate --force
```

`--force` diperlukan pada production agar migration dapat berjalan non-interaktif.

---

# 16. Migration Safety

Sebelum migration production:

- review migration;
- backup database jika migration signifikan;
- pastikan migration tidak menjalankan destructive operation yang tidak disengaja.

Jangan pernah menjalankan:

```bash
php artisan migrate:fresh
```

di production.

Jangan menjalankan:

```bash
php artisan db:wipe
```

di production.

---

# 17. Initial Seed

Seeder production hanya dijalankan jika memang dirancang aman.

Contoh data awal:

- categories;
- regions;
- homepage slots.

Gunakan:

```bash
php artisan db:seed --force
```

hanya jika seeder telah direview.

Jangan membuat production admin dengan password hardcoded pada repository.

---

# 18. Admin Production Account

Admin production sebaiknya dibuat melalui:

- Artisan command khusus; atau
- mekanisme provisioning yang aman.

Contoh target workflow:

```text
php artisan tintapena:create-admin
```

Command tersebut dapat dibuat saat implementasi authentication/admin selesai.

Jangan menyimpan default admin credential di source code.

---

# 19. Storage

Media publik menggunakan Laravel public disk.

Laravel menyimpan public files pada:

```text
storage/app/public
```

dan membutuhkan link menuju:

```text
public/storage
```

Pada layout Hostinger kita, target publik akhirnya harus dapat diakses melalui `public_html/storage`.

Coba terlebih dahulu:

```bash
php artisan storage:link
```

Jika struktur public Laravel sudah dipetakan secara khusus ke `public_html`, verifikasi symlink mengarah ke lokasi yang benar.

Jangan membuat link sebelum memahami absolute production path.

---

# 20. Storage Verification

Setelah storage link:

1. upload satu image test melalui Newsroom;
2. cek record database;
3. cek file fisik;
4. buka URL public image;
5. pastikan 200;
6. pastikan `.env` atau file private tidak terekspos.

---

# 21. Writable Directories

Laravel membutuhkan write permission pada:

```text
storage/
bootstrap/cache/
```

Gunakan permission secukupnya.

Jangan membuat seluruh project:

```text
777
```

hanya untuk mengatasi permission error.

---

# 22. Laravel Optimization

Setelah dependency, `.env`, dan migration benar:

```bash
php artisan optimize
```

Atau command cache spesifik bila dibutuhkan:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Gunakan hanya setelah memastikan semua konfigurasi production valid.

Jika debugging config deployment:

```bash
php artisan optimize:clear
```

kemudian rebuild cache setelah masalah selesai.

---

# 23. Scheduler

TINTAPENA membutuhkan Laravel Scheduler terutama untuk:

```text
scheduled article publishing
```

Target command aplikasi:

```text
articles:publish-scheduled
```

Schedule didefinisikan di aplikasi, misalnya melalui:

```text
routes/console.php
```

Server hanya membutuhkan satu scheduler entry.

---

# 24. Hostinger Cron

Laravel merekomendasikan:

```text
schedule:run
```

dipanggil setiap menit.

Contoh konsep:

```cron
* * * * * cd /home/USER/domains/DOMAIN/tintapena && /opt/alt/php84/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Atau format Hostinger dengan absolute artisan path:

```cron
* * * * * /usr/bin/php /home/USER/domains/DOMAIN/tintapena/artisan schedule:run
```

Gunakan path PHP dan project yang benar untuk akun hosting.

Jangan copy `USER` atau `DOMAIN` literal.

---

# 25. Scheduler Verification

Setelah cron dibuat:

```bash
php artisan schedule:list
```

Lalu test scheduled article:

```text
1. buat artikel Scheduled beberapa menit ke depan;
2. pastikan belum public;
3. tunggu cron;
4. pastikan status berubah Published;
5. pastikan public URL menjadi tersedia.
```

Ini adalah release-critical test.

---

# 26. Queue

V1 tidak membutuhkan long-running queue worker.

Jika fitur tertentu akhirnya membutuhkan queue:

```text
database queue
```

lebih disukai untuk shared hosting.

Tetapi implementasi V1 tidak boleh bergantung pada daemon:

```text
queue:work
```

yang harus hidup permanen.

---

# 27. Cache

V1 tidak membutuhkan Redis.

Gunakan cache Laravel yang kompatibel dengan shared hosting.

Setelah deploy:

```bash
php artisan optimize
```

dan lakukan invalidation sesuai application logic.

Jika terdapat stale data:

```bash
php artisan optimize:clear
```

dapat digunakan sebagai troubleshooting, lalu cache dibangun kembali.

---

# 28. HTTPS

Aktifkan SSL/HTTPS pada domain production.

Production:

```env
APP_URL=https://domain-produksi
```

Verifikasi:

```text
https://domain
https://domain/admin/login
```

harus bekerja tanpa mixed-content error.

---

# 29. Domain and APP_URL

`APP_URL` harus menggunakan canonical production domain.

Contoh:

```env
APP_URL=https://tintapena.example
```

Jangan gunakan:

```text
localhost
127.0.0.1
temporary preview URL
```

pada production final.

---

# 30. Email

Jika V1 menggunakan email untuk notifikasi/contact di kemudian hari:

- SMTP config berada di `.env`;
- password SMTP tidak berada di `settings`;
- lakukan test email dari production sebelum mengaktifkan workflow.

Contact V1 tetap dapat menyimpan pesan ke database meskipun email notification belum digunakan.

---

# 31. First Deployment Sequence

Recommended first deployment:

```text
1. Backup/prepare hosting
2. Create production database
3. Upload/clone application
4. Arrange application outside public_html
5. Place Laravel public files in public_html
6. Adjust public_html/index.php paths
7. Create production .env
8. Set PHP 8.4
9. Install Composer dependencies
10. Generate APP_KEY
11. Run migrations
12. Run approved seeders
13. Create production admin
14. Create/verify storage link
15. Build/verify frontend assets
16. Run Laravel optimize
17. Configure cron
18. Test website
19. Test Newsroom
20. Test scheduled publishing
```

---

# 32. Standard Update Deployment

Untuk update setelah website production:

```text
1. Backup if migration/risky change
2. Put application in maintenance mode if necessary
3. Upload/pull new source
4. composer install --no-dev --optimize-autoloader
5. Deploy fresh public/build assets
6. php artisan migrate --force
7. php artisan optimize
8. php artisan schedule:interrupt if applicable
9. Exit maintenance mode
10. Smoke test
```

Laravel maintenance mode:

```bash
php artisan down
```

Kembali online:

```bash
php artisan up
```

Gunakan maintenance mode hanya jika update memang membutuhkannya.

---

# 33. Frontend Asset Deployment

Setelah:

```bash
npm run build
```

pastikan:

```text
public/build/manifest.json
```

dan asset build tersedia di production public path.

Jika muncul error Vite manifest not found:

- cek apakah build sudah dilakukan;
- cek folder `public/build`;
- cek apakah asset hasil build ikut diupload.

---

# 34. Smoke Test After Deployment

Minimum:

```text
GET /
GET /admin/login
GET /terbaru
GET /terpopuler
GET /cari
GET /kontak
GET /sitemap.xml
```

Lalu:

- login admin;
- create Draft;
- preview Draft;
- publish article;
- cek public article;
- upload media;
- cek featured image;
- cek homepage;
- test scheduled article.

---

# 35. Production Security Check

Wajib:

```text
APP_ENV=production
APP_DEBUG=false
HTTPS active
.env not public
/admin protected
Draft not public
Scheduled future not public
public_html only exposes public files
```

Test URL sensitif:

```text
/.env
/composer.json
/storage/logs/laravel.log
```

harus tidak memberikan isi sensitif.

---

# 36. Release Verification

Sebelum release dinyatakan selesai:

```text
[ ] Homepage works
[ ] Admin login works
[ ] Database connected
[ ] Migration successful
[ ] Media upload works
[ ] storage URL works
[ ] Draft protected
[ ] Published article works
[ ] Scheduled article works
[ ] Cron works
[ ] Search works
[ ] Sitemap works
[ ] HTTPS works
[ ] APP_DEBUG=false
[ ] No obvious 500 errors
```

---

# 37. Logs

Laravel log:

```text
storage/logs/
```

Gunakan untuk troubleshooting server-side.

Log tidak boleh menjadi public asset.

Jika terjadi 500:

1. cek Laravel log;
2. cek hPanel error/log tool;
3. cek PHP version;
4. cek file permissions;
5. cek `.env`;
6. cek DB connection;
7. cek cache.

Jangan mengaktifkan `APP_DEBUG=true` di production publik hanya untuk troubleshooting.

---

# 38. Common Deployment Problems

## 500 Internal Server Error

Periksa:

- PHP version;
- missing vendor;
- `.env`;
- APP_KEY;
- permissions;
- cached config;
- Laravel logs.

## Vite Manifest Not Found

Periksa:

```text
public/build/
```

## Database Connection Error

Periksa production:

```text
DB_HOST
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

## Storage Image 404

Periksa:

```text
storage link
filesystem disk
public path
file permissions
```

## Artisan Uses Wrong PHP Version

Gunakan binary PHP 8.4 secara eksplisit setelah memverifikasi path Hostinger.

---

# 39. Rollback Strategy

Sebelum risky deployment:

- backup database;
- simpan release sebelumnya bila memungkinkan;
- simpan build asset sebelumnya.

Jika release gagal:

1. aktifkan maintenance mode bila perlu;
2. restore application release sebelumnya;
3. rollback migration hanya jika migration memang aman di-rollback;
4. restore database backup jika diperlukan;
5. clear/rebuild Laravel cache;
6. test;
7. bring application online.

Jangan melakukan:

```bash
php artisan migrate:rollback
```

secara otomatis tanpa memahami perubahan data.

---

# 40. Database Backup

Sebelum migration signifikan:

- gunakan backup Hostinger/hPanel atau export database;
- pastikan backup benar-benar tersedia;
- beri timestamp pada manual backup bila digunakan.

Backup sangat penting untuk migration yang:

- drop column;
- alter data;
- delete data;
- transform schema besar.

---

# 41. Media Backup

Database backup saja tidak cukup.

Backup juga:

```text
storage/app/public
```

karena berisi media berita.

Jika storage eksternal dipakai di masa depan, backup strategy harus diperbarui.

---

# 42. Git Deployment Option

Jika repository Git digunakan:

preferred flow:

```text
local development
→ Git repository
→ reviewed commit
→ production pull/deploy
```

Production tidak boleh menjadi tempat editing source utama.

Jangan melakukan hotfix langsung di server tanpa kemudian mengembalikan perubahan ke repository.

---

# 43. Manual Upload Option

Jika belum memakai Git deploy:

1. build/test lokal;
2. buat package deployment;
3. jangan sertakan `.env`;
4. upload source aplikasi;
5. upload `public/build`;
6. install Composer dependency di server atau siapkan vendor secara benar;
7. jalankan migration;
8. optimize;
9. smoke test.

Hindari upload file satu per satu tanpa version tracking.

---

# 44. Deployment Package

Jika membuat ZIP deployment, isinya boleh seperti:

```text
app/
bootstrap/
config/
database/
public/
resources/
routes/
artisan
composer.json
composer.lock
package.json
vite config
```

Jangan masukkan:

```text
.env
node_modules/
development database
local logs
IDE folder
```

---

# 45. Release Versioning

Gunakan Git commit/tag untuk release penting.

Contoh:

```text
v1.0.0
v1.0.1
```

Minimal catat:

- tanggal deploy;
- commit;
- migration yang dijalankan;
- issue penting.

---

# 46. Production Timezone

Timezone aplikasi harus diputuskan secara eksplisit.

Untuk portal Bangka Belitung, gunakan timezone aplikasi yang sesuai kebutuhan editorial.

Jadwal artikel harus konsisten antara:

- Newsroom input;
- Laravel application timezone;
- database timestamp strategy;
- cron/server time.

Sebelum release Scheduled Article, test dengan waktu nyata.

---

# 47. Scheduled Publish Time Rule

Admin melihat waktu editorial dalam timezone aplikasi.

Business rule:

```text
Scheduled Article tidak boleh Published sebelum waktu yang dipilih admin.
```

Test production minimal satu kali setelah cron aktif.

---

# 48. Maintenance Mode

Maintenance mode dapat digunakan untuk deployment yang berpotensi menghasilkan state tidak konsisten.

Commands:

```bash
php artisan down
php artisan up
```

Untuk perubahan kecil tanpa migration dan tanpa breaking change, maintenance mode dapat tidak diperlukan.

---

# 49. Production Optimization

Setelah release sukses:

```bash
php artisan optimize
```

Pastikan:

- config cache valid;
- route cache tidak menimbulkan error;
- view cache valid.

Jika ada masalah:

```bash
php artisan optimize:clear
```

kemudian perbaiki root cause.

---

# 50. Hostinger Resource Awareness

Shared hosting memiliki resource limit.

Karena itu V1:

- tidak memakai Elasticsearch;
- tidak memakai Redis wajib;
- tidak memakai long-running daemon;
- tidak memakai realtime polling berat;
- tidak menjalankan frontend dev server;
- tidak melakukan processing media berat tanpa kebutuhan.

Jika traffic/resource kebutuhan tumbuh, evaluasi upgrade hosting/VPS secara terpisah.

---

# 51. Deployment Security Rules for AI Agent

Agent tidak boleh:

1. memasukkan `.env` ke Git;
2. menjalankan `migrate:fresh` production;
3. menjalankan `db:wipe` production;
4. mengganti APP_KEY tanpa instruksi;
5. menjalankan `composer update` production sebagai release normal;
6. menyalakan `APP_DEBUG=true` secara permanen;
7. membuat seluruh project permission 777;
8. menaruh Laravel application root secara publik tanpa mitigasi;
9. menghapus production data untuk memperbaiki migration;
10. menebak credential/path server.

Agent harus meminta/menunggu value production yang memang hanya diketahui pemilik saat deployment nyata.

---

# 52. Deployment Completion Report

Setelah deployment, catat:

```text
Environment:
Production

Release:
Git commit/tag

PHP:
8.4.x

Migration:
PASS / FAIL

Frontend Build:
PASS / FAIL

Storage:
PASS / FAIL

Cron:
PASS / FAIL

Admin Login:
PASS / FAIL

Draft Protection:
PASS / FAIL

Scheduled Publish:
PASS / FAIL

HTTPS:
PASS / FAIL

APP_DEBUG:
false

Known Issues:
None / ...
```

---

# 53. Final Production Gate

Production release hanya PASS jika:

```text
Tests            PASS
Build            PASS
Database         PASS
Migration        PASS
Storage          PASS
Authentication   PASS
Draft Security   PASS
Scheduler/Cron   PASS
HTTPS            PASS
APP_DEBUG=false
Smoke Test       PASS
```

Jika Scheduled Article merupakan fitur aktif V1, cron yang belum berfungsi adalah release blocker.

---

# 54. Current Official Platform Notes

Dokumen ini disusun untuk Hostinger Premium Shared Hosting.

Platform detail seperti:

- menu hPanel;
- exact SSH path;
- PHP binary path;
- Composer command alias;
- cron UI;

dapat berubah dari waktu ke waktu.

Saat deployment nyata, verifikasi detail platform menggunakan dokumentasi Hostinger terbaru dan nilai yang ditampilkan pada akun hosting.

Jangan menebak absolute path production.

---

# 55. Source of Truth

Architecture:

```text
docs/03-ARCHITECTURE.md
```

Database:

```text
docs/04-DATABASE.md
```

Routes:

```text
docs/05-ROUTES.md
```

Testing:

```text
docs/08-TEST-PLAN.md
```

Security:

```text
docs/09-SECURITY.md
```

Deployment:

```text
docs/10-DEPLOYMENT.md
```

Jika deployment requirement bertentangan dengan security rule, security rule memiliki prioritas.
