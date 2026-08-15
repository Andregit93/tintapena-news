# TINTAPENA — Security Specification V1

Dokumen ini menetapkan baseline keamanan TINTAPENA V1.

Tujuan:

- melindungi Newsroom dan akun admin;
- mencegah kebocoran Draft/Scheduled content;
- melindungi form publik dan upload media;
- menjaga credential dan configuration tetap aman;
- memastikan deployment di Hostinger Premium Shared Hosting tetap mengikuti praktik keamanan Laravel.

Dokumen terkait:

- `docs/01-PRD.md`
- `docs/02-FEATURES.md`
- `docs/03-ARCHITECTURE.md`
- `docs/04-DATABASE.md`
- `docs/05-ROUTES.md`
- `docs/07-ACCEPTANCE-CRITERIA.md`
- `docs/08-TEST-PLAN.md`

---

# 1. Security Principles

Prinsip keamanan V1:

1. deny by default untuk area admin;
2. public route hanya menampilkan konten yang memang Published;
3. semua input dianggap tidak terpercaya;
4. credential rahasia tidak boleh disimpan di repository;
5. gunakan proteksi native Laravel terlebih dahulu;
6. jangan menambah package keamanan tanpa kebutuhan nyata;
7. error production tidak boleh membocorkan detail internal;
8. perubahan security-sensitive wajib memiliki test.

---

# 2. Authentication Scope

V1 hanya memiliki:

```text
Admin / Redaksi Internal
```

Tidak ada:

```text
Public Registration
Reader Account
Public Login
Social Login
```

Newsroom berada di:

```text
/admin
```

Seluruh route admin wajib membutuhkan authentication.

---

# 3. Admin Login

Login admin harus menggunakan mekanisme authentication Laravel/Filament.

Password:

- tidak disimpan plain text;
- harus menggunakan hashing Laravel;
- tidak boleh ditampilkan kembali dari database;
- tidak boleh dicatat ke log.

Pesan error login tidak perlu mengungkap apakah email tertentu terdaftar.

---

# 4. Admin Account Provisioning

Karena V1 tidak memiliki public registration:

admin dibuat melalui salah satu mekanisme internal yang disetujui, misalnya:

- database seeder development;
- Artisan command;
- provisioning manual yang aman.

Credential production:

- tidak boleh hardcoded;
- tidak boleh dimasukkan ke Git;
- development credential tidak boleh otomatis menjadi production credential.

---

# 5. Session Security

Session admin harus mengikuti konfigurasi Laravel yang aman.

Production harus menggunakan HTTPS.

Cookie session production harus menggunakan setting yang sesuai seperti:

```text
Secure
HttpOnly
SameSite
```

sesuai kemampuan konfigurasi framework dan environment.

Logout harus mengakhiri session authentication.

---

# 6. Authorization

Authentication dan authorization adalah dua hal berbeda.

Walaupun V1 hanya memiliki satu tipe admin, area Newsroom tetap harus dilindungi.

Guest:

```text
/admin/*
→ DENIED
```

Public route tidak boleh menggunakan request parameter untuk melewati authorization.

---

# 7. Article Visibility Security

Ini adalah security rule kritis.

Public Article hanya valid jika:

```text
status = published
AND
published_at <= now()
```

Konten berikut tidak boleh terekspos public:

```text
draft
scheduled yang belum waktunya
archived pada feed normal
```

Rule ini tidak boleh hanya bergantung pada UI.

Protection harus diterapkan pada query/backend.

---

# 8. Draft Security

Draft tidak boleh dapat dibuka melalui:

```text
/berita/{slug}
```

Jika slug Draft diketahui oleh orang lain, public route tetap harus menghasilkan response non-public, umumnya:

```text
404
```

Jangan menggunakan:

```text
hidden button
CSS display:none
frontend-only restriction
```

sebagai mekanisme keamanan Draft.

---

