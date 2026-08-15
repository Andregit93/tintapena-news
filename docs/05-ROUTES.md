# TINTAPENA — Route Specification V1

## 1. Tujuan

Dokumen ini menetapkan struktur URL resmi TINTAPENA V1.

Tujuan:

- URL konsisten;
- SEO-friendly;
- mudah dipahami;
- tidak dibuat berbeda-beda oleh developer atau AI agent;
- memisahkan Public Website dan Newsroom dengan jelas.

---

# 2. Route Principles

Gunakan prinsip berikut:

- URL publik menggunakan bahasa Indonesia.
- URL menggunakan lowercase.
- Gunakan slug, bukan ID database, untuk halaman publik.
- Route admin berada di bawah `/admin`.
- Route publik tidak boleh mengekspos Draft atau Scheduled Article.
- Route name Laravel harus digunakan jika tersedia.
- Jangan hardcode URL di Blade jika dapat menggunakan `route()`.

Contoh:

```php
route('articles.show', $article)
```

bukan:

```php
'/berita/' . $article->slug
```

---

# 3. Public Route Map

## Homepage

```text
GET /
```

Route name:

```text
home
```

Controller:

```text
HomeController
```

Fungsi:

Menampilkan homepage TINTAPENA.

Feature:

```text
HOME-001
```

---

# 4. Article Detail

```text
GET /berita/{article:slug}
```

Route name:

```text
articles.show
```

Controller:

```text
ArticleController@show
```

Feature:

```text
PUBLIC-001
```

Hanya Article Published yang boleh ditampilkan.

Jika artikel:

- Draft;
- Scheduled yang belum waktunya;
- Archived;

maka public request tidak boleh menampilkan isi artikel.

Gunakan response yang sesuai, umumnya:

```text
404
```

---

# 5. Category

```text
GET /kategori/{category:slug}
```

Route name:

```text
categories.show
```

Controller:

```text
CategoryController@show
```

Feature:

```text
LIST-003
```

Contoh:

```text
/kategori/ekonomi
/kategori/pariwisata
```

---

# 6. Region

```text
GET /wilayah/{region:slug}
```

Route name:

```text
regions.show
```

Controller:

```text
RegionController@show
```

Feature:

```text
LIST-004
```

Contoh:

```text
/wilayah/bangka-tengah
/wilayah/pangkalpinang
```

---

# 7. Tag / Topic

```text
GET /topik/{tag:slug}
```

Route name:

```text
tags.show
```

Controller:

```text
TagController@show
```

Feature:

```text
LIST-005
```

Contoh:

```text
/topik/pt-timah
```

---

# 8. Latest News

```text
GET /terbaru
```

Route name:

```text
articles.latest
```

Controller:

```text
LatestNewsController@index
```

Feature:

```text
LIST-001
```

Urutan:

```text
published_at DESC
```

Hanya Published Article.

---

# 9. Popular News

```text
GET /terpopuler
```

Route name:

```text
articles.popular
```

Controller:

```text
PopularNewsController@index
```

Feature:

```text
LIST-002
```

Query parameter periode:

```text
/terpopuler?periode=24jam
/terpopuler?periode=7hari
```

Nilai yang didukung V1:

```text
24jam
7hari
```

Default:

```text
24jam
```

---

# 10. Search

```text
GET /cari
```

Route name:

```text
search
```

Contoh:

```text
/cari?q=timah
```

Feature:

```text
SEARCH-001
SEARCH-002
SEARCH-003
```

Search dapat menggunakan Livewire.

Parameter utama:

```text
q
```

Search hanya mengembalikan konten publik yang Published.

---

# 11. Contact

## Contact Page

```text
GET /kontak
```

Route name:

```text
contact.show
```

Controller:

```text
ContactController@show
```

Feature:

```text
CONTACT-001
```

## Submit Contact Form

```text
POST /kontak
```

Route name:

```text
contact.store
```

Controller:

```text
ContactController@store
```

Feature:

```text
CONTACT-002
```

Request wajib memiliki:

- validation;
- CSRF protection;
- rate limiting.

---

# 12. Static Pages

Static page menggunakan:

```text
GET /{page:slug}
```

Route name:

```text
pages.show
```

Controller:

```text
PageController@show
```

Contoh:

```text
/tentang-kami
/redaksi
/pedoman-media-siber
/privacy-policy
/disclaimer
```

Hanya Page dengan status:

```text
published
```

yang boleh ditampilkan.

---

# 13. IMPORTANT — Static Page Route Order

Route:

```text
/{page:slug}
```

adalah catch-all route.

