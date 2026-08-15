# TINTAPENA — Acceptance Criteria V1

Dokumen ini menentukan kondisi PASS/FAIL untuk fitur TINTAPENA V1.

Setiap implementasi harus mengacu pada Feature ID di:

`docs/02-FEATURES.md`

Sebuah fitur belum dianggap selesai jika acceptance criteria terkait belum terpenuhi.

---

# 1. AUTHENTICATION

## AUTH-001 — Admin Login

PASS jika:

- halaman login tersedia di `/admin/login`;
- admin dapat login menggunakan email dan password yang valid;
- login gagal jika credential salah;
- user yang belum login tidak dapat mengakses `/admin`;
- setelah login berhasil, admin diarahkan ke Newsroom;
- tidak tersedia public registration.

## AUTH-002 — Admin Logout

PASS jika:

- admin dapat logout;
- session authentication diakhiri;
- setelah logout, akses `/admin` meminta login kembali.

---

# 2. DASHBOARD

## DASH-001 — Dashboard Newsroom

PASS jika dashboard dapat menampilkan:

- jumlah artikel Published;
- jumlah Draft;
- jumlah Scheduled;
- daftar berita terbaru;
- ringkasan artikel terpopuler;
- shortcut membuat berita;
- data berasal dari database, bukan hardcoded;
- layout dapat digunakan di desktop dan mobile.

---

# 3. CATEGORY

## CATEGORY-001 — Daftar Kategori

PASS jika:

- seluruh kategori dapat dilihat admin;
- daftar menampilkan nama, slug, dan status aktif;
- tersedia pencarian atau navigasi yang memadai bila data bertambah.

## CATEGORY-002 — Tambah Kategori

PASS jika:

- admin dapat membuat kategori;
- `name` wajib;
- `slug` unik;
- slug dapat dibuat otomatis dari nama;
- kategori tersimpan di database.

## CATEGORY-003 — Edit Kategori

PASS jika:

- admin dapat mengubah nama, slug, deskripsi, dan status;
- perubahan tersimpan;
- perubahan tidak menghapus artikel yang sudah terkait.

## CATEGORY-004 — Nonaktifkan Kategori

PASS jika:

- kategori dapat dibuat tidak aktif;
- artikel lama tetap tersimpan;
- kategori tidak aktif tidak diprioritaskan untuk pemilihan artikel baru.

---

# 4. REGION

## REGION-001 — Daftar Wilayah

PASS jika:

- admin dapat melihat seluruh wilayah;
- status aktif dapat diketahui.

## REGION-002 — Tambah Wilayah

PASS jika:

- admin dapat menambah wilayah;
- nama wajib;
- slug unik;
- data tersimpan.

## REGION-003 — Edit Wilayah

PASS jika:

- data wilayah dapat diperbarui;
- perubahan tidak menghapus artikel terkait.

---

# 5. TAG

## TAG-001 — Daftar Tag

PASS jika admin dapat melihat dan mencari tag.

## TAG-002 — Tambah Tag

PASS jika:

- admin dapat menambah tag;
- nama wajib;
- slug unik;
- data tersimpan.

## TAG-003 — Edit Tag

PASS jika tag dapat diperbarui tanpa merusak relasi artikel.

## TAG-004 — Hapus Tag Tidak Terpakai

PASS jika:

- tag yang tidak terhubung ke artikel dapat dihapus;
- tag yang masih digunakan tidak boleh dihapus secara tidak aman.

---

# 6. ARTICLE

## ARTICLE-001 — Create Draft

PASS jika admin dapat membuat artikel dengan:

- judul;
- subtitle opsional;
- slug;
- ringkasan;
- konten;
- kategori;
- wilayah opsional;
- tag opsional.

Ketika disimpan sebagai Draft:

- status = `draft`;
- artikel tersimpan;
- artikel tidak tampil pada website publik;
- artikel tidak masuk sitemap;
- artikel dapat dibuka kembali dari Newsroom.

## ARTICLE-002 — Edit Draft

PASS jika:

- admin dapat membuka Draft;
- seluruh field dapat diperbarui sesuai permission;
- perubahan tersimpan;
- status tetap Draft kecuali admin melakukan publish/schedule.

## ARTICLE-003 — Featured Image

PASS jika:

- admin dapat memilih gambar dari Media Library;
- gambar dapat memiliki alt text;
- caption dapat diisi;
- photo credit dapat diisi;
- featured image tampil pada artikel publik ketika artikel Published.

## ARTICLE-004 — Article Classification

PASS jika:

- artikel memiliki tepat satu kategori;
- artikel boleh memiliki satu wilayah atau kosong;
- artikel dapat memiliki banyak tag;
- relationship tersimpan dengan benar.

## ARTICLE-005 — Preview

PASS jika:

- admin dapat preview Draft;
- preview hanya dapat diakses user terautentikasi;
- preview tidak membuat artikel menjadi Published;
- preview tidak menggunakan public URL Draft;
- preview memiliki perlindungan `noindex`.

## ARTICLE-006 — Publish Article

PASS jika:

- admin dapat menerbitkan artikel yang valid;
- status berubah menjadi `published`;
- `published_at` terisi;
- artikel dapat dibuka melalui `/berita/{slug}`;
- artikel muncul di Berita Terbaru;
- artikel dapat ditemukan oleh query publik yang sesuai.

FAIL jika Draft tetap dapat diakses publik sebelum publish.

## ARTICLE-007 — Schedule Article

PASS jika:

- admin dapat menentukan waktu publikasi masa depan;
- status berubah menjadi `scheduled`;
- `scheduled_at` tersimpan;
- artikel tidak dapat diakses publik sebelum waktunya;
- Laravel Scheduler memproses artikel yang sudah jatuh tempo;
- setelah jatuh tempo status menjadi `published`;
- `published_at` terisi sesuai aturan sistem.

## ARTICLE-008 — Archive Article

PASS jika:

- admin dapat mengarsipkan artikel Published;
- status berubah menjadi `archived`;
- `archived_at` terisi;
- artikel tidak muncul pada feed publik normal;
- artikel Archived tidak muncul di Berita Terbaru.

## ARTICLE-009 — Article SEO

PASS jika admin dapat mengatur:

- slug;
- SEO title;
- meta description.

Jika SEO title kosong, sistem dapat menggunakan title artikel.

Jika meta description kosong, sistem menggunakan fallback yang ditetapkan.

## ARTICLE-010 — Article List

PASS jika daftar artikel admin dapat:

- menampilkan artikel;
- mencari berdasarkan keyword;
- filter status;
- filter kategori;
- filter wilayah;
- filter tanggal;
- membuka artikel untuk diedit;
- bekerja pada desktop dan mobile.

---

# 7. MEDIA

## MEDIA-001 — Upload Media

PASS jika:

- admin dapat upload gambar;
- MIME type divalidasi;
- ukuran file divalidasi;
- file tersimpan melalui Laravel Filesystem;
- metadata file tersimpan di database;
- upload invalid ditolak dengan pesan yang jelas.

## MEDIA-002 — Media Library

PASS jika:

- media yang sudah di-upload dapat dilihat;
- media dapat dipilih dari Newsroom;
- library tetap usable pada desktop dan mobile.

## MEDIA-003 — Media Metadata

PASS jika admin dapat mengubah:

- alt text;
- caption;
- photo credit.

Perubahan harus tersimpan dan digunakan pada render publik bila relevan.

## MEDIA-004 — Select Featured Image

PASS jika media yang dipilih dapat menjadi featured image artikel tanpa upload ulang.

---

# 8. PUBLIC ARTICLE

## PUBLIC-001 — Article Detail

PASS jika:

- Published Article dapat dibuka melalui `/berita/{slug}`;
- Draft menghasilkan 404 pada public route;
- Scheduled yang belum waktunya menghasilkan 404;
- Archived tidak tampil sebagai artikel normal;
- halaman mengikuti referensi Desktop dan Mobile;
- title, subtitle, author, waktu publikasi, featured image, caption, credit, body, tag tampil sesuai data;
- canonical URL benar.

## PUBLIC-002 — Related News

PASS jika:

- artikel publik menampilkan berita terkait;
- hanya artikel Published yang digunakan;
- artikel saat ini tidak muncul sebagai related item sendiri;
- hasil tetap valid bila related article tidak tersedia.

## PUBLIC-003 — Social Share

PASS jika tersedia:

- WhatsApp;
- Facebook;
- X;
- Salin Tautan.

URL share harus mengarah ke canonical article URL.

---

# 9. HOMEPAGE

## HOME-001 — Homepage Public

PASS jika:

- `/` dapat diakses publik;
- layout mengikuti referensi Desktop dan Mobile;
- hanya artikel Published yang tampil;
- section utama sesuai PRD;
- tidak ada mock content hardcoded sebagai production content.

## HOME-002 — Headline Utama

PASS jika:

- admin dapat memilih Published Article untuk `headline_main`;
- headline utama homepage berubah sesuai pilihan;
- pemilihan headline tidak mengubah status artikel;
- tidak menggunakan `articles.is_headline`.

## HOME-003 — Supporting Headlines

PASS jika:

- supporting headline dapat dikelola melalui homepage slots;
- hanya Published Article yang dapat dipilih;
- urutan sesuai slot.

## HOME-004 — Pilihan Redaksi

PASS jika:

- admin dapat mengatur artikel pada slot Pilihan Redaksi;
- artikel tampil sesuai urutan;
- hanya Published Article yang boleh digunakan.

## HOME-005 — Automatic News Sections

PASS jika section otomatis:

- menggunakan query database;
- hanya mengambil Published Article;
- urutan sesuai requirement section;
- tidak bergantung pada hardcoded article ID.

---

# 10. BREAKING NEWS

## BREAKING-001 — Create Breaking News

PASS jika admin dapat membuat Breaking News dari:

- artikel internal; atau
- headline + URL manual.

## BREAKING-002 — Activate Breaking News

PASS jika Breaking News dapat:

- diaktifkan;
- dinonaktifkan;
- status perubahan langsung tercermin pada ticker setelah cache terkait diperbarui.

## BREAKING-003 — Breaking Schedule

PASS jika:

- `starts_at` dapat digunakan;
- `ends_at` dapat digunakan;
- item sebelum waktu mulai tidak tampil;
- item setelah waktu selesai tidak tampil.

## BREAKING-004 — Breaking Ticker

PASS jika:

- ticker hanya menampilkan Breaking News aktif;
- periode waktu dihormati;
- link bekerja;
- layout responsive.

---

# 11. PUBLIC LISTING

## LIST-001 — Latest News

PASS jika:

- `/terbaru` dapat diakses;
- hanya artikel Published;
- urutan berdasarkan `published_at DESC`;
- pagination tersedia bila data melebihi limit;
- desktop dan mobile sesuai design reference.

## LIST-002 — Popular News

PASS jika:

- `/terpopuler` dapat diakses;
- filter `24jam` bekerja;
- filter `7hari` bekerja;
- hanya Published Article tampil;
- ranking berasal dari data view, bukan hardcoded;
- default periode = `24jam`.

## LIST-003 — Category Page

PASS jika:

- `/kategori/{slug}` bekerja;
- kategori yang valid menampilkan artikel Published kategori tersebut;
- artikel kategori lain tidak ikut;
- kategori tidak ditemukan menghasilkan 404.

## LIST-004 — Region Page

PASS jika:

- `/wilayah/{slug}` bekerja;
- hanya artikel Published wilayah tersebut tampil;
- artikel tanpa region tidak ikut;
- wilayah tidak ditemukan menghasilkan 404.

## LIST-005 — Tag Page

PASS jika:

- `/topik/{slug}` bekerja;
- hanya artikel Published dengan tag tersebut tampil;
- tag tidak ditemukan menghasilkan 404.

---

# 12. SEARCH

## SEARCH-001 — Search Articles

PASS jika:

- `/cari?q=...` bekerja;
- pencarian dapat menemukan artikel berdasarkan keyword;
- Draft, Scheduled belum waktunya, dan Archived tidak tampil.

## SEARCH-002 — Search Filters

PASS jika filter yang diaktifkan pada V1:

- memengaruhi hasil;
- dapat dikombinasikan dengan keyword bila didukung UI;
- tidak menghasilkan data non-public.

## SEARCH-003 — Empty Search State

PASS jika pencarian tanpa hasil menampilkan empty state yang sesuai desain, bukan error.

---

# 13. ADVERTISEMENT

## ADS-001 — Create Advertisement

