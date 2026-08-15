# TINTAPENA — Feature Specification V1

Dokumen ini adalah daftar fitur resmi TINTAPENA V1.

Setiap fitur memiliki ID agar implementasi oleh agent dilakukan satu per satu dan tidak melebar ke fitur lain.

---

# 1. AUTHENTICATION

## AUTH-001 — Admin Login
Admin dapat login ke Newsroom menggunakan email dan password.

Acceptance summary:
- hanya admin yang dapat masuk;
- tidak ada registrasi publik;
- user yang belum login tidak dapat mengakses `/admin`.

## AUTH-002 — Admin Logout
Admin dapat keluar dari Newsroom dan session diakhiri dengan benar.

---

# 2. DASHBOARD

## DASH-001 — Dashboard Newsroom
Dashboard menampilkan ringkasan:

- jumlah berita diterbitkan;
- draft;
- berita terjadwal;
- berita terbaru;
- artikel terpopuler;
- shortcut membuat berita.

---

# 3. CATEGORY

## CATEGORY-001 — Daftar Kategori
Admin dapat melihat semua kategori.

## CATEGORY-002 — Tambah Kategori
Admin dapat membuat kategori baru.

Field utama:
- nama;
- slug;
- deskripsi;
- status aktif.

## CATEGORY-003 — Edit Kategori
Admin dapat mengubah kategori.

## CATEGORY-004 — Nonaktifkan Kategori
Kategori dapat dinonaktifkan tanpa menghapus artikel lama.

---

# 4. REGION

## REGION-001 — Daftar Wilayah
Admin dapat melihat seluruh wilayah.

## REGION-002 — Tambah Wilayah
Admin dapat membuat wilayah baru.

## REGION-003 — Edit Wilayah
Admin dapat mengubah data wilayah.

Wilayah awal:

- Pangkalpinang
- Bangka
- Bangka Barat
- Bangka Tengah
- Bangka Selatan
- Belitung
- Belitung Timur

---

# 5. TAG

## TAG-001 — Daftar Tag
Admin dapat melihat tag yang tersedia.

## TAG-002 — Tambah Tag
Admin dapat membuat tag.

## TAG-003 — Edit Tag
Admin dapat mengubah tag.

## TAG-004 — Hapus Tag Tidak Terpakai
Tag yang tidak digunakan artikel dapat dihapus.

---

# 6. ARTICLE

## ARTICLE-001 — Create Draft
Admin dapat membuat artikel dan menyimpannya sebagai Draft.

Field dasar:

- judul;
- subtitle;
- slug;
- ringkasan;
- konten;
- kategori;
- wilayah;
- tag.

## ARTICLE-002 — Edit Draft
Admin dapat mengedit artikel Draft.

## ARTICLE-003 — Featured Image
Admin dapat menentukan gambar utama artikel.

Gambar memiliki:

- alt text;
- caption;
- kredit foto.

## ARTICLE-004 — Article Classification
Artikel dapat memiliki:

- satu kategori;
- satu wilayah opsional;
- banyak tag.

## ARTICLE-005 — Preview
Admin dapat melihat tampilan artikel sebelum diterbitkan.

Preview tidak boleh dianggap sebagai artikel publik.

## ARTICLE-006 — Publish Article
Admin dapat menerbitkan artikel.

Ketika diterbitkan:

- status menjadi `published`;
- `published_at` diisi;
- artikel dapat diakses publik;
- artikel masuk Berita Terbaru.

## ARTICLE-007 — Schedule Article
Admin dapat menentukan waktu penerbitan di masa depan.

Sebelum waktunya:
- artikel tidak dapat diakses publik.

Ketika waktunya tiba:
- artikel otomatis menjadi Published.

## ARTICLE-008 — Archive Article
Admin dapat mengarsipkan artikel.

Artikel arsip tidak ditampilkan dalam feed publik normal.

## ARTICLE-009 — Article SEO
Admin dapat mengatur:

- SEO title;
- meta description;
- slug.

## ARTICLE-010 — Article List
Admin dapat melihat daftar artikel dengan filter:

- status;
- kategori;
- wilayah;
- tanggal;
- pencarian.

---

# 7. MEDIA

## MEDIA-001 — Upload Media
Admin dapat upload gambar.

## MEDIA-002 — Media Library
Admin dapat melihat seluruh media.

## MEDIA-003 — Media Metadata
Admin dapat mengatur:

- alt text;
- caption;
- photo credit.

## MEDIA-004 — Select Featured Image
Media dari library dapat digunakan sebagai featured image artikel.

---

# 8. PUBLIC ARTICLE

## PUBLIC-001 — Article Detail
Pembaca dapat membuka artikel Published melalui URL:

`/berita/{slug}`

## PUBLIC-002 — Related News
Halaman artikel menampilkan berita terkait.

## PUBLIC-003 — Social Share
Artikel dapat dibagikan melalui:

- WhatsApp;
- Facebook;
- X;
- salin tautan.

