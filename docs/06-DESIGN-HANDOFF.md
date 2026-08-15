# TINTAPENA — Design Handoff V1

## 1. Tujuan

Dokumen ini menghubungkan desain Figma TINTAPENA dengan implementasi Laravel.

Visual source of truth:

- Figma TINTAPENA
- `DESIGN.md`

AI agent tidak boleh mendesain ulang halaman tanpa alasan yang disetujui.

---

## 2. Design Principles

TINTAPENA menggunakan gaya:

- modern editorial;
- profesional;
- content-first;
- fokus pada keterbacaan berita;
- responsive desktop dan mobile;
- minim dekorasi yang tidak diperlukan.

Gunakan border, spacing, dan hierarchy typography sebagai struktur utama.

Hindari:

- tampilan seperti SaaS dashboard pada website publik;
- rounded card berlebihan;
- shadow berlebihan;
- efek visual yang mengganggu pembacaan berita.

---

## 3. Brand

Nama:

TINTAPENA

Tagline:

Menulis Berdasarkan Fakta

Warna utama:

```text
Primary Blue   #1A2BC4
Primary Red    #E53935

Ink            #17191D
Text Secondary #5D6470
Text Muted     #8A9099
Border         #E1E4E8
Surface        #F6F7F9
White          #FFFFFF
Dark Navy      #111833
```

Typography utama:

```text
Plus Jakarta Sans
```

Fallback:

```text
Inter
sans-serif
```

---

# 4. Responsive Reference

Desktop reference:

```text
1440px
```

Public content maximum:

```text
1240px
```

Desktop outer gutter:

```text
32px
```

Mobile reference:

```text
390px
```

Mobile horizontal padding:

```text
16px
```

Minimum touch target:

```text
44 × 44 px
```

Mobile bukan sekadar versi desktop yang diperkecil.

Layout harus berubah sesuai kebutuhan layar.

---

# 5. Public Website Mapping

## Homepage

Figma:

```text
Homepage / Desktop
Homepage / Mobile
```

Laravel:

```text
Route:
/

Controller:
HomeController

View:
resources/views/pages/home.blade.php
```

Feature:

```text
HOME-001
```

Isi utama:

```text
Header
Breaking News
Headline Utama
Supporting Headlines
Berita Terbaru
Terpopuler
Bangka Belitung
Politik & Pemerintahan
Ekonomi
Hukum & Kriminal
Pariwisata
Pilihan Redaksi
Advertisement
Footer
```

---

## Article Detail

Figma:

```text
Article Detail / Desktop
Article Detail / Mobile
```

Laravel:

```text
Route:
/berita/{article:slug}

Controller:
ArticleController@show

View:
resources/views/articles/show.blade.php
```

Feature:

```text
PUBLIC-001
PUBLIC-002
PUBLIC-003
```

Isi:

```text
Breadcrumb
Category
Region
Headline
Subtitle
Author
Published Time
Updated Time
Share Actions
Featured Image
Caption
Photo Credit
Article Body
Baca Juga
Editorial Quote
Inline Advertisement
Tags
Related News
Popular News
Footer
```

---

## Category Page

Figma:

```text
Category / Ekonomi / Desktop
Category / Ekonomi / Mobile
```

Laravel:

```text
/kategori/{category:slug}
```

Feature:

```text
LIST-003
```

Template harus reusable untuk semua kategori.

Jangan membuat Blade terpisah untuk setiap kategori.

---

## Region Page

Figma:

```text
Region / Bangka Tengah / Desktop
Region / Bangka Tengah / Mobile
```

Laravel:

```text
/wilayah/{region:slug}
```

Feature:

```text
LIST-004
```

Region navigation pada mobile menggunakan horizontal scrolling.

---

## Latest News

Figma:

```text
Latest News / Desktop
Latest News / Mobile
```

Laravel:

```text
/terbaru
```

Feature:

```text
LIST-001
```

Artikel disusun berdasarkan `published_at`.

---

## Popular News

Figma:

```text
Popular News / Desktop
Popular News / Mobile
```

Laravel:

```text
/terpopuler
```

Feature:

```text
LIST-002
```

Filter:

```text
24 Jam
7 Hari
```

---

## Search