PASS jika admin dapat membuat iklan dengan data yang sesuai tipe.

## ADS-002 — Advertisement Placement

PASS jika:

- iklan memiliki `placement_key`;
- iklan hanya tampil pada slot yang sesuai;
- satu placement tidak mengacaukan layout halaman lain.

## ADS-003 — Advertisement Schedule

PASS jika:

- iklan dapat diaktifkan/nonaktifkan;
- waktu mulai dihormati;
- waktu selesai dihormati.

## ADS-004 — Public Advertisement

PASS jika:

- iklan aktif tampil pada public placement;
- iklan tidak aktif tidak tampil;
- iklan di luar periode tidak tampil;
- target URL bekerja jika tersedia.

---

# 14. STATIC PAGES

## PAGE-001 — Manage Static Pages

PASS jika admin dapat:

- membuat halaman;
- menyimpan Draft;
- mengedit;
- publish;
- mengatur slug;
- mengatur SEO metadata.

## PAGE-002 — Public Static Page

PASS jika:

- Published Page dapat dibuka melalui slug;
- Draft Page tidak dapat diakses publik;
- layout menggunakan template reusable;
- catch-all route tidak mengambil route sistem seperti `/terbaru`.

---

# 15. CONTACT

## CONTACT-001 — Contact Page

PASS jika:

- `/kontak` dapat dibuka;
- informasi kontak berasal dari settings bila tersedia;
- layout sesuai Desktop dan Mobile reference.

## CONTACT-002 — Contact Form

PASS jika:

- user dapat mengirim nama;
- email;
- subject;
- message;
- validation bekerja;
- CSRF protection aktif;
- rate limiting aktif;
- pesan valid tersimpan pada `contact_messages`;
- user mendapat feedback sukses;
- submit invalid tidak membuat record.

---

# 16. SETTINGS

## SETTINGS-001 — General Settings

PASS jika admin dapat mengubah data seperti:

- site name;
- tagline;
- informasi dasar.

Perubahan digunakan oleh public website bila relevan.

## SETTINGS-002 — Contact Settings

PASS jika admin dapat mengubah:

- email;
- WhatsApp;
- alamat;
- jam kontak.

## SETTINGS-003 — Social Settings

PASS jika social link dapat diatur dan digunakan website.

## SETTINGS-004 — SEO Settings

PASS jika:

- default SEO title tersedia;
- default meta description tersedia;
- halaman dapat menggunakan fallback tersebut.

## SETTINGS-005 — Analytics Settings

PASS jika:

- analytics identifier dapat disimpan;
- script hanya dirender jika konfigurasi valid/tersedia;
- credential rahasia tidak disimpan di tabel settings.

---

# 17. SYSTEM

## SYSTEM-001 — Responsive Website

PASS jika seluruh public screen utama:

- dapat digunakan pada desktop;
- dapat digunakan pada mobile;
- tidak memiliki horizontal overflow yang tidak disengaja;
- navigation tetap usable;
- typography tetap terbaca;
- layout tidak rusak.

Referensi visual:

`design-reference/public/`

## SYSTEM-002 — Admin Mobile Support

PASS jika fungsi utama Newsroom dapat digunakan pada smartphone:

- login;
- daftar artikel;
- edit artikel;
- Draft;
- Preview;
- Publish;
- Schedule;
- media selection.

Referensi visual:

`design-reference/admin/`

## SYSTEM-003 — Scheduler

PASS jika:

- Laravel Scheduler dapat dijalankan;
- scheduled article yang belum waktunya tidak berubah;
- scheduled article yang sudah waktunya berubah menjadi Published;
- command dapat dijalankan idempotent tanpa publish berulang yang merusak data.

## SYSTEM-004 — Sitemap

PASS jika `/sitemap.xml`:

- dapat diakses;
- valid XML;
- berisi Published Articles;
- berisi halaman publik utama yang relevan;
- tidak berisi Draft;
- tidak berisi Scheduled belum waktunya;
- tidak berisi admin route.

## SYSTEM-005 — Error Handling

PASS jika minimal tersedia behavior layak untuk:

- 404;
- 500.

Production tidak boleh menampilkan stack trace sensitif kepada public user.

---

# 18. VISUAL ACCEPTANCE

Untuk screen yang memiliki file pada:

`design-reference/`

PASS jika:

- struktur utama sesuai screenshot;
- hierarchy visual sesuai;
- spacing secara visual konsisten;
- typography mengikuti `DESIGN.md`;
- warna mengikuti design tokens;
- desktop sesuai referensi desktop;
- mobile sesuai referensi mobile;
- screenshot tidak digunakan sebagai background pengganti UI;
- data tetap dinamis.

Minor perbedaan yang disebabkan real content diperbolehkan selama tidak mengubah struktur desain.

---

# 19. PERFORMANCE ACCEPTANCE

V1 PASS jika:

- halaman publik tidak membutuhkan Redis;
- tidak membutuhkan long-running queue worker;
- tidak membutuhkan Node.js backend production;
- image loading tidak menyebabkan layout rusak;
- query utama tidak menimbulkan N+1 yang jelas;
- pagination digunakan untuk listing besar;
- cache dapat digunakan pada bagian yang sesuai tanpa membuat data stale permanen.

---

# 20. SECURITY ACCEPTANCE

PASS jika:

- `/admin` membutuhkan authentication;
- public registration tidak tersedia;
- password disimpan dengan Laravel hashing;
- CSRF aktif pada form;
- user input divalidasi;
- contact form rate-limited;
- upload media divalidasi;
- `.env` tidak ikut Git;
- credential sensitif tidak disimpan di tabel settings;
- production `APP_DEBUG=false`;
- rich text/output diperlakukan sebagai input tidak terpercaya.

---

# 21. DATABASE ACCEPTANCE

PASS jika:

- schema mengikuti `docs/database/erd.dbml`;
- migration digunakan untuk perubahan schema;
- foreign key utama tersedia;
- slug unik sesuai specification;
- index penting tersedia;
- article status menggunakan nilai teknis:
  - `draft`
  - `scheduled`
  - `published`
  - `archived`
- tidak ada `is_headline` atau `is_editor_pick` pada articles;
- homepage menggunakan `homepage_slots`.

---

# 22. ROUTE ACCEPTANCE

PASS jika public route mengikuti:

`docs/05-ROUTES.md`

Minimal:

```text
/
/berita/{slug}
/kategori/{slug}
/wilayah/{slug}
/topik/{slug}
/terbaru
/terpopuler
/cari
/kontak
/sitemap.xml
/{page:slug}
```

Admin menggunakan prefix:

```text
/admin
```

Catch-all static page harus berada setelah route public spesifik.

---

# 23. TEST ACCEPTANCE

Untuk feature yang mengubah business behavior:

PASS jika:

- test terkait ditambahkan atau diperbarui;
- test suite relevan lulus;
- tidak ada test existing yang rusak tanpa alasan;
- test mencakup happy path dan business rule kritis.

Prioritas test:

- Draft tidak publik;
- Published publik;
- Scheduled belum publik;
- Scheduled otomatis publish;
- homepage slot hanya menggunakan artikel valid;
- contact validation;
- authentication admin.

---

# 24. DEFINITION OF DONE

Sebuah Feature ID dianggap DONE hanya jika:

1. requirement pada `02-FEATURES.md` dipenuhi;
2. database mengikuti specification;
3. route mengikuti specification;
4. acceptance criteria feature PASS;
5. test relevan PASS;
6. desktop UI diverifikasi jika ada;
7. mobile UI diverifikasi jika ada;
8. tidak ada regression yang diketahui;
9. dokumentasi diperbarui jika keputusan berubah.

Status yang digunakan:

```text
NOT STARTED
IN PROGRESS
BLOCKED
PASS
```

Jangan menandai Feature ID sebagai PASS hanya karena halaman dapat dibuka.

---

# 25. AI AGENT COMPLETION REPORT

Setelah menyelesaikan Feature ID, agent harus memberi laporan ringkas:

```text
Feature:
ARTICLE-001

Status:
PASS / BLOCKED

Changed:
- file...
- file...

Tests:
- test name
- PASS/FAIL

Visual QA:
- Desktop PASS/FAIL
- Mobile PASS/FAIL

Notes:
- ...

Out of scope changes:
None
```

Jika ada acceptance criteria yang belum terpenuhi:

status harus `BLOCKED` atau `IN PROGRESS`, bukan `PASS`.
