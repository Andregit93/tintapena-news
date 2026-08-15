# TINTAPENA — Database Specification V1

## 1. Tujuan

Dokumen ini menjadi ringkasan aturan database TINTAPENA V1.

Detail struktur tabel terdapat pada:

- `docs/database/erd.dbml`
- `docs/database/data-dictionary.md`

Jika ada perbedaan antara dokumen ini dan struktur detail, gunakan:

1. `erd.dbml`
2. `data-dictionary.md`
3. dokumen ini

---

## 2. Database Engine

Database:

- MySQL

Character set:

```text
utf8mb4
```

Collation:

```text
utf8mb4_unicode_ci
```

Target environment:

- Local development: MySQL lokal
- Production: MySQL Hostinger

---

## 3. Core Tables

Tabel utama V1:

```text
users
categories
regions
tags
article_tag
media
articles
homepage_slots
breaking_news
article_view_stats
advertisements
pages
settings
contact_messages
```

Laravel juga dapat memiliki tabel framework seperti:

```text
sessions
cache
jobs
failed_jobs
password_reset_tokens
```

sesuai konfigurasi framework.

---

## 4. Core Relationships

```text
User
 ├── hasMany Articles
 ├── hasMany Media
 └── hasMany Pages

Article
 ├── belongsTo User
 ├── belongsTo Category
 ├── belongsTo Region
 ├── belongsTo Media (featured image)
 └── belongsToMany Tags

Category
 └── hasMany Articles

Region
 └── hasMany Articles

Tag
 └── belongsToMany Articles
```

---

## 5. Article Classification

Setiap artikel:

- wajib memiliki satu kategori;
- boleh memiliki satu wilayah;
- boleh memiliki banyak tag;
- boleh belum memiliki featured image selama masih Draft.

Contoh:

```text
Judul:
Harga Timah Menguat di Bangka Belitung

Category:
Ekonomi

Region:
Bangka Tengah

Tags:
PT Timah
Pertambangan
Ekonomi Babel
```

Kategori dan wilayah tidak boleh digabung menjadi satu taxonomy.

---

## 6. Article Status

Status resmi artikel:

```text
draft
scheduled
published
archived
```

### Draft

Belum diterbitkan.

Tidak dapat diakses publik.

### Scheduled

Dijadwalkan untuk waktu tertentu.

Tidak dapat diakses sebelum waktu publikasi.

### Published

Dapat diakses publik.

Harus memiliki:

```text
published_at
```

### Archived

Tidak muncul pada feed publik normal.

---

## 7. Public Article Rule

Artikel hanya boleh tampil pada public website jika:

```text
status = published
AND
published_at <= current_time
```

Rule ini harus digunakan secara konsisten pada seluruh public query.

Jangan hanya memeriksa:

```text
status = published
```

tanpa memperhatikan `published_at`.

---

## 8. Slug

Tabel yang memiliki halaman publik harus menggunakan slug unik.

Contoh:

```text
articles.slug
categories.slug
regions.slug
tags.slug
pages.slug
```

Slug digunakan untuk URL yang SEO-friendly.

Contoh:

```text
/berita/pemprov-babel-dorong-ekonomi-daerah
/kategori/ekonomi
/wilayah/bangka-tengah
/topik/pt-timah
```

Slug tidak boleh berubah otomatis setelah artikel Published tanpa pertimbangan redirect.

---

## 9. Homepage Placement

Headline homepage tidak disimpan sebagai boolean pada artikel.

Jangan menambahkan:

```text
articles.is_headline
articles.is_editor_pick
```

Gunakan:

```text
homepage_slots
```

Contoh slot:

```text
headline_main
headline_2
headline_3

editor_pick_1
editor_pick_2
editor_pick_3
editor_pick_4
```

Dengan demikian perubahan posisi homepage tidak mengubah properti artikel.

---

## 10. Breaking News

Breaking News disimpan pada:

```text
breaking_news
```

Breaking News dapat:

1. terhubung dengan artikel;
2. menggunakan headline dan URL manual.

Breaking News memiliki periode aktif menggunakan:

```text
starts_at
ends_at
```

serta:

```text
is_active
```

---

## 11. Article Views

TINTAPENA menggunakan dua tingkat data view.

### Lifetime Views

```text
articles.views_count
```

Menyimpan total view sepanjang waktu.

### Period Statistics

```text
article_view_stats
```

Menyimpan agregasi view berdasarkan periode waktu.

Digunakan untuk:

- Terpopuler 24 jam
- Terpopuler 7 hari

V1 tidak menyimpan satu row untuk setiap page view.

Tujuannya agar database tetap ringan.

---

## 12. Media

File media tidak disimpan sebagai binary di MySQL.

Database hanya menyimpan:

- path;
- filename;
- MIME type;
- ukuran;
- dimensi;
- alt text;
- caption;
- photo credit.