# 9. Scheduled Article Security

Scheduled Article di masa depan harus tetap non-public.

Contoh:

```text
status = scheduled
scheduled_at = tomorrow
```

harus:

```text
public route → 404
search → excluded
latest → excluded
category → excluded
region → excluded
tag → excluded
sitemap → excluded
homepage → excluded
```

---

# 10. Preview Security

Preview digunakan admin untuk melihat Draft sebelum Published.

Preview:

- harus membutuhkan authentication;
- tidak menggunakan public article route;
- tidak boleh muncul pada sitemap;
- tidak boleh diindex search engine;
- sebaiknya mengirim directive `noindex, nofollow`.

Target route:

```text
/admin/berita/{article}/preview
```

Preview URL tidak boleh dianggap sebagai shareable public URL.

---

# 11. CSRF Protection

Semua form mutating berbasis web harus menggunakan CSRF protection Laravel.

Contoh:

```text
POST
PUT
PATCH
DELETE
```

Jangan menonaktifkan CSRF secara global.

Jika suatu endpoint memang perlu dikecualikan di masa depan, harus memiliki alasan terdokumentasi.

---

# 12. Input Validation

Semua input harus divalidasi server-side.

Frontend validation hanya untuk UX.

Validation wajib untuk minimal:

- login;
- article;
- category;
- region;
- tag;
- media;
- advertisement;
- page;
- settings;
- contact form.

Agent tidak boleh menganggap data dari Filament otomatis aman tanpa rules yang sesuai.

---

# 13. Output Escaping

Blade escaped output digunakan secara default untuk text biasa.

Gunakan:

```text
{{ $value }}
```

untuk content yang tidak dimaksudkan sebagai trusted HTML.

Raw output seperti:

```text
{!! $value !!}
```

hanya boleh digunakan jika:

- field memang rich text;
- content berasal dari sumber admin terpercaya;
- sanitization/allowlist strategy sudah ditentukan.

---

# 14. Rich Text Security

Article content dan Static Page content dapat berupa rich text.

Risiko utama:

```text
Stored XSS
```

Karena itu:

- jangan menerima arbitrary script tag dari editor;
- jangan menerima event handler HTML seperti `onclick`;
- jangan menerima javascript URL;
- gunakan editor/sanitization yang membatasi markup;
- script iklan diperlakukan berbeda dari article rich text.

---

# 15. Advertisement Script Security

`advertisements.type = script` merupakan area berisiko tinggi.

Hanya admin yang boleh mengelolanya.

Script advertisement:

- tidak boleh dapat dikirim dari public form;
- tidak boleh dieksekusi di Newsroom preview secara sembarangan;
- harus dipisahkan dari article content;
- hanya digunakan untuk provider iklan yang memang disetujui.

Jika V1 belum membutuhkan script ad, lebih aman menggunakan image ads terlebih dahulu.

---

# 16. Media Upload Security

Upload media wajib divalidasi server-side.

Minimal cek:

- MIME type;
- extension;
- ukuran file;
- jenis file yang diizinkan.

Untuk V1, media artikel sebaiknya dibatasi pada format gambar yang memang diperlukan.

Jangan mengizinkan executable file.

Jangan mempercayai `original_filename` sebagai nama file storage.

---

# 17. Media Filename

File yang disimpan harus menggunakan nama yang aman.

Jangan menggunakan user-controlled filename langsung sebagai path final.

Database tetap dapat menyimpan:

```text
original_filename
```

untuk metadata.

Storage filename dapat menggunakan:

```text
generated UUID
hash
random filename
```

sesuai implementasi.

---

# 18. Media Storage

Gunakan Laravel Filesystem.

File public hanya ditempatkan pada lokasi yang memang boleh dibaca publik.

File sensitif tidak boleh disimpan di public web root.

Untuk media berita:

```text
public disk
```

dapat digunakan jika file memang dimaksudkan untuk public access.

