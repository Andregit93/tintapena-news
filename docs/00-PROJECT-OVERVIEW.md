# TINTAPENA — Project Overview

## 1. Nama Project

TINTAPENA

Tagline:

> Menulis Berdasarkan Fakta

## 2. Jenis Project

TINTAPENA adalah portal berita digital lokal yang berfokus pada informasi dan pemberitaan Bangka Belitung.

Website terdiri dari dua bagian utama:

1. Public Website
2. TINTAPENA Newsroom / Admin

---

## 3. Tujuan Project

Membangun portal berita yang:

- cepat dan responsif;
- mudah digunakan pembaca;
- mudah dikelola oleh pemilik/redaksi;
- optimal di desktop dan mobile;
- memiliki pengelolaan berita yang sederhana;
- mendukung SEO;
- dapat dijalankan pada Hostinger Premium Shared Hosting.

---

## 4. Target Pengguna

### Public

Pembaca umum.

Tidak memerlukan akun atau login.

### Admin

Pemilik/redaksi TINTAPENA.

Admin dapat:

- menulis berita;
- mengedit berita;
- menyimpan draft;
- menjadwalkan berita;
- menerbitkan berita;
- mengarsipkan berita;
- mengelola media;
- mengatur headline homepage;
- mengatur Breaking News;
- mengelola iklan;
- mengelola kategori, wilayah, dan tag;
- mengelola halaman website;
- mengatur konfigurasi website.

---

## 5. Scope V1

Fitur utama V1:

### Public Website

- Homepage
- Detail Berita
- Kategori
- Wilayah
- Berita Terbaru
- Berita Terpopuler
- Search
- Tag / Topik
- Halaman Statis
- Kontak
- Breaking News
- Iklan
- Related News
- Social Share
- SEO

### Newsroom

- Login Admin
- Dashboard
- Berita
- Media Library
- Kategori
- Wilayah
- Tag
- Homepage Manager
- Breaking News Manager
- Advertisement Management
- Static Pages
- Website Settings

---

## 6. Out of Scope V1

Belum termasuk:

- registrasi pembaca;
- akun pembaca;
- komentar;
- forum;
- chat;
- newsletter management;
- push notification;
- multi-role editorial workflow;
- aplikasi Android/iOS;
- sistem subscription berbayar.

---

## 7. Technology Stack

Backend:

- Laravel

Public Frontend:

- Blade
- Livewire
- Alpine.js
- Tailwind CSS

Admin:

- Filament

Database:

- MySQL

Hosting:

- Hostinger Premium Shared Hosting

---

## 8. Technical Principles

Project harus tetap sederhana dan sesuai shared hosting.

Untuk V1 hindari:

- React SPA
- Vue SPA
- Next.js
- microservices
- Elasticsearch
- Redis sebagai dependency wajib
- Node.js backend terpisah

---

## 9. Design Source

Visual source of truth:

- Figma TINTAPENA

Design rules:

- `DESIGN.md`

Seluruh implementasi frontend harus mengikuti design system TINTAPENA.

---

## 10. Development Principle

Pengembangan dilakukan secara bertahap per fitur.

Setiap fitur harus:

1. memiliki spesifikasi yang jelas;
2. memiliki acceptance criteria;
3. mengikuti database dan architecture specification;
4. diuji sebelum dianggap selesai;
5. tidak mengubah keputusan arsitektur tanpa persetujuan.

---

## 11. Project Status

Status saat ini:

- UI/UX Public Website: selesai
- UI/UX Newsroom: selesai
- Design System: selesai
- Technical Specification: dalam penyusunan
- Development Laravel: belum dimulai