Figma:

```text
Search Results / Desktop
Search Results / Mobile
```

Laravel:

```text
/cari?q={keyword}
```

Feature:

```text
SEARCH-001
SEARCH-002
SEARCH-003
```

Harus memiliki empty state jika hasil tidak ditemukan.

---

## Topic / Tag

Figma:

```text
Tag / PTTimah / Desktop
Tag / PTTimah / Mobile
```

Laravel:

```text
/topik/{tag:slug}
```

Feature:

```text
LIST-005
```

---

## Static Information

Figma reference:

```text
Static Info / Tentang Kami / Desktop
Static Info / Tentang Kami / Mobile
```

Laravel:

```text
/{page:slug}
```

Feature:

```text
PAGE-002
```

Template reusable untuk:

```text
Tentang Kami
Redaksi
Pedoman Media Siber
Privacy Policy
Disclaimer
```

---

## Contact

Figma:

```text
Contact / Desktop
Contact / Mobile
```

Laravel:

```text
/kontak
```

Feature:

```text
CONTACT-001
CONTACT-002
```

---

# 6. Reusable Public Components

Figma component:

```text
Header
```

Laravel:

```text
<x-public.header />
```

Figma:

```text
Footer
```

Laravel:

```text
<x-public.footer />
```

Figma:

```text
Breaking News
```

Laravel:

```text
<x-news.breaking-ticker />
```

Figma:

```text
News Card / Featured
```

Laravel:

```text
<x-news.featured-card />
```

Figma:

```text
News Row / Horizontal
```

Laravel:

```text
<x-news.horizontal-row />
```

Figma:

```text
News Row / Compact
```

Laravel:

```text
<x-news.compact-row />
```

Figma:

```text
Popular Ranking
```

Laravel:

```text
<x-news.popular-ranking />
```

Figma:

```text
Ad Placeholder
```

Laravel:

```text
<x-ads.slot position="..." />
```

---

# 7. Newsroom Mapping

Base:

```text
/admin
```

Framework:

```text
Filament
```

---

## Login

Figma:

```text
Admin Login / Desktop
Admin Login / Mobile
```

Implementation:

```text
Filament Authentication
```

Feature:

```text
AUTH-001
```

---

## Dashboard

Figma:

```text
Admin Dashboard / Desktop
Admin Dashboard / Mobile
```

Implementation:

```text
Filament Dashboard
+
Filament Widgets
```

Feature:

```text
DASH-001
```

---

## Article List

Figma:

```text
Admin Article List / Desktop
Admin Article List / Mobile
```

Implementation:

```text
ArticleResource
```

Feature:

```text
ARTICLE-010
```

Desktop:

table.

Mobile:

article cards/list.

---

## Article Editor

Figma:

```text
Admin Article Editor / Desktop
Admin Article Editor / Mobile
```

Implementation:

```text
ArticleResource
CreateArticle
EditArticle
```

Feature:

```text
ARTICLE-001
ARTICLE-002
ARTICLE-003
ARTICLE-004
ARTICLE-005
ARTICLE-006
ARTICLE-007
ARTICLE-008
ARTICLE-009
```

Desktop layout:

```text
Main editor
+
Publishing/settings sidebar
```

Mobile:

```text
Single column
Accordion-style sections
Sticky bottom action
```

Mobile sticky actions:

```text
Draft
Preview
Terbitkan
```

---

## Media Library

Figma:

```text
Admin Media Library / Desktop
Admin Media Library / Mobile
```

Implementation:

```text
MediaResource
and/or
MediaLibrary custom Filament Page
```

Feature:

```text
MEDIA-001
MEDIA-002
MEDIA-003
MEDIA-004
```

---

## Category

Figma:

```text
Kategori Management
```

Implementation:

```text
CategoryResource
```

---

## Region

Figma:

```text
Wilayah Management
```

Implementation:

```text
RegionResource
```

---

## Tag

Figma:

```text
Tag Management
```

Implementation:

```text
TagResource
```

---

## Homepage Manager

Figma:

```text
Homepage Manager
```

Implementation:

```text
Custom Filament Page:
HomepageManager
```

Feature:

```text
HOME-002
HOME-003
HOME-004
HOME-005
```