---

# 9. HOMEPAGE

## HOME-001 — Homepage Public
Homepage menampilkan bagian utama TINTAPENA sesuai desain Figma.

## HOME-002 — Headline Utama
Admin dapat menentukan artikel untuk Headline Utama.

## HOME-003 — Supporting Headlines
Admin dapat menentukan headline pendukung.

## HOME-004 — Pilihan Redaksi
Admin dapat memilih artikel sebagai Pilihan Redaksi.

## HOME-005 — Automatic News Sections
Bagian tertentu homepage dapat otomatis mengambil artikel berdasarkan:

- terbaru;
- kategori;
- wilayah.

---

# 10. BREAKING NEWS

## BREAKING-001 — Create Breaking News
Admin dapat membuat Breaking News dari artikel atau headline manual.

## BREAKING-002 — Activate Breaking News
Breaking News dapat diaktifkan atau dinonaktifkan.

## BREAKING-003 — Breaking Schedule
Breaking News dapat memiliki:

- waktu mulai;
- waktu selesai.

## BREAKING-004 — Breaking Ticker
Breaking News aktif tampil pada website publik.

---

# 11. PUBLIC LISTING

## LIST-001 — Latest News
Halaman `/terbaru` menampilkan artikel berdasarkan waktu publikasi terbaru.

## LIST-002 — Popular News
Halaman `/terpopuler` menampilkan artikel berdasarkan jumlah pembaca.

Filter:

- 24 jam;
- 7 hari.

## LIST-003 — Category Page
Halaman:

`/kategori/{slug}`

menampilkan artikel dari kategori tertentu.

## LIST-004 — Region Page
Halaman:

`/wilayah/{slug}`

menampilkan artikel dari wilayah tertentu.

## LIST-005 — Tag Page
Halaman:

`/topik/{slug}`

menampilkan artikel berdasarkan tag.

---

# 12. SEARCH

## SEARCH-001 — Search Articles
Pembaca dapat mencari berita menggunakan keyword.

Route:

`/cari?q={keyword}`

## SEARCH-002 — Search Filters
Hasil pencarian dapat difilter sesuai kebutuhan V1.

## SEARCH-003 — Empty Search State
Jika tidak ditemukan hasil, website menampilkan pesan yang sesuai.

---

# 13. ADVERTISEMENT

## ADS-001 — Create Advertisement
Admin dapat membuat iklan.

## ADS-002 — Advertisement Placement
Iklan dapat ditempatkan pada posisi tertentu di website.

## ADS-003 — Advertisement Schedule
Iklan dapat memiliki:

- tanggal mulai;
- tanggal selesai;
- status aktif.

## ADS-004 — Public Advertisement
Iklan aktif ditampilkan pada posisi yang telah ditentukan.

---

# 14. STATIC PAGES

## PAGE-001 — Manage Static Pages
Admin dapat membuat dan mengedit halaman statis.

Contoh:

- Tentang Kami;
- Redaksi;
- Pedoman Media Siber;
- Privacy Policy;
- Disclaimer.

## PAGE-002 — Public Static Page
Halaman Published dapat diakses oleh pembaca.

---

# 15. CONTACT

## CONTACT-001 — Contact Page
Website menyediakan halaman Kontak.

## CONTACT-002 — Contact Form
Pembaca dapat mengirim pesan kepada redaksi.

Form harus memiliki validasi dan perlindungan spam dasar.

---

# 16. SETTINGS

## SETTINGS-001 — General Settings
Admin dapat mengatur:

- nama website;
- tagline;
- informasi dasar.

## SETTINGS-002 — Contact Settings
Admin dapat mengatur:

- email;
- WhatsApp;
- alamat;
- jam kontak.

## SETTINGS-003 — Social Settings
Admin dapat mengatur akun media sosial.

## SETTINGS-004 — SEO Settings
Admin dapat mengatur SEO global website.

## SETTINGS-005 — Analytics Settings
Admin dapat memasukkan konfigurasi analytics yang dibutuhkan.

---

# 17. SYSTEM

## SYSTEM-001 — Responsive Website
Website harus bekerja baik pada desktop dan mobile.

## SYSTEM-002 — Admin Mobile Support
Fungsi utama Newsroom harus dapat digunakan melalui smartphone.

## SYSTEM-003 — Scheduler
Laravel Scheduler digunakan untuk proses seperti penerbitan artikel terjadwal.

## SYSTEM-004 — Sitemap
Website menyediakan sitemap untuk artikel Published dan halaman publik.

## SYSTEM-005 — Error Handling
Website menyediakan halaman error yang layak untuk kondisi seperti 404 dan 500.

---

# Development Rule

Agent tidak boleh mengimplementasikan seluruh module sekaligus.

Implementasi dilakukan berdasarkan Feature ID.

Contoh:

Implement `ARTICLE-001` only.

Fitur dianggap selesai hanya setelah acceptance criteria dan test terkait berhasil.