Karena itu route ini **WAJIB berada setelah seluruh route publik lainnya**.

Benar:

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

/{page:slug}
```

Jangan meletakkan catch-all static page sebelum route lain.

Jika tidak, URL seperti:

```text
/terbaru
```

dapat dianggap sebagai slug halaman statis.

---

# 14. Sitemap

```text
GET /sitemap.xml
```

Route name:

```text
sitemap
```

Feature:

```text
SYSTEM-004
```

Sitemap minimal mencakup:

- homepage;
- Published Articles;
- kategori aktif;
- wilayah aktif;
- Published Static Pages.

Draft dan Scheduled Article tidak boleh masuk sitemap.

---

# 15. Newsroom Base URL

Seluruh Newsroom menggunakan prefix:

```text
/admin
```

Newsroom menggunakan Filament.

Public user tidak boleh mengakses area ini tanpa authentication.

---

# 16. Admin Authentication

Login:

```text
GET|POST /admin/login
```

Logout dikelola oleh authentication Filament.

Tidak tersedia:

```text
/admin/register
```

Public registration dilarang pada V1.

Feature:

```text
AUTH-001
AUTH-002
```

---

# 17. Admin Dashboard

```text
/admin
```

Target page:

```text
Filament Dashboard
```

Feature:

```text
DASH-001
```

---

# 18. Article Management

Base:

```text
/admin/berita
```

Target:

```text
ArticleResource
```

Expected routes secara konsep:

```text
GET    /admin/berita
GET    /admin/berita/create
GET    /admin/berita/{record}/edit
```

Create/edit/save actions dikelola oleh Filament Resource.

Feature:

```text
ARTICLE-001
ARTICLE-002
ARTICLE-003
ARTICLE-004
ARTICLE-006
ARTICLE-007
ARTICLE-008
ARTICLE-009
ARTICLE-010
```

---

# 19. Article Preview

Preview artikel memiliki route khusus.

```text
GET /admin/berita/{article}/preview
```

Route name:

```text
admin.articles.preview
```

Feature:

```text
ARTICLE-005
```

Rules:

- wajib authentication;
- dapat menampilkan Draft;
- tidak boleh dianggap sebagai public article;
- tidak boleh diindex search engine.

Response harus menggunakan:

```text
X-Robots-Tag: noindex, nofollow
```

atau mekanisme setara.

Preview tidak menggunakan public URL `/berita/{slug}` untuk Draft.

---

# 20. Media Library

```text
/admin/media
```

Target:

```text
MediaLibrary
```

atau Filament Resource/custom page sesuai implementasi.

Feature:

```text
MEDIA-001
MEDIA-002
MEDIA-003
MEDIA-004
```

---

# 21. Category Management

```text
/admin/kategori
```

Target:

```text
CategoryResource
```

Feature:

```text
CATEGORY-001
CATEGORY-002
CATEGORY-003
CATEGORY-004
```

---

# 22. Region Management

```text
/admin/wilayah
```

Target:

```text
RegionResource
```

Feature:

```text
REGION-001
REGION-002
REGION-003
```

---

# 23. Tag Management

```text
/admin/tag
```

Target:

```text
TagResource
```

Feature:

```text
TAG-001
TAG-002
TAG-003
TAG-004
```

---

# 24. Homepage Manager

```text
/admin/homepage
```

Target:

```text
HomepageManager
```

Custom Filament Page.

Feature:

```text
HOME-002
HOME-003
HOME-004
HOME-005
```

---

# 25. Breaking News Manager

```text
/admin/breaking-news
```

Target:

```text
BreakingNewsManager
```

Feature:

```text
BREAKING-001
BREAKING-002
BREAKING-003
BREAKING-004
```

---

# 26. Advertisement Management

```text
/admin/iklan
```

Target:

```text
AdvertisementResource
```

Feature:

```text
ADS-001
ADS-002
ADS-003
ADS-004
```

---

# 27. Static Page Management

```text
/admin/halaman
```

Target:

```text
PageResource
```

Feature:

```text
PAGE-001
PAGE-002
```

---

# 28. Website Settings

```text
/admin/pengaturan
```

Target:

```text
WebsiteSettings
```

Custom Filament Page.

Feature:

```text
SETTINGS-001
SETTINGS-002
SETTINGS-003
SETTINGS-004
SETTINGS-005
```

---

# 29. Contact Messages in Admin

Contact messages dapat ditampilkan pada:

```text
/admin/pesan
```

Target:

```text
ContactMessageResource
```

Fungsi:

- melihat pesan masuk;
- menandai Read;
- mengarsipkan pesan.

Tidak menyediakan fitur email client pada V1.

---

# 30. Laravel Route Files

Public routes:

```text
routes/web.php
```

Console/scheduler:

```text
routes/console.php
```

atau struktur Laravel yang sesuai versi project.

Filament routes sebisa mungkin dikelola melalui Filament, bukan dibuat ulang secara manual.

---

# 31. Middleware

Public route umumnya menggunakan:

```text
web
```

Admin menggunakan:

```text
web
authentication Filament
```

Contact submit menggunakan tambahan:

```text
throttle
```

Contoh konsep:

```php
Route::post('/kontak', ...)
    ->middleware('throttle:contact');