Manages:

```text
Headline Utama
Headline #2
Headline #3
Pilihan Redaksi
```

---

## Breaking News

Figma:

```text
Breaking News Manager
```

Implementation:

```text
Custom Filament Page:
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

## Advertisement

Figma:

```text
Advertisement Management
```

Implementation:

```text
AdvertisementResource
```

---

## Static Pages

Figma:

```text
Static Pages
```

Implementation:

```text
PageResource
```

---

## Website Settings

Figma:

```text
Website Settings
```

Implementation:

```text
Custom Filament Page:
WebsiteSettings
```

---

# 8. Article Status UI

Technical values:

```text
draft
scheduled
published
archived
```

Visible Indonesian labels:

```text
draft       → Draft
scheduled   → Terjadwal
published   → Diterbitkan
archived    → Arsip
```

Jangan menggunakan nilai bahasa Indonesia sebagai nilai database.

---

# 9. Public Component Rules

News cards tidak boleh mempunyai data logic sendiri.

Component menerima data dari controller/query layer.

Contoh:

```php
<x-news.featured-card :article="$article" />
```

Component tidak boleh melakukan query database sendiri jika dapat dihindari.

---

# 10. Image Rules

Saat ini desain Figma menggunakan placeholder gambar untuk beberapa area.

Pada implementasi production:

- gunakan gambar artikel dari Media Library;
- gunakan aspect ratio sesuai desain;
- gunakan `object-fit: cover`;
- gunakan alt text;
- gunakan lazy loading jika sesuai;
- hindari layout shift.

Final logo TINTAPENA dapat menggantikan text-based placeholder tanpa mengubah struktur layout.

---

# 11. Mobile Transformation Rules

Desktop sidebar:

```text
→ mobile in-flow section
```

Desktop table:

```text
→ mobile card/list
```

Desktop multi-column:

```text
→ mobile single column
```

Region/topic chips:

```text
→ horizontal scroll
```

Article editor desktop:

```text
main editor + sidebar
```

Mobile:

```text
single column + sticky actions
```

---

# 12. Figma vs Implementation

Figma menentukan:

```text
layout
hierarchy
spacing
typography
visual appearance
responsive behavior
```

Laravel implementation menentukan:

```text
data
routing
validation
authorization
business logic
database interaction
```

Jangan mengorbankan business rules hanya untuk meniru mock data pada Figma.

---

# 13. Mock Content

Semua nama berita, jumlah artikel, view count, tanggal, email, foto, dan data contoh pada Figma dianggap:

```text
MOCK DATA
```

kecuali secara eksplisit ditetapkan sebagai data produksi.

Agent tidak boleh memasukkan mock content Figma sebagai production data tanpa instruksi.

---

# 14. Design Verification

Setelah sebuah public screen selesai:

1. jalankan aplikasi;
2. buka halaman di browser;
3. bandingkan dengan Figma;
4. cek desktop;
5. cek mobile;
6. cek spacing;
7. cek typography;
8. cek responsive behavior;
9. cek overflow;
10. cek touch target.

Fitur UI belum dianggap selesai hanya karena tidak menghasilkan error.

---

# 15. Agent Rules

AI agent wajib:

- membaca `DESIGN.md`;
- membaca halaman terkait dalam dokumen ini;
- menggunakan reusable component yang sudah ada;
- tidak membuat style system baru;
- tidak memperkenalkan warna baru tanpa alasan;
- tidak membuat duplicate component;
- tidak mengubah responsive behavior tanpa persetujuan;
- tidak menambahkan fitur UI di luar PRD.

Jika Figma dan requirement business bertentangan:

laporkan konflik terlebih dahulu.

---

# 16. Implementation Priority

Urutan implementasi desain:

```text
Design Tokens
→ Shared Blade Components
→ Public Page Skeleton
→ Real Data Integration
→ Responsive QA
→ Admin / Filament Styling
→ Final Browser Verification
```

---

# 17. Source of Truth

Product:

```text
docs/01-PRD.md
```

Feature:

```text
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

Design:

```text
DESIGN.md
docs/06-DESIGN-HANDOFF.md
Figma TINTAPENA
```


---

# 18. Local Visual Reference Package

