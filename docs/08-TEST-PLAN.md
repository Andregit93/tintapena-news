# TINTAPENA — Test Plan V1

Dokumen ini mendefinisikan strategi pengujian TINTAPENA V1.

Tujuan utama:

- memastikan business rule berjalan benar;
- mencegah regression;
- memastikan public website dan Newsroom bekerja pada desktop dan mobile;
- memberi standar objektif bagi developer dan AI agent sebelum fitur dinyatakan PASS.

Dokumen terkait:

- `docs/01-PRD.md`
- `docs/02-FEATURES.md`
- `docs/03-ARCHITECTURE.md`
- `docs/04-DATABASE.md`
- `docs/05-ROUTES.md`
- `docs/06-DESIGN-HANDOFF.md`
- `docs/07-ACCEPTANCE-CRITERIA.md`

---

# 1. Testing Stack

Gunakan:

- Pest sebagai test runner utama;
- Laravel testing utilities;
- database test environment terpisah;
- browser/manual visual QA untuk tampilan;
- Laravel Scheduler test untuk scheduled publishing.

Prioritas utama V1:

```text
Feature Tests
```

Unit test hanya digunakan jika business logic tertentu memang lebih tepat diuji terpisah.

---

# 2. Testing Principles

1. Test business rule, bukan implementation detail yang tidak penting.
2. Setiap Feature ID yang mengubah behavior harus memiliki test relevan.
3. Public content test harus memastikan hanya konten valid yang terekspos.
4. Jangan bergantung pada urutan test.
5. Test harus dapat dijalankan ulang.
6. Test tidak boleh menggunakan production database.
7. Gunakan factory/seeder khusus testing.
8. Jangan menganggap UI selesai hanya karena backend test PASS.
9. Visual QA tetap wajib untuk screen yang memiliki design reference.
10. Bug yang pernah ditemukan dan penting sebaiknya mendapat regression test.

---

# 3. Test Environment

Environment testing harus menggunakan:

```text
APP_ENV=testing
```

Gunakan database terpisah dari development/production.

Pilihan:

- SQLite in-memory jika seluruh behavior kompatibel;
- atau MySQL testing database jika behavior perlu identik dengan production.

Karena production menggunakan MySQL, test yang menyangkut:

- enum/status;
- index;
- foreign key;
- query agregasi;
- date/time;
- full-text/search behavior tertentu;

lebih aman diverifikasi juga pada MySQL.

---

# 4. Test Directory Convention

Target struktur:

```text
tests/

Feature/
    Auth/
    Admin/
    Articles/
    Media/
    Homepage/
    BreakingNews/
    Public/
    Search/
    Ads/
    Pages/
    Contact/
    Settings/
    System/

Unit/
    Actions/
    Models/
```

Nama test harus menjelaskan behavior.

Contoh:

```text
AdminCanCreateDraftTest
DraftArticleIsNotPublicTest
ScheduledArticlePublishesWhenDueTest
PublishedArticleAppearsOnLatestPageTest
```

Atau gunakan descriptive Pest syntax.

---

# 5. Factory Requirements

Minimal factory:

```text
UserFactory
ArticleFactory
CategoryFactory
RegionFactory
TagFactory
MediaFactory
HomepageSlotFactory
BreakingNewsFactory
AdvertisementFactory
PageFactory
ContactMessageFactory
```

Article Factory harus mendukung state:

```text
draft()
scheduled()
published()
archived()
```

Contoh penggunaan:

```php
Article::factory()->published()->create();
```

Test tidak boleh mengulang setup status artikel secara manual di banyak file jika dapat menggunakan factory state.

---

# 6. AUTH TESTS

## AUTH-001 — Admin Login

Test:

- admin dapat membuka `/admin/login`;
- login berhasil dengan credential valid;
- login gagal dengan password salah;
- guest tidak dapat membuka `/admin`;
- guest diarahkan ke login;
- tidak tersedia route registrasi publik.

Expected:

```text
PASS
```