```

---

# 32. Route Model Binding

Gunakan slug untuk model publik.

Contoh:

```php
Route::get(
    '/berita/{article:slug}',
    [ArticleController::class, 'show']
);
```

Gunakan:

```text
{article:slug}
{category:slug}
{region:slug}
{tag:slug}
{page:slug}
```

Admin dapat menggunakan internal record ID sesuai mekanisme Filament.

---

# 33. Canonical URL

Satu konten hanya boleh memiliki satu canonical public URL.

Article:

```text
/berita/{slug}
```

Category:

```text
/kategori/{slug}
```

Region:

```text
/wilayah/{slug}
```

Tag:

```text
/topik/{slug}
```

Jangan membuat beberapa public URL berbeda untuk artikel yang sama tanpa canonical/redirect yang jelas.

---

# 34. Slug Changes

Jika slug Published Article diubah setelah website production:

old URL sebaiknya diarahkan ke URL baru menggunakan redirect permanen jika sistem redirect sudah tersedia.

Untuk V1, hindari perubahan slug Published Article yang tidak diperlukan.

---

# 35. Example routes/web.php

Struktur konseptual:

```php
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LatestNewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PopularNewsController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\TagController;

Route::get('/', HomeController::class)
    ->name('home');

Route::get('/berita/{article:slug}', [ArticleController::class, 'show'])
    ->name('articles.show');

Route::get('/kategori/{category:slug}', [CategoryController::class, 'show'])
    ->name('categories.show');

Route::get('/wilayah/{region:slug}', [RegionController::class, 'show'])
    ->name('regions.show');

Route::get('/topik/{tag:slug}', [TagController::class, 'show'])
    ->name('tags.show');

Route::get('/terbaru', [LatestNewsController::class, 'index'])
    ->name('articles.latest');

Route::get('/terpopuler', [PopularNewsController::class, 'index'])
    ->name('articles.popular');

Route::get('/cari', ...)
    ->name('search');

Route::get('/kontak', [ContactController::class, 'show'])
    ->name('contact.show');

Route::post('/kontak', [ContactController::class, 'store'])
    ->name('contact.store');

Route::get('/sitemap.xml', ...)
    ->name('sitemap');

// MUST BE LAST
Route::get('/{page:slug}', [PageController::class, 'show'])
    ->name('pages.show');
```

Kode di atas adalah contoh struktur, bukan kewajiban untuk menyalin persis jika implementasi final menggunakan Livewire pada route tertentu.

---

# 36. Route Rules for AI Agent

AI agent:

1. tidak boleh membuat URL baru tanpa mengecek dokumen ini;
2. tidak boleh mengganti URL Indonesia menjadi URL Inggris;
3. tidak boleh mengekspos Draft melalui public route;
4. tidak boleh membuat public registration;
5. tidak boleh menduplikasi Filament routes secara manual;
6. harus menggunakan named route;
7. harus menggunakan route model binding jika sesuai;
8. harus memastikan static-page catch-all berada paling akhir;
9. harus menjaga backward compatibility URL setelah production;
10. harus memperbarui dokumen ini jika route resmi berubah.

---

# 37. Public Route Summary

```text
/                         Homepage
/berita/{slug}            Detail Berita
/kategori/{slug}          Kategori
/wilayah/{slug}           Wilayah
/topik/{slug}             Topik
/terbaru                   Berita Terbaru
/terpopuler                Berita Terpopuler
/cari                      Pencarian
/kontak                    Kontak
/sitemap.xml               Sitemap
/{page-slug}               Halaman Statis
```

Admin:

```text
/admin                     Dashboard
/admin/berita              Berita
/admin/media               Media
/admin/kategori            Kategori
/admin/wilayah             Wilayah
/admin/tag                 Tag
/admin/homepage            Homepage Manager
/admin/breaking-news       Breaking News
/admin/iklan               Iklan
/admin/halaman             Halaman
/admin/pesan               Pesan Masuk
/admin/pengaturan          Pengaturan
```