---

# 19. Upload Size

Tetapkan maximum file size yang realistis untuk shared hosting.

Validation aplikasi harus konsisten dengan:

- PHP upload limit;
- PHP post limit;
- Hostinger environment.

Jika server menolak file sebelum Laravel menerima request, UI harus tetap memberikan panduan yang masuk akal kepada admin.

---

# 20. Contact Form Security

Contact form adalah endpoint public dan harus dianggap target spam.

Wajib:

- server-side validation;
- CSRF;
- rate limiting;
- panjang input dibatasi;
- email divalidasi;
- message tidak boleh dieksekusi sebagai HTML.

Status awal pesan:

```text
unread
```

---

# 21. Contact Rate Limiting

Route:

```text
POST /kontak
```

harus menggunakan throttling.

Tujuan:

- mengurangi spam;
- mengurangi abuse;
- mengurangi request berlebihan.

Nilai limit final dapat disesuaikan saat implementasi.

Jangan membuat limit terlalu agresif hingga pengguna normal sulit mengirim pesan.

---

# 22. Search Input

Search query:

```text
/cari?q=...
```

harus diperlakukan sebagai untrusted input.

Gunakan query builder/Eloquent parameter binding.

Jangan menyusun SQL mentah dengan concatenation input user.

---

# 23. SQL Injection Protection

Prioritaskan:

```text
Eloquent
Query Builder
parameter binding
```

Hindari raw SQL.

Jika raw query diperlukan:

- gunakan binding;
- jangan interpolasi input request secara langsung.

---

# 24. Mass Assignment

Model harus mengikuti aturan mass assignment Laravel.

Gunakan:

```text
$fillable
```

atau strategi yang disengaja.

Jangan membiarkan field sensitif berubah hanya karena request membawa nama field tersebut.

Contoh field yang tidak boleh editable public:

```text
views_count
author_id tanpa authorization
status tanpa workflow
published_at tanpa workflow
```

---

# 25. Article Status Changes

Perubahan status artikel sebaiknya dilakukan melalui workflow/action yang jelas.

Contoh:

```text
PublishArticle
ScheduleArticle
ArchiveArticle
```

Jangan menerima arbitrary:

```text
status=request('status')
```

tanpa validation dan business rule.

---

# 26. Slug Security

Slug harus divalidasi dan unik.

Slug tidak boleh memungkinkan traversal/path manipulation.

Gunakan slug normal yang hanya berisi karakter URL-safe sesuai implementasi.

Contoh:

```text
harga-timah-bangka-belitung
```

---

# 27. Settings Security

Tabel `settings` hanya untuk configuration non-sensitive.

Boleh:

```text
site name
tagline
contact email
WhatsApp
social URLs
SEO defaults
analytics public identifier
```

Tidak boleh:

```text
APP_KEY
DB_PASSWORD
SMTP_PASSWORD
API_SECRET
OAuth Client Secret
private token
```

Secret berada di:

```text
.env
```

---

# 28. Environment File

`.env`:

- tidak boleh di-commit;
- tidak boleh dimasukkan ke ZIP source publik;
- tidak boleh dikirim ke frontend;
- tidak boleh ditampilkan pada error page;
- tidak boleh disimpan ke `docs/`.

`.env.example` boleh disimpan tanpa secret nyata.

---

# 29. APP_KEY

Production wajib memiliki:

```text
APP_KEY
```

yang valid dan unik.

Jangan mengganti APP_KEY production setelah aplikasi memiliki encrypted data/session tanpa memahami dampaknya.

APP_KEY tidak boleh dibagikan ke public.

---

# 30. Debug Mode

Production wajib:

```text
APP_DEBUG=false
```

Development dapat:

```text
APP_DEBUG=true
```

sesuai kebutuhan lokal.

Stack trace production tidak boleh tampil ke public user.

---

# 31. Environment Mode

Production:

```text
APP_ENV=production
```

Development:

```text
APP_ENV=local
```

Testing:

```text
APP_ENV=testing
```

Jangan menjalankan production seolah-olah development.

---

# 32. HTTPS

Production harus menggunakan HTTPS.

HTTP production sebaiknya diarahkan ke HTTPS.

HTTPS penting untuk:

- login;
- session;
- form;
- admin;
- credential transport.

---

# 33. Database Credential

Database production credential hanya disimpan pada environment server.

Jangan:

- hardcode di source;
- menaruh di migration;
- menaruh di seeder;
- menaruh di docs;
- menaruh di GitHub issue/public notes.

---

# 34. Database User Privilege

Jika hosting memungkinkan, gunakan database user aplikasi yang hanya memiliki permission yang memang dibutuhkan aplikasi.

Jangan menggunakan credential yang memberikan akses server/database lebih luas dari kebutuhan.

---

# 35. Database Backup

Backup production harus dilakukan secara rutin.

Backup harus mencakup minimal:

- database;
- media penting;
- configuration yang diperlukan untuk recovery.

Backup tidak boleh disimpan hanya pada lokasi server yang sama jika tersedia opsi lokasi lain.

Detail operasional akan dibahas di:

```text
docs/10-DEPLOYMENT.md
```

---

# 36. Error Handling

404:

- tidak mengungkap internal path;
- tidak mengungkap query/database detail.

500:

- generic production response;
- detail error masuk log server;
- public tidak melihat stack trace.

---

# 37. Logging

Log boleh berisi informasi teknis yang diperlukan.

Jangan log:

- password;
- session token;
- DB password;
- API secret;
- full credential;
- raw authorization header.

Jika data personal user masuk log, simpan seminimal mungkin.

---

# 38. Log Access

Production log tidak boleh dapat diakses melalui public URL.

Folder:

```text
storage/logs
```

harus berada di luar public exposure normal Laravel.

---

# 39. Admin URLs

URL `/admin` bukan security mechanism.

Walaupun URL diketahui publik, authentication tetap harus mencegah access.

Jangan mengandalkan "admin URL sulit ditebak".

---

# 40. Route Security

Public route:

```text
/
```

Admin route:

```text
/admin/*
```

Preview:

```text
/admin/berita/{article}/preview
```

Static page catch-all:

```text
/{page:slug}
```

Catch-all route tidak boleh menangkap:

```text
/admin
/berita
/kategori
/wilayah
/topik
/cari
/kontak
/terbaru
/terpopuler
/sitemap.xml
```

Route ordering adalah bagian dari security/correctness.

---

# 41. Sitemap Security

Sitemap hanya boleh mengandung URL yang memang public.

Jangan masukkan:

```text
Draft
Scheduled future
Admin
Preview
internal endpoint
```

---

# 42. Search Engine Indexing

Public Published content:

```text
indexable sesuai SEO rules
```

Admin/preview:

```text
noindex
```

Jangan mengandalkan robots.txt sebagai satu-satunya protection untuk private content.

Private content harus benar-benar dibatasi server-side.

---

# 43. Popular View Tracking

View counter tidak boleh menjadi endpoint bebas yang dapat mengubah arbitrary article tanpa validation.

Jika menggunakan request-based increment:

- article harus valid;
- artikel harus Published;
- update dilakukan melalui controlled application logic.

Tidak perlu membuat anti-fraud analytics kompleks pada V1.

---

# 44. Homepage Slot Security

Homepage Manager hanya admin.

Slot hanya boleh menunjuk artikel yang valid sesuai business rule.

Jangan memperbolehkan public request menentukan:

```text
homepage_slots.article_id
```

---

# 45. Breaking News Security

Breaking News Manager hanya admin.

Manual target URL harus divalidasi.

Jika external URL diperbolehkan:

- gunakan valid URL;
- jangan menerima `javascript:` URI;
- render link secara aman.