jika seluruh behavior sesuai.

## AUTH-002 — Logout

Test:

- authenticated admin dapat logout;
- session berakhir;
- setelah logout `/admin` tidak dapat diakses tanpa login kembali.

---

# 7. DASHBOARD TESTS

## DASH-001

Test bahwa dashboard:

- membutuhkan authentication;
- menghitung Published dengan benar;
- menghitung Draft dengan benar;
- menghitung Scheduled dengan benar;
- menampilkan data berita terbaru;
- tidak menggunakan angka hardcoded.

Visual test manual:

- desktop;
- mobile.

---

# 8. CATEGORY TESTS

## CATEGORY-001

Test:

- admin dapat melihat daftar kategori;
- guest tidak dapat mengakses management.

## CATEGORY-002

Test:

- category valid dapat dibuat;
- name wajib;
- slug unik;
- duplicate slug ditolak.

## CATEGORY-003

Test:

- category dapat diedit;
- article relationship tetap ada setelah edit.

## CATEGORY-004

Test:

- category dapat dinonaktifkan;
- article lama tidak terhapus;
- inactive category tetap tersedia untuk historical data.

---

# 9. REGION TESTS

## REGION-001

Test daftar wilayah dapat diakses admin.

## REGION-002

Test:

- region valid dapat dibuat;
- name wajib;
- slug unik.

## REGION-003

Test:

- region dapat diedit;
- relationship artikel tetap aman.

---

# 10. TAG TESTS

## TAG-001

Test daftar tag.

## TAG-002

Test:

- tag valid dapat dibuat;
- duplicate slug ditolak.

## TAG-003

Test edit tag tidak merusak pivot article_tag.

## TAG-004

Test:

- unused tag dapat dihapus;
- used tag tidak dihapus secara tidak aman.

---

# 11. ARTICLE CORE TESTS

## ARTICLE-001 — Create Draft

Test:

- authenticated admin dapat membuat Draft;
- status tersimpan `draft`;
- artikel tersimpan dengan category;
- region boleh null;
- banyak tag dapat disimpan;
- Draft tidak dapat dibuka melalui public route;
- Draft tidak muncul pada latest;
- Draft tidak muncul pada search;
- Draft tidak masuk sitemap.

Critical regression test:

```text
Draft must never become public accidentally.
```

---

## ARTICLE-002 — Edit Draft

Test:

- Draft dapat diedit;
- perubahan tersimpan;
- status tetap Draft jika tidak dipublish/schedule;
- tag dapat diperbarui.

---

## ARTICLE-003 — Featured Image

Test:

- featured media dapat dihubungkan ke artikel;
- relation menghasilkan media yang benar;
- metadata alt/caption/photo credit tersedia;
- featured image dapat diganti.

---

## ARTICLE-004 — Classification

Test:

- article wajib punya category;
- region boleh null;
- tag many-to-many bekerja;
- duplicate pivot tidak tercipta.

---

# 12. ARTICLE PREVIEW TESTS

## ARTICLE-005

Test:

- authenticated admin dapat preview Draft;
- guest tidak dapat membuka preview;
- preview Draft tidak mengubah status;
- public `/berita/{slug}` tetap 404;
- response preview mengandung protection `noindex` atau equivalent.

---

# 13. ARTICLE PUBLISH TESTS

## ARTICLE-006

Test:

- Draft valid dapat dipublish;
- status menjadi `published`;
- `published_at` terisi;
- artikel dapat dibuka melalui public route;
- artikel muncul di Latest;
- artikel dapat masuk Category;
- artikel dapat masuk Region jika memiliki region;
- artikel dapat muncul Tag page;
- artikel dapat muncul Search.

Test tanggal:

- Published dengan `published_at` di masa depan tidak boleh dianggap public jika rule public query mensyaratkan `published_at <= now()`.

---

# 14. ARTICLE SCHEDULING TESTS

## ARTICLE-007

Test:

- artikel dapat menjadi `scheduled`;
- `scheduled_at` wajib untuk scheduled;
- scheduled future article tidak public;
- scheduler tidak publish artikel sebelum waktunya;
- scheduler publish artikel saat due;
- status berubah ke `published`;
- `published_at` sesuai rule;
- command dapat dijalankan dua kali tanpa corrupt data.

Test command target:

```text
articles:publish-scheduled
```

Gunakan time travel/fake time bila perlu.

---

# 15. ARTICLE ARCHIVE TESTS

## ARTICLE-008

Test:

- Published dapat diarsipkan;
- status menjadi `archived`;
- `archived_at` terisi;
- tidak muncul Latest;
- tidak muncul Category listing normal;
- tidak muncul Search;
- tidak masuk Popular normal.

Jika public detail untuk Archived diputuskan 404, test behavior tersebut secara eksplisit.

---

# 16. ARTICLE SEO TESTS

## ARTICLE-009

Test:

- slug unik;
- seo_title dapat disimpan;
- meta_description dapat disimpan;
- fallback title bekerja bila seo_title kosong;
- canonical URL benar.

Jika slug Published diubah, behavior redirect hanya dites jika fitur redirect memang dibuat.

---

# 17. ARTICLE LIST TESTS

## ARTICLE-010

Test admin filters:

- status;
- category;
- region;
- tanggal;
- keyword.

Test bahwa filter menghasilkan record yang tepat.

Visual QA:

- desktop table;
- mobile list/card.

---

# 18. MEDIA TESTS

## MEDIA-001

Test upload:

- valid image diterima;
- invalid MIME ditolak;
- file terlalu besar ditolak;
- metadata tersimpan;
- file tersimpan pada filesystem test disk.

Gunakan:

```text
Storage::fake(...)
```

bila sesuai.

## MEDIA-002

Test Media Library hanya dapat diakses admin.

## MEDIA-003

Test metadata:

- alt text;
- caption;
- photo credit;

dapat disimpan dan diperbarui.

## MEDIA-004

Test media existing dapat dipilih sebagai featured image tanpa upload baru.

---

# 19. PUBLIC ARTICLE TESTS

## PUBLIC-001

Test:

- Published Article = 200;
- Draft = 404;
- Scheduled future = 404;
- Archived = behavior sesuai spec;
- slug tidak ditemukan = 404;
- article detail menampilkan title yang benar;
- canonical link benar jika diimplementasikan;
- article relationship dimuat tanpa error.

## PUBLIC-002

Test related news:

- hanya Published;
- current article tidak ikut;
- hasil kosong tetap render tanpa error.

## PUBLIC-003

Test share link menggunakan canonical URL.

Tidak perlu menguji layanan eksternal WhatsApp/Facebook/X.

Yang diuji adalah URL/action yang dibangun aplikasi.

---

# 20. HOMEPAGE TESTS

## HOME-001

Test:

- `/` = 200;
- hanya Published Article tampil;
- Draft tidak tampil;
- Scheduled future tidak tampil;
- section data berasal dari query/database.

## HOME-002

Test:

- `headline_main` dapat menunjuk Published Article;
- Draft tidak dapat menjadi headline jika business validation diterapkan;
- perubahan slot tidak mengubah article status.

## HOME-003

Test supporting headline slot.

## HOME-004

Test editor pick slot.

## HOME-005

Test automatic section:

- Latest sorted benar;
- category section mengambil category yang benar;
- region section mengambil region yang benar;
- tidak hardcoded article ID.

---

# 21. BREAKING NEWS TESTS

## BREAKING-001

Test dapat membuat:

- internal article breaking item;
- manual headline + URL item.

## BREAKING-002

Test:

- inactive tidak tampil;
- active tampil.

## BREAKING-003

Gunakan fake time untuk menguji:

- sebelum `starts_at`;
- saat periode aktif;
- setelah `ends_at`.

## BREAKING-004

Test public ticker:

- hanya active item;
- link internal benar;
- manual target URL benar.