File disimpan menggunakan Laravel Filesystem.

Artikel menghubungkan gambar utama menggunakan:

```text
featured_media_id
```

---

## 13. Advertisement

Iklan menggunakan tabel:

```text
advertisements
```

Posisi iklan ditentukan oleh:

```text
placement_key
```

Contoh:

```text
homepage_top
homepage_middle
article_inline
article_sidebar
category_sidebar
```

Iklan dapat memiliki:

- waktu mulai;
- waktu selesai;
- status aktif.

---

## 14. Static Pages

Halaman statis menggunakan tabel:

```text
pages
```

Contoh:

- Tentang Kami
- Redaksi
- Pedoman Media Siber
- Privacy Policy
- Disclaimer

Status:

```text
draft
published
```

Hanya halaman Published yang dapat diakses publik.

---

## 15. Settings

Konfigurasi non-sensitive website menggunakan tabel:

```text
settings
```

Contoh:

```text
general.site_name
general.tagline

contact.email
contact.whatsapp

social.instagram

seo.default_title

analytics.google_measurement_id
```

Jangan simpan credential sensitif di tabel settings.

Credential sensitif harus berada di `.env`.

Contoh:

```text
APP_KEY
DB_PASSWORD
SMTP_PASSWORD
API_SECRET
```

---

## 16. Contact Messages

Pesan dari halaman kontak disimpan pada:

```text
contact_messages
```

Status:

```text
unread
read
archived
```

Input wajib melalui validation dan rate limiting.

---

## 17. Foreign Keys

Gunakan foreign key pada relationship utama.

Contoh:

```text
articles.author_id
→ users.id

articles.category_id
→ categories.id

articles.region_id
→ regions.id

articles.featured_media_id
→ media.id
```

Foreign key behavior seperti cascade atau null-on-delete harus ditentukan secara hati-hati saat migration dibuat.

Jangan gunakan cascade delete secara sembarangan pada konten editorial.

---

## 18. Index Strategy

Kolom yang sering digunakan untuk query harus memiliki index.

Prioritas:

```text
articles.status
articles.published_at
articles.category_id
articles.region_id

categories.slug
regions.slug
tags.slug

breaking_news.is_active

advertisements.placement_key
```

Composite index digunakan untuk query publik utama, misalnya:

```text
(status, published_at)
(category_id, status, published_at)
(region_id, status, published_at)
```

---

## 19. Soft Delete

Soft delete tidak wajib digunakan untuk semua tabel.

Untuk V1:

- Artikel menggunakan status `archived`, bukan otomatis soft delete.
- Category/Region lebih baik dinonaktifkan daripada dihapus jika sudah digunakan.
- Hard delete hanya digunakan jika aman dan tidak merusak relationship.

Keputusan soft delete tambahan harus memiliki alasan yang jelas.

---

## 20. Migration Rules

Semua perubahan database wajib melalui Laravel migration.

Tidak boleh:

- mengubah schema production secara manual;
- menghapus column langsung dari phpMyAdmin;
- membuat tabel manual tanpa migration.

Migration harus:

- memiliki nama yang jelas;
- memiliki rollback yang aman bila memungkinkan;
- menggunakan foreign key;
- menggunakan index sesuai ERD.

---

## 21. Seeder Rules

Seeder digunakan untuk data awal sistem.

Minimal V1:

```text
Admin User
Categories
Regions
Homepage Slots
```

Region awal:

```text
Pangkalpinang
Bangka
Bangka Barat
Bangka Tengah
Bangka Selatan
Belitung
Belitung Timur
```

Kategori awal mengikuti PRD.

---

## 22. Database Naming Convention

Table:

```text
snake_case
plural
```

Contoh:

```text
articles
homepage_slots
contact_messages
```

Column:

```text
snake_case
```

Contoh:

```text
published_at
featured_media_id
photo_credit
```

Foreign key:

```text
{model}_id
```

Contoh:

```text
category_id
region_id
article_id
```

---

## 23. Database Change Rule for AI Agent

Sebelum AI agent mengubah database:

1. baca `01-PRD.md`;
2. baca `02-FEATURES.md`;
3. baca `03-ARCHITECTURE.md`;
4. baca `database/erd.dbml`;
5. baca `database/data-dictionary.md`;
6. periksa migration yang sudah ada.

Jika perubahan membutuhkan schema baru:

- update ERD;
- update Data Dictionary;
- update Database Specification bila diperlukan;
- baru buat migration.

AI agent tidak boleh membuat field baru hanya karena terasa lebih mudah untuk implementasi UI.

---

## 24. Source of Truth

Database source of truth:

```text
docs/database/erd.dbml
```

Penjelasan field:

```text
docs/database/data-dictionary.md
```

Ringkasan aturan:

```text
docs/04-DATABASE.md
```

Migration Laravel harus merepresentasikan struktur yang telah disetujui pada dokumentasi tersebut.
