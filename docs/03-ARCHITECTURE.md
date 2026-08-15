# TINTAPENA — Technical Architecture V1

## 1. Architecture Goal

Arsitektur TINTAPENA dibuat sederhana, mudah dipahami oleh developer maupun AI agent, dan cocok untuk Hostinger Premium Shared Hosting.

Prinsip utama:

- gunakan fitur native Laravel sebanyak mungkin;
- hindari over-engineering;
- hindari dependency infrastructure yang tidak diperlukan;
- pisahkan public website dan Newsroom secara jelas;
- business rule penting tidak boleh tersebar secara acak di UI.

---

# 2. Technology Stack

## Backend

- Laravel 13
- PHP 8.4

## Public Frontend

- Blade
- Livewire
- Alpine.js
- Tailwind CSS

## Admin / Newsroom

- Filament

## Database

- MySQL

## Development Tooling

- Laravel Boost
- Pest
- Vite
- Git

## Production Target

- Hostinger Premium Shared Hosting

---

# 3. High-Level Architecture

```text
Browser
   |
   v
Laravel
   |
   +----------------------+
   |                      |
   v                      v
Public Website        Newsroom
Blade + Livewire      Filament
   |                      |
   +----------+-----------+
              |
              v
        Application Logic
              |
              v
        Eloquent Models
              |
              v
            MySQL
```

---

# 4. Public Website Architecture

Public website menggunakan Blade sebagai renderer utama.

Livewire hanya digunakan jika interaksi dinamis memang diperlukan.

Contoh:

- pencarian;
- filter wilayah;
- filter berita;
- pagination tertentu;
- load more jika dibutuhkan.

Jangan membuat seluruh public website sebagai Livewire application.

## Public Routes

Contoh:

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
/{static-page-slug}
```

---

# 5. Public Controllers

Controller digunakan untuk request halaman publik.

Contoh:

```text
HomeController
ArticleController
CategoryController
RegionController
LatestNewsController
PopularNewsController
PageController
ContactController
```

Controller harus tetap tipis.

Controller tidak boleh berisi business logic kompleks.

---

# 6. Blade Components

Elemen UI yang digunakan berulang harus dibuat sebagai Blade Component.

Contoh:

```text
resources/views/components/

public/
    header.blade.php
    footer.blade.php

news/
    featured-card.blade.php
    horizontal-row.blade.php
    compact-row.blade.php
    popular-ranking.blade.php
    breaking-ticker.blade.php

ads/
    slot.blade.php
```

Implementasi visual harus mengikuti:

`DESIGN.md`

dan desain Figma TINTAPENA.

---

# 7. Livewire Usage

Livewire digunakan untuk fitur yang membutuhkan state/interaksi tanpa reload penuh.

Target V1:

```text
Search
Region Filter
Topic Filter
Popular Period Filter
```

Livewire tidak digunakan hanya karena tersedia.

Jika Blade biasa sudah cukup, gunakan Blade.

---

# 8. Newsroom Architecture

Newsroom menggunakan Filament.

Base URL:

```text
/admin
```

Tidak ada public registration.

---

# 9. Filament Resources

CRUD standar menggunakan Filament Resource.

Target:

```text
ArticleResource
CategoryResource
RegionResource
TagResource
AdvertisementResource
PageResource
```

Media dapat menggunakan Resource dan/atau custom page sesuai kebutuhan UX final.

---

# 10. Custom Filament Pages

Fitur yang bukan CRUD standar menggunakan Custom Filament Page.

Target:

```text
HomepageManager
BreakingNewsManager
MediaLibrary
WebsiteSettings
```

Alasan:

Homepage Manager dan Breaking News memiliki workflow khusus dan tidak cocok diperlakukan sebagai tabel CRUD biasa.

---

# 11. Dashboard

Filament Dashboard digunakan sebagai halaman utama Newsroom.

Widget dapat menampilkan:

- jumlah berita Published;
- jumlah Draft;
- jumlah Scheduled;
- berita terbaru;
- artikel terpopuler;
- shortcut membuat berita.

---

# 12. Models

Model utama:

```text
User
Article
Category
Region
Tag
Media
HomepageSlot
BreakingNews
ArticleViewStat
Advertisement
Page
Setting
ContactMessage
```

Relationship harus mengikuti:

`docs/database/erd.dbml`

dan:

`docs/database/data-dictionary.md`

---

# 13. Article Query Scopes

Query artikel yang sering dipakai harus menggunakan Eloquent scopes.

Contoh:

```text
published()
draft()
scheduled()
archived()
latestPublished()
byCategory()
byRegion()
```

Contoh penggunaan:

```php
Article::published()
    ->latestPublished()
    ->get();