---

# 22. LATEST TESTS

## LIST-001

Test:

- route `/terbaru`;
- hanya Published;
- urutan `published_at DESC`;
- Draft tidak muncul;
- pagination bekerja.

---

# 23. POPULAR TESTS

## LIST-002

Test ranking:

- `24jam`;
- `7hari`;
- default `24jam`;
- hanya Published;
- ranking berubah berdasarkan `article_view_stats`;
- lifetime count tidak mengacaukan period ranking.

Gunakan fixture view stats dengan angka yang jelas.

---

# 24. CATEGORY PUBLIC TESTS

## LIST-003

Test:

- category slug valid = 200;
- unknown category = 404;
- hanya Published dari category target;
- artikel category lain tidak muncul.

---

# 25. REGION PUBLIC TESTS

## LIST-004

Test:

- region slug valid;
- unknown region = 404;
- hanya Published Article region tersebut;
- region null tidak ikut.

---

# 26. TAG PUBLIC TESTS

## LIST-005

Test:

- tag valid;
- tag unknown = 404;
- artikel dengan tag tampil;
- artikel tanpa tag tidak tampil;
- Draft dengan tag tidak tampil.

---

# 27. SEARCH TESTS

## SEARCH-001

Test query:

- title match;
- subtitle match jika didukung;
- excerpt match jika didukung;
- no Draft;
- no Scheduled future;
- no Archived.

## SEARCH-002

Test filter yang benar-benar diaktifkan pada V1.

Jangan membuat test untuk filter yang belum menjadi requirement final.

## SEARCH-003

Test empty result:

- status 200;
- empty state muncul;
- bukan exception.

---

# 28. ADS TESTS

## ADS-001

Test image/script ad dapat disimpan sesuai rule.

## ADS-002

Test placement:

- iklan `homepage_top` tidak muncul pada placement lain;
- placement component mengambil slot benar.

## ADS-003

Fake time:

- sebelum start tidak tampil;
- dalam periode tampil;
- setelah end tidak tampil;
- inactive tidak tampil.

## ADS-004

Test target URL dan media relation bila ada.

---

# 29. STATIC PAGE TESTS

## PAGE-001

Test:

- admin create Draft;
- edit;
- publish;
- SEO fields;
- slug unique.

## PAGE-002

Test:

- Published page = 200;
- Draft page = 404;
- static page catch-all tidak menangkap `/terbaru`;
- static page catch-all tidak menangkap `/cari`;
- static page catch-all tidak menangkap `/kontak`.

Critical regression:

```text
Specific public routes must win before static page catch-all.
```

---

# 30. CONTACT TESTS

## CONTACT-001

Test:

- `/kontak` = 200;
- contact info dapat memakai settings.

## CONTACT-002

Test valid request:

- record tersimpan;
- status default `unread`;
- success feedback tersedia.

Test invalid:

- name missing;
- invalid email;
- subject missing;
- message missing;
- record tidak tersimpan.

Test security:

- CSRF berlaku melalui web middleware;
- throttle diterapkan;
- spam request berlebih mendapatkan response yang sesuai.

---

# 31. SETTINGS TESTS

## SETTINGS-001

Test general settings disimpan dan dibaca.

## SETTINGS-002

Test contact settings.

## SETTINGS-003

Test social settings.

## SETTINGS-004

Test SEO fallback.

## SETTINGS-005

Test analytics:

- identifier tersimpan;
- empty value tidak merender script yang tidak perlu;
- sensitive secret tidak menjadi field settings.

---

# 32. RESPONSIVE TEST PLAN

## SYSTEM-001

Manual browser QA minimum:

Desktop:

```text
1440px
```

Mobile:

```text
390px
```

Cek:

- tidak ada accidental horizontal overflow;
- navigation bekerja;
- typography tidak terpotong;
- images maintain aspect ratio;
- cards/list tidak overlap;
- footer tidak rusak;
- interactive target usable.

Referensi:

```text
design-reference/public/
```

