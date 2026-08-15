# TINTAPENA — Data Dictionary

Dokumen ini menjelaskan fungsi tabel dan field utama database TINTAPENA V1.

Source of truth struktur database:
`docs/database/erd.dbml`

---

# 1. users

Menyimpan akun yang dapat masuk ke Newsroom.

V1 hanya membutuhkan admin/redaksi internal.

### Field utama

- `id` — primary key.
- `name` — nama admin.
- `email` — email untuk login, harus unik.
- `password` — password yang sudah di-hash.
- `remember_token` — token Laravel untuk fitur remember login.

### Rules

- Tidak ada registrasi publik.
- Tidak ada akun pembaca pada V1.

---

# 2. categories

Mengelompokkan artikel berdasarkan jenis/topik berita.

Contoh:

- Ekonomi
- Politik
- Pemerintahan
- Hukum & Kriminal
- Pendidikan
- Kesehatan
- Pariwisata
- Olahraga
- Opini

### Field utama

- `name` — nama kategori.
- `slug` — URL kategori.
- `description` — deskripsi opsional.
- `is_active` — menentukan apakah kategori dapat digunakan untuk artikel baru.

### Rules

Kategori dan wilayah adalah dua konsep berbeda.

Contoh:

Category:
`Ekonomi`

Region:
`Bangka Tengah`

---

# 3. regions

Mengelompokkan artikel berdasarkan wilayah geografis.

Wilayah awal:

- Pangkalpinang
- Bangka
- Bangka Barat
- Bangka Tengah
- Bangka Selatan
- Belitung
- Belitung Timur

### Field utama

- `name`
- `slug`
- `description`
- `is_active`

### Rules

`region_id` pada artikel boleh kosong jika berita tidak terkait satu wilayah tertentu.

---

# 4. tags

Menyimpan topik yang lebih spesifik daripada kategori.

Contoh:

- PT Timah
- UMKM
- Pilkada
- ASN
- Pariwisata Babel

### Field utama

- `name`
- `slug`

### Relationship

Article ↔ Tag = many-to-many.

---

# 5. article_tag

Pivot table yang menghubungkan artikel dan tag.

### Field

- `article_id`
- `tag_id`

Kombinasi keduanya harus unik.

---

# 6. media

Menyimpan metadata file gambar yang di-upload melalui Newsroom.

### Field utama

- `uploaded_by` — admin yang meng-upload.
- `disk` — Laravel filesystem disk.
- `path` — lokasi file.
- `filename` — nama file yang disimpan.
- `original_filename` — nama file asli.
- `mime_type`
- `extension`
- `size`
- `width`
- `height`
- `alt_text`
- `caption`
- `photo_credit`

### Rules

File gambar artikel sebaiknya tidak disimpan langsung di tabel `articles`.

Artikel hanya menyimpan referensi:

`featured_media_id`

---

# 7. articles

Tabel utama seluruh berita TINTAPENA.

### Relationships

Setiap artikel:

- memiliki satu author;
- memiliki satu category;
- dapat memiliki satu region;
- dapat memiliki satu featured media;
- dapat memiliki banyak tags.

### Field utama

#### author_id

Admin/redaksi yang membuat artikel.

#### category_id

Kategori utama artikel.

Wajib diisi.

#### region_id

Wilayah berita.

Opsional.

#### featured_media_id

Gambar utama artikel.

Opsional selama masih Draft.

#### title

Judul utama artikel.

#### subtitle

Subjudul atau deck artikel.

Opsional.

#### slug

Digunakan sebagai URL publik.

Contoh:

`pemprov-babel-dorong-penguatan-ekonomi`

Harus unik.

#### excerpt

Ringkasan pendek artikel.

Digunakan pada card/list berita jika diperlukan.

#### content

Isi utama artikel.

#### status

Allowed values:

- `draft`
- `scheduled`
- `published`
- `archived`

#### scheduled_at

Waktu artikel dijadwalkan untuk terbit.

Digunakan jika status `scheduled`.

#### published_at

Waktu artikel benar-benar diterbitkan.

#### archived_at

Waktu artikel diarsipkan.

#### seo_title

Judul khusus SEO.

Jika kosong, dapat menggunakan `title`.

#### meta_description

Deskripsi untuk search engine.

#### views_count

Total view artikel sepanjang waktu.

Tidak boleh diedit manual dari Newsroom.

### Publication Rules

Artikel hanya boleh tampil kepada publik jika:

```text
status = published
AND
published_at <= current time
```

Draft tidak boleh diakses publik.

Scheduled yang belum waktunya tidak boleh diakses publik.

Archived tidak muncul pada feed publik normal.

---

# 8. homepage_slots

Mengelola penempatan artikel secara manual di homepage.