```

Jangan mengulang kondisi status Published di banyak controller.

---

# 14. Business Actions

Business logic yang penting dan digunakan lebih dari satu tempat dibuat sebagai Action/Class khusus.

Contoh:

```text
PublishArticle
ScheduleArticle
ArchiveArticle
RecordArticleView
ActivateBreakingNews
```

Contoh lokasi:

```text
app/Actions/Articles/
```

Jangan membuat Service class untuk setiap CRUD sederhana.

Gunakan Action hanya ketika ada business rule yang nyata.

---

# 15. Article Publication Rules

Article lifecycle:

```text
Draft
  |
  +--> Published

Draft
  |
  +--> Scheduled
          |
          v
       Published

Published
  |
  v
Archived
```

Public query hanya boleh mengambil artikel:

```text
status = published
AND
published_at <= now()
```

Rule ini harus konsisten di seluruh public website.

---

# 16. Scheduled Publishing

Scheduled article menggunakan Laravel Scheduler.

Target command:

```text
articles:publish-scheduled
```

Scheduler mencari artikel:

```text
status = scheduled
AND
scheduled_at <= now()
```

Kemudian:

```text
status = published
published_at = scheduled_at
```

Production menggunakan cron untuk menjalankan Laravel Scheduler.

Tidak diperlukan queue worker permanen untuk fitur ini.

---

# 17. Homepage Architecture

Homepage memiliki dua tipe konten:

## Automatic Sections

Contoh:

- Berita Terbaru
- kategori tertentu
- wilayah tertentu
- Terpopuler

Data berasal dari query artikel otomatis.

## Curated Sections

Contoh:

- Headline Utama
- Headline Pendukung
- Pilihan Redaksi

Data berasal dari:

```text
homepage_slots
```

Jangan menambahkan `is_headline` ke tabel articles.

---

# 18. Breaking News

Breaking News dikelola terpisah dari Article.

Breaking News dapat mengarah ke:

1. Article;
2. URL manual.

Public Breaking Ticker hanya mengambil Breaking News yang:

```text
is_active = true
```

dan masih berada dalam periode aktif.

---

# 19. Search

Search V1 menggunakan MySQL.

Target pencarian awal:

- title;
- subtitle;
- excerpt;
- content bila diperlukan.

Tidak menggunakan:

- Elasticsearch;
- Algolia;
- Meilisearch;

pada V1.

Search engine eksternal baru dipertimbangkan jika kebutuhan traffic dan pencarian meningkat.

---

# 20. Popular News

Total view disimpan pada:

```text
articles.views_count
```

Statistik periode disimpan pada:

```text
article_view_stats
```

Digunakan untuk:

```text
24 jam
7 hari
```

Jangan menyimpan satu database row untuk setiap page view pada V1.

---

# 21. Media Storage

Media menggunakan Laravel Filesystem.

Development:

```text
public disk
```

File database hanya menyimpan metadata dan path.

File binary tidak disimpan di MySQL.

Target direktori dapat menggunakan pola seperti:

```text
news/2026/08/
ads/2026/08/
```

Upload harus memvalidasi:

- MIME type;
- ukuran;
- extension;
- dimensi jika diperlukan.

---

# 22. Advertisement

Advertisement menggunakan:

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

Blade menggunakan component seperti:

```text
<x-ads.slot position="article_inline" />
```

Component menentukan iklan aktif berdasarkan:

- placement;
- status;
- waktu mulai;
- waktu selesai.

---

# 23. Settings

Website settings menggunakan tabel:

```text
settings
```

Untuk konfigurasi non-sensitive.

Contoh:

```text
site name
tagline
contact information
social media
SEO defaults
analytics ID
```

Sensitive configuration tetap menggunakan `.env`.

---

# 24. Cache Strategy

V1 menggunakan Laravel cache standar.

Tidak ada Redis dependency wajib.

Cache dapat digunakan untuk:

- homepage;
- berita terpopuler;
- settings;
- Breaking News aktif.

Cache harus di-invalidate ketika data terkait berubah.

---

# 25. Queue Strategy

Queue bukan dependency utama V1.

Jika nantinya diperlukan:

```text
database queue
```

lebih diprioritaskan daripada Redis agar tetap kompatibel dengan shared hosting.

Jangan membuat fitur V1 bergantung pada long-running queue worker.

---

# 26. Security Architecture

Newsroom:

```text
/admin/*
```

harus membutuhkan authentication.

Tidak ada public registration.

Laravel protections tetap digunakan:

- CSRF;
- password hashing;
- validation;
- authorization;
- rate limiting;
- escaped output.

Rich text dan upload harus diperlakukan sebagai input tidak terpercaya.

---

# 27. Testing Architecture

Testing menggunakan Pest.

Prioritas test:

```text
Feature Tests
```

untuk business flow penting.

Contoh:

```text
Admin dapat membuat Draft
Draft tidak dapat dibuka publik
Published dapat dibuka publik
Scheduled belum dapat dibuka publik
Scheduled terbit setelah waktunya
Headline hanya menggunakan Published Article
```

---

# 28. Folder Convention

Struktur aplikasi target:

```text
app/

Actions/
    Articles/
    BreakingNews/

Filament/
    Resources/
    Pages/
    Widgets/

Http/
    Controllers/

Livewire/

Models/

Console/
    Commands/
```

Views:

```text
resources/views/

components/
livewire/
pages/
articles/
categories/
regions/
```

---

# 29. Architecture Rules for AI Agent

AI agent harus mengikuti aturan berikut:

1. Baca PRD sebelum implementasi fitur.
2. Baca Feature Specification terkait.
3. Baca ERD dan Data Dictionary sebelum mengubah database.
4. Jangan membuat framework atau architecture baru tanpa persetujuan.
5. Jangan menambahkan package jika Laravel native sudah mencukupi.
6. Jangan membuat repository/service abstraction tanpa kebutuhan nyata.
7. Controller harus tipis.
8. Business rules penting harus reusable.
9. Jangan mengubah database langsung tanpa migration.
10. Jangan mengubah Figma/design behavior tanpa alasan yang terdokumentasi.
11. Implementasikan satu Feature ID dalam satu scope pekerjaan jika memungkinkan.
12. Tambahkan atau update test untuk behavior yang diubah.

---

# 30. Forbidden Architecture V1

Jangan memperkenalkan:

```text
React SPA
Vue SPA
Next.js
Nuxt
Node.js backend
Microservices
Elasticsearch
Mandatory Redis
Docker requirement for production
Separate API backend tanpa kebutuhan
Repository pattern untuk semua model
Service layer untuk semua CRUD
```

---

# 31. Source of Truth Priority

Jika terjadi konflik, gunakan urutan:

```text
1. PRD
2. Feature Specification
3. Database ERD + Data Dictionary
4. Architecture
5. Acceptance Criteria
6. DESIGN.md / Figma
7. Existing implementation
```

Jika dua specification bertentangan:

AI agent tidak boleh menebak.

Laporkan konflik sebelum melakukan perubahan besar.