---

# 33. ADMIN MOBILE TEST PLAN

## SYSTEM-002

Manual QA pada mobile reference:

```text
design-reference/admin/
```

Prioritas:

- login;
- Dashboard;
- Article List;
- Article Editor;
- media selection;
- Draft;
- Preview;
- Publish;
- Schedule.

Critical action button harus tetap dapat digunakan tanpa desktop.

---

# 34. SCHEDULER SYSTEM TESTS

## SYSTEM-003

Test command secara langsung.

Scenario:

```text
Article A scheduled 1 hour future
Article B scheduled 5 minutes past
Article C already published
```

Expected:

```text
A remains scheduled
B becomes published
C unchanged
```

Command harus aman dijalankan berulang.

---

# 35. SITEMAP TESTS

## SYSTEM-004

Test:

- `/sitemap.xml` = 200;
- Content-Type sesuai XML;
- Published Article URL ada;
- Draft URL tidak ada;
- Scheduled future URL tidak ada;
- admin URL tidak ada;
- Published static page ada bila sesuai;
- XML dapat diparse.

---

# 36. ERROR HANDLING TESTS

## SYSTEM-005

Test:

- unknown route = 404;
- unknown article slug = 404;
- unknown category = 404;
- unknown region = 404;
- unknown tag = 404.

Manual production check:

```text
APP_DEBUG=false
```

Public error tidak boleh menampilkan stack trace sensitif.

---

# 37. DATABASE TESTS

Verifikasi migration:

- migration fresh berhasil;
- rollback sesuai kemampuan migration;
- foreign key terbentuk;
- unique constraint terbentuk;
- index penting terbentuk.

Command minimum sebelum release:

```text
php artisan migrate:fresh --seed
```

hanya pada environment development/testing.

Jangan jalankan `migrate:fresh` pada production.

---

# 38. SEEDER TESTS

Seeder minimum harus menghasilkan:

- admin development/testing;
- kategori awal;
- wilayah awal;
- homepage slots.

Test seeder harus memastikan slug dan slot tidak duplikat jika seeder dirancang idempotent.

Production admin credential tidak boleh hardcoded ke repository.

---

# 39. QUERY / N+1 CHECK

Manual/development review untuk halaman:

- homepage;
- latest;
- popular;
- category;
- region;
- tag;
- article detail.

Pastikan relation seperti:

- category;
- region;
- featuredMedia;
- tags;
- author;

di-eager-load jika memang dibutuhkan pada listing.

Target:

tidak ada N+1 yang jelas pada halaman utama.

---

# 40. CACHE TEST PLAN

Jika cache diterapkan:

Test bahwa perubahan:

- homepage slot;
- Breaking News;
- settings;
- ads;

tidak menyebabkan data lama tersimpan permanen.

Pastikan ada invalidation atau TTL yang sesuai.

Cache bukan alasan untuk test business rule dilewati.

---

# 41. SECURITY TEST PLAN

Wajib cek:

- guest tidak dapat admin;
- no public registration;
- CSRF pada form;
- validation;
- rate limiting contact;
- upload type/size;
- output escaping;
- no secret in response;
- no `.env` committed;
- production debug disabled.

Security detail tambahan ada pada:

```text
docs/09-SECURITY.md
```

setelah dokumen tersebut dibuat.

---

# 42. VISUAL QA PROCESS

Untuk setiap screen:

1. jalankan aplikasi;
2. buka target route;
3. buka PNG reference Desktop;
4. bandingkan layout;
5. cek header;
6. cek spacing;
7. cek hierarchy;
8. cek typography;
9. cek image ratio;
10. cek footer;
11. ulangi pada Mobile.

Gunakan:

```text
design-reference/manifest.json
```

untuk mapping screen.

PASS visual tidak berarti pixel-perfect absolut.

Yang wajib konsisten:

- layout;
- hierarchy;
- spacing system;
- typography system;
- responsive behavior;
- component structure;
- warna/tokens.