Karena integrasi Figma MCP tidak menjadi dependency wajib project, seluruh screen V1 memiliki referensi PNG lokal di:

`design-reference/`

Antigravity wajib menggunakan referensi lokal ini saat mengimplementasikan UI.

## Public Website

| Screen | Desktop | Mobile |
|---|---|---|
| Homepage | `design-reference/public/homepage-desktop.png` | `design-reference/public/homepage-mobile.png` |
| Article Detail | `design-reference/public/article-detail-desktop.png` | `design-reference/public/article-detail-mobile.png` |
| Category | `design-reference/public/category-desktop.png` | `design-reference/public/category-mobile.png` |
| Region | `design-reference/public/region-desktop.png` | `design-reference/public/region-mobile.png` |
| Latest News | `design-reference/public/latest-news-desktop.png` | `design-reference/public/latest-news-mobile.png` |
| Popular News | `design-reference/public/popular-news-desktop.png` | `design-reference/public/popular-news-mobile.png` |
| Search Results | `design-reference/public/search-results-desktop.png` | `design-reference/public/search-results-mobile.png` |
| Tag / Topic | `design-reference/public/tag-desktop.png` | `design-reference/public/tag-mobile.png` |
| Static Page | `design-reference/public/static-page-desktop.png` | `design-reference/public/static-page-mobile.png` |
| Contact | `design-reference/public/contact-desktop.png` | `design-reference/public/contact-mobile.png` |

## Newsroom

| Screen | Desktop | Mobile |
|---|---|---|
| Login | `design-reference/admin/login-desktop.png` | `design-reference/admin/login-mobile.png` |
| Dashboard | `design-reference/admin/dashboard-desktop.png` | `design-reference/admin/dashboard-mobile.png` |
| Article List | `design-reference/admin/article-list-desktop.png` | `design-reference/admin/article-list-mobile.png` |
| Article Editor | `design-reference/admin/article-editor-desktop.png` | `design-reference/admin/article-editor-mobile.png` |
| Media Library | `design-reference/admin/media-library-desktop.png` | `design-reference/admin/media-library-mobile.png` |
| Categories | `design-reference/admin/categories-desktop.png` | `design-reference/admin/categories-mobile.png` |
| Regions | `design-reference/admin/regions-desktop.png` | `design-reference/admin/regions-mobile.png` |
| Tags | `design-reference/admin/tags-desktop.png` | `design-reference/admin/tags-mobile.png` |
| Homepage Manager | `design-reference/admin/homepage-manager-desktop.png` | `design-reference/admin/homepage-manager-mobile.png` |
| Breaking News | `design-reference/admin/breaking-news-desktop.png` | `design-reference/admin/breaking-news-mobile.png` |
| Advertisements | `design-reference/admin/advertisements-desktop.png` | `design-reference/admin/advertisements-mobile.png` |
| Static Pages | `design-reference/admin/pages-desktop.png` | `design-reference/admin/pages-mobile.png` |
| Website Settings | `design-reference/admin/settings-desktop.png` | `design-reference/admin/settings-mobile.png` |

# 19. Visual Implementation Rule

Untuk setiap Feature ID yang memiliki UI:

1. baca requirement feature;
2. baca route;
3. baca desain di `DESIGN.md`;
4. buka kedua referensi Desktop dan Mobile;
5. implementasikan struktur visual;
6. hubungkan dengan real application data;
7. lakukan browser comparison sebelum fitur dinyatakan selesai.

Jangan menggunakan screenshot sebagai background atau image replacement untuk UI.

Screenshot hanya menjadi visual reference.

# 20. MCP Policy

Figma MCP bersifat opsional.

Jika MCP tersedia, agent dapat menggunakannya sebagai sumber context tambahan.

Jika MCP tidak tersedia, implementasi tidak boleh berhenti karena seluruh referensi wajib sudah tersedia pada:

- `DESIGN.md`
- `docs/06-DESIGN-HANDOFF.md`
- `design-reference/`
- `design-reference/manifest.json`

Jika terdapat perbedaan antara screenshot dan requirement product/business:

requirement product/business memiliki prioritas lebih tinggi, sedangkan visual appearance tetap mengikuti screenshot sejauh tidak menimbulkan konflik.
