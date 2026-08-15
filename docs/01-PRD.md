# TINTAPENA
# Product Requirements Document — V1

## 1. Product Overview

TINTAPENA adalah portal berita digital lokal yang berfokus pada pemberitaan Bangka Belitung.

Tagline:

> Menulis Berdasarkan Fakta

Sistem terdiri dari:

1. Public Website
2. Newsroom / Admin

---

## 2. Product Goal

Tujuan V1 adalah menyediakan portal berita yang:

- cepat;
- responsif;
- mudah dibaca;
- mudah dikelola oleh redaksi;
- mendukung publikasi berita dari desktop maupun mobile;
- SEO-friendly;
- dapat berjalan pada Hostinger Premium Shared Hosting.

---

## 3. User

### Public Reader

Pembaca dapat:

- membaca berita;
- melihat berita terbaru;
- melihat berita terpopuler;
- mencari berita;
- melihat berita berdasarkan kategori;
- melihat berita berdasarkan wilayah;
- melihat berita berdasarkan topik/tag;
- membagikan berita;
- menghubungi redaksi.

Tidak ada akun atau login pembaca pada V1.

### Admin

Admin adalah pemilik/redaksi TINTAPENA.

Admin dapat:

- login ke Newsroom;
- membuat berita;
- mengedit berita;
- menyimpan draft;
- preview berita;
- menjadwalkan berita;
- menerbitkan berita;
- mengarsipkan berita;
- mengelola media;
- kategori;
- wilayah;
- tag;
- headline homepage;
- Pilihan Redaksi;
- Breaking News;
- iklan;
- halaman statis;
- pengaturan website.

---

## 4. Public Website V1

Halaman yang tersedia:

- Homepage
- Detail Berita
- Kategori
- Wilayah
- Berita Terbaru
- Berita Terpopuler
- Search
- Tag / Topik
- Tentang Kami
- Kontak
- halaman statis lainnya

Homepage memiliki:

- Breaking News
- Headline Utama
- berita pendukung
- Berita Terbaru
- Terpopuler
- Bangka Belitung
- Politik & Pemerintahan
- Ekonomi
- Hukum & Kriminal
- Pariwisata
- Pilihan Redaksi
- iklan

---

## 5. Newsroom V1

Menu utama:

- Dashboard
- Berita
- Media
- Kategori
- Wilayah
- Tag
- Homepage
- Breaking News
- Iklan
- Halaman
- Pengaturan

---

## 6. Article Workflow

Status artikel:

- Draft
- Scheduled
- Published
- Archived

Alur dasar:

Draft
→ Preview
→ Publish

atau:

Draft
→ Schedule
→ Published otomatis sesuai jadwal

Artikel Draft dan Scheduled yang belum waktunya terbit tidak boleh dapat diakses publik.

---

## 7. Regional Coverage

Wilayah utama:

- Pangkalpinang
- Bangka
- Bangka Barat
- Bangka Tengah
- Bangka Selatan
- Belitung
- Belitung Timur

---

## 8. Main Categories

Kategori awal:

- Politik
- Pemerintahan
- Ekonomi
- Hukum & Kriminal
- Pendidikan
- Kesehatan
- Pariwisata
- Olahraga
- Opini

Kategori dapat ditambah dan diubah melalui Newsroom.

---

## 9. Non-Functional Requirements

Website harus:

- responsive;
- mobile friendly;
- memiliki URL yang SEO-friendly;
- menggunakan HTTPS di production;
- memiliki validasi upload media;
- memiliki proteksi authentication admin;
- memiliki loading page yang ringan;
- mengikuti desain Figma TINTAPENA;
- dapat berjalan tanpa Redis sebagai dependency wajib.

---

## 10. Technology Constraints

Stack V1:

- Laravel
- Blade
- Livewire
- Alpine.js
- Tailwind CSS
- Filament
- MySQL

Tidak menggunakan untuk V1:

- React SPA
- Vue SPA
- Next.js
- microservices
- Elasticsearch
- Redis sebagai dependency wajib

---

## 11. Out of Scope V1

Belum dibuat pada V1:

- registrasi pembaca;
- login pembaca;
- komentar;
- forum;
- chat;
- newsletter manager;
- push notification;
- aplikasi Android/iOS;
- subscription berbayar;
- multi-level editorial approval.

---

## 12. Design Reference

Visual source of truth:

Figma TINTAPENA

Design specification:

`DESIGN.md`

Implementasi harus mengikuti desain tersebut selama tidak bertentangan dengan requirement teknis.

---

## 13. V1 Definition of Success

V1 dianggap berhasil apabila admin dapat:

1. login;
2. membuat berita;
3. mengunggah foto;
4. menyimpan draft;
5. preview;
6. menerbitkan atau menjadwalkan berita;
7. mengatur headline;
8. mengaktifkan Breaking News;
9. mengelola iklan;
10. melihat berita tersebut tampil dengan benar pada website publik.

Website publik harus dapat digunakan dengan baik pada desktop dan mobile.