---

# 43. SMOKE TEST BEFORE COMMIT

Minimum sebelum feature commit:

```text
php artisan test
```

atau subset test relevan terlebih dahulu.

Contoh:

```text
php artisan test --filter=Article
```

Jika feature menyentuh database:

- migration test;
- relevant feature tests.

Jika feature menyentuh UI:

- relevant automated tests;
- desktop visual check;
- mobile visual check.

---

# 44. PRE-RELEASE TEST SUITE

Sebelum deploy V1:

1. seluruh automated test PASS;
2. migrate pada staging/test environment PASS;
3. seed test PASS;
4. admin login PASS;
5. create Draft PASS;
6. preview PASS;
7. publish PASS;
8. schedule PASS;
9. automatic scheduled publish PASS;
10. homepage PASS;
11. article detail PASS;
12. latest PASS;
13. popular PASS;
14. category PASS;
15. region PASS;
16. tag PASS;
17. search PASS;
18. breaking news PASS;
19. ads PASS;
20. static pages PASS;
21. contact PASS;
22. sitemap PASS;
23. desktop visual QA PASS;
24. mobile visual QA PASS;
25. error handling PASS;
26. production config review PASS.

---

# 45. TEST PRIORITY LEVEL

Gunakan prioritas:

```text
P0 — Critical
P1 — High
P2 — Normal
```

## P0

- authentication;
- Draft tidak public;
- Scheduled tidak public sebelum waktunya;
- Publish;
- Scheduler;
- database integrity;
- admin authorization;
- production secrets.

## P1

- homepage;
- latest;
- popular;
- article detail;
- media upload;
- category/region/tag;
- search;
- Breaking News;
- contact validation.

## P2

- minor visual states;
- secondary empty state;
- optional enhancements.

Semua P0 harus PASS sebelum release.

---

# 46. BUG REGRESSION RULE

Jika ditemukan bug yang:

- mengekspos Draft;
- merusak publish;
- merusak route;
- merusak scheduled publishing;
- menyebabkan data hilang;
- merusak authentication;
- merusak database relationship;

setelah diperbaiki harus dibuat regression test bila memungkinkan.

---

# 47. TEST DATA RULE

Gunakan data fiktif.

Jangan menggunakan:

- credential production;
- email private nyata;
- nomor WhatsApp produksi kecuali memang configuration test;
- API secret;
- database production dump;

dalam automated test repository.

---

# 48. AI AGENT TESTING RULES

AI agent wajib:

1. baca Feature ID yang dikerjakan;
2. baca Acceptance Criteria;
3. identifikasi test yang perlu dibuat sebelum/bersamaan dengan implementasi;
4. jalankan relevant test;
5. perbaiki failure yang disebabkan perubahan sendiri;
6. jangan menghapus test hanya agar suite PASS;
7. jangan mengubah expected behavior tanpa update specification;
8. laporkan test yang dijalankan;
9. laporkan test yang belum dapat dijalankan;
10. jangan menyatakan PASS jika test kritis belum lulus.

---

# 49. FEATURE COMPLETION TEMPLATE

Setelah feature selesai:

```text
Feature ID:
ARTICLE-006

Automated Tests:
PASS

Tests Run:
- publish article test
- published article public route test
- latest listing test

Visual QA:
Desktop: PASS
Mobile: PASS

Regression:
None

Known Issues:
None

Final Status:
PASS
```

---

# 50. V1 RELEASE GATE

TINTAPENA V1 dapat dinyatakan siap release jika:

```text
P0 Tests       = PASS
P1 Tests       = PASS atau memiliki waiver terdokumentasi
Migrations     = PASS
Security Check = PASS
Desktop QA     = PASS
Mobile QA      = PASS
Production Env = VERIFIED
```

Tidak boleh release jika terdapat known issue yang:

- mengekspos konten non-public;
- menghalangi admin publish;
- merusak database;
- membocorkan credential;
- menyebabkan website utama tidak dapat digunakan.