Contoh `slot_key`:

- `headline_main`
- `headline_2`
- `headline_3`
- `editor_pick_1`
- `editor_pick_2`
- `editor_pick_3`
- `editor_pick_4`

### Field utama

- `slot_key` — identifier teknis slot.
- `slot_name` — nama yang ditampilkan di Newsroom.
- `article_id` — artikel yang ditempatkan.
- `updated_by` — admin terakhir yang mengubah.
- `sort_order`
- `is_active`

### Important Rule

Jangan menambahkan:

```text
is_headline
is_editor_pick
```

ke tabel `articles`.

Headline adalah **penempatan homepage**, bukan properti permanen artikel.

---

# 9. breaking_news

Mengelola ticker Breaking News.

### Field utama

- `article_id` — artikel terkait, opsional.
- `created_by`
- `headline` — headline manual opsional.
- `target_url` — URL manual opsional.
- `starts_at`
- `ends_at`
- `is_active`

### Rules

Breaking News dapat berasal dari:

1. artikel TINTAPENA; atau
2. headline + URL manual.

Breaking News hanya tampil jika:

- aktif;
- sudah melewati `starts_at`;
- belum melewati `ends_at`.

Tanggal mulai/selesai boleh nullable sesuai implementasi final.

---

# 10. article_view_stats

Menyimpan agregasi jumlah view artikel per periode waktu.

Tujuan utamanya:

- ranking 24 jam;
- ranking 7 hari.

### Field utama

- `article_id`
- `period_start`
- `views_count`

### Example

```text
article_id: 125
period_start: 2026-08-16 04:00:00
views_count: 37
```

### Rules

Jangan membuat satu database row untuk setiap page view pada V1.

Gunakan agregasi untuk menjaga database tetap ringan pada shared hosting.

`articles.views_count` menyimpan total sepanjang waktu.

---

# 11. advertisements

Menyimpan iklan website.

### Field utama

- `name`
- `type`
- `placement_key`
- `media_id`
- `content`
- `target_url`
- `starts_at`
- `ends_at`
- `is_active`
- `sort_order`

### type

Allowed:

- `image`
- `script`

### Example placement_key

- `homepage_top`
- `homepage_middle`
- `article_inline`
- `article_sidebar`
- `category_sidebar`

Daftar final placement mengikuti implementasi desain.

---

# 12. pages

Menyimpan halaman statis.

Contoh:

- Tentang Kami
- Redaksi
- Pedoman Media Siber
- Privacy Policy
- Disclaimer

### Field utama

- `title`
- `slug`
- `content`
- `status`
- `seo_title`
- `meta_description`
- `published_at`
- `created_by`
- `updated_by`

### status

- `draft`
- `published`

Hanya halaman Published yang dapat diakses publik.

---

# 13. settings

Menyimpan konfigurasi global website.

### Field utama

- `group_name`
- `setting_key`
- `value`
- `value_type`

### Example

```text
general.site_name
general.tagline

contact.email
contact.whatsapp
contact.address

social.instagram
social.facebook

seo.default_title
seo.default_description

analytics.google_measurement_id
```

### Rules

Jangan menyimpan credential sensitif seperti:

- database password;
- APP_KEY;
- SMTP password;
- API secret;

di tabel settings.

Credential sensitif tetap berada di `.env`.

---

# 14. contact_messages

Menyimpan pesan yang masuk dari halaman Kontak.

### Field utama

- `name`
- `email`
- `subject`
- `message`
- `status`

### status

- `unread`
- `read`
- `archived`

### Rules

Input harus divalidasi.

Form harus memiliki proteksi spam/rate limiting.

---

# Relationship Summary

```text
User
 ├── Articles
 ├── Media
 ├── Pages
 ├── Homepage updates
 └── Breaking News

Category
 └── Articles

Region
 └── Articles

Article
 ├── Category
 ├── Region
 ├── Featured Media
 ├── Tags
 ├── Homepage Slots
 ├── Breaking News
 └── View Stats

Media
 ├── Article Featured Image
 └── Advertisement

Tag
 └── Articles
```

---

# Database Design Rules

1. Jangan mengubah nama tabel atau relationship tanpa memperbarui:
   - `erd.dbml`
   - data dictionary
   - architecture specification.

2. Jangan menambahkan field hanya untuk kebutuhan UI jika dapat diturunkan dari relationship yang sudah ada.

3. Jangan menghapus data taxonomy hanya karena tidak aktif.

4. Gunakan foreign key untuk menjaga integritas relationship.

5. Artikel Published adalah satu-satunya artikel yang boleh muncul pada public feed.

6. Struktur database harus tetap ringan dan cocok untuk MySQL pada shared hosting.

7. Perubahan schema harus dilakukan melalui Laravel migration.