---

# 46. External Links

Untuk link eksternal:

- URL harus valid;
- jangan menerima dangerous schemes;
- jika membuka tab baru, gunakan rel yang sesuai bila diperlukan.

Social settings juga harus divalidasi sebagai URL yang masuk akal.

---

# 47. Analytics

Analytics identifier boleh disimpan di settings jika memang public identifier.

Secret analytics/API credential tidak boleh berada di database settings atau frontend HTML.

Script hanya dirender jika konfigurasi tersedia dan valid.

---

# 48. Dependencies

Dependency baru harus:

- memiliki kebutuhan nyata;
- kompatibel dengan Laravel/PHP project;
- tidak menggantikan native Laravel tanpa alasan;
- tidak memperkenalkan infrastructure berat yang tidak dibutuhkan.

Jangan install package hanya karena agent lebih familiar dengan package tersebut.

---

# 49. Composer / NPM Security

Sebelum release:

- dependency lock file harus konsisten;
- jangan commit `vendor/` jika workflow repository tidak membutuhkannya;
- jangan commit `node_modules/`;
- hindari package tidak terpakai;
- update dependency dilakukan secara terkontrol, bukan otomatis tanpa test.

---

# 50. Git Security

Repository tidak boleh berisi:

```text
.env
production credentials
database dump production
private key
API secret
SMTP password
OAuth secret
```

`.gitignore` harus diperiksa sebelum initial/major commit.

---

# 51. Production File Permissions

File/folder permissions production harus sesempit mungkin sambil tetap membuat Laravel berjalan.

Laravel membutuhkan write access pada lokasi seperti:

```text
storage/
bootstrap/cache/
```

Jangan memberikan writable permission ke seluruh project tanpa kebutuhan.

---

# 52. Public Directory

Web document root idealnya menunjuk ke:

```text
public/
```

Bukan root Laravel.

Tujuannya agar file seperti:

```text
.env
composer.json
storage/
vendor/
```

tidak dapat diakses langsung dari web.

Untuk Hostinger, deployment harus menyesuaikan struktur hosting tanpa mengekspos root project.

---

# 53. Scheduler Security

Cron/Scheduler hanya menjalankan command aplikasi yang diperlukan.

Scheduled publishing harus:

- idempotent;
- tidak publish Draft yang tidak dijadwalkan;
- tidak memproses ulang Published secara merusak.

Cron command/entry tidak boleh menyertakan secret secara terbuka jika tidak diperlukan.

---

# 54. Queue Security

V1 tidak bergantung pada long-running queue worker.

Jika database queue dipakai:

- job input harus tervalidasi dari application layer;
- jangan serialize secret yang tidak perlu;
- failed job tidak boleh membocorkan credential.

---

# 55. Cache Security

Cache tidak boleh membuat private/admin content menjadi public.

Jika cache digunakan pada homepage/article listing:

- key harus jelas;
- hanya Published content yang dicache;
- perubahan status harus meng-invalidasi data yang sesuai.

Jangan cache Preview sebagai public page.

---

# 56. Rate Limiting

Minimum rate-limited V1:

```text
Contact Form
```

Login dapat mengikuti protection Filament/Laravel dan dapat diperkuat dengan throttle bila diperlukan.

Endpoint public lain hanya diberi rate limit tambahan jika memang ada risiko abuse yang nyata.

---

# 57. Security Tests — P0

P0 tests wajib:

1. guest tidak dapat `/admin`;
2. login invalid gagal;
3. Draft public = 404;
4. Scheduled future public = 404;
5. Preview guest = denied;
6. Published Article public = 200;
7. contact invalid tidak tersimpan;
8. contact throttle bekerja;
9. invalid upload ditolak;
10. public registration tidak tersedia.

---

# 58. Security Tests — Deployment

Sebelum production:

```text
APP_ENV=production
APP_DEBUG=false
HTTPS active
.env protected
admin auth active
storage path protected
public document root verified
```

Wajib cek juga bahwa:

```text
/.env
/storage/logs/...
/composer.json
```

tidak dapat dibaca sebagai public asset.

---

# 59. Security Incident Baseline

Jika ditemukan:

- credential bocor;
- Draft terekspos;
- admin bypass;
- malicious upload;
- stored XSS;
- database corruption;

tindakan awal:

1. hentikan exposure;
2. simpan evidence/log relevan;
3. revoke/rotate credential jika perlu;
4. perbaiki root cause;
5. tambahkan regression test;
6. dokumentasikan perubahan.

---

# 60. Secret Rotation

Jika secret pernah:

- masuk Git;
- terkirim ke tempat publik;
- terlihat di screenshot publik;
- masuk log yang dapat diakses pihak lain;

anggap secret compromised.

Lakukan rotation.

Menghapus secret dari file terbaru saja belum tentu cukup jika secret pernah masuk history repository.

---

# 61. Admin Operational Rules

Admin disarankan:

- menggunakan password unik;
- tidak berbagi account;
- logout dari device publik;
- menjaga device kerja;
- tidak membuka link mencurigakan melalui session admin;
- tidak meng-upload file yang sumbernya tidak dipercaya.

---

# 62. Security Scope V1

Tidak termasuk V1:

```text
Multi-factor Authentication custom
Enterprise SSO
WAF custom
SIEM
Intrusion Detection System
Complex RBAC
Reader Account Security
Payment Security
Subscription Security
```

Fitur tersebut dapat dipertimbangkan setelah V1 bila kebutuhan meningkat.

---

# 63. AI Agent Security Rules

AI agent wajib:

1. tidak membuat public registration;
2. tidak membuka `/admin` tanpa auth;
3. tidak menonaktifkan CSRF untuk mempermudah implementasi;
4. tidak menggunakan raw SQL dengan input user;
5. tidak memasukkan secret ke source;
6. tidak commit `.env`;
7. tidak membuat Draft accessible melalui public route;
8. tidak merender arbitrary user HTML tanpa alasan;
9. tidak mengizinkan arbitrary file upload;
10. tidak mengubah security requirement tanpa dokumentasi;
11. menambahkan regression test untuk security bug penting;
12. melaporkan bila requirement meminta behavior yang berisiko.

---

# 64. Security Review Checklist

Sebelum sebuah feature security-sensitive dianggap PASS, cek:

```text
[ ] Authentication
[ ] Authorization
[ ] Validation
[ ] CSRF
[ ] Output escaping
[ ] Data visibility
[ ] Upload safety
[ ] Route exposure
[ ] Secret handling
[ ] Test coverage
```

Tidak semua item berlaku pada setiap feature, tetapi harus ditinjau.

---

# 65. Release Security Gate

V1 tidak boleh production release jika salah satu kondisi berikut terjadi:

```text
Draft dapat diakses publik
Scheduled future dapat diakses publik
/admin dapat diakses guest
APP_DEBUG=true
credential production ada di repository
.env dapat diakses via web
upload executable dapat lolos
contact form tanpa validation
known stored XSS belum diperbaiki
```

---

# 66. Source of Truth

Product behavior:

```text
docs/01-PRD.md
docs/02-FEATURES.md
```

Architecture:

```text
docs/03-ARCHITECTURE.md
```

Database:

```text
docs/04-DATABASE.md
docs/database/erd.dbml
```

Routes:

```text
docs/05-ROUTES.md
```

Acceptance:

```text
docs/07-ACCEPTANCE-CRITERIA.md
```

Testing:

```text
docs/08-TEST-PLAN.md
```

Security:

```text
docs/09-SECURITY.md
```

Jika implementation bertentangan dengan security rule kritis, implementation harus diperbaiki sebelum feature dinyatakan PASS.
