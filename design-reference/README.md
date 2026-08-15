# TINTAPENA — Design Reference

Folder ini berisi referensi visual final hasil export Figma untuk implementasi TINTAPENA.

## Cara pakai oleh Antigravity

Sebelum mengimplementasikan sebuah screen:

1. baca `DESIGN.md`;
2. baca `docs/06-DESIGN-HANDOFF.md`;
3. baca Feature ID terkait di `docs/02-FEATURES.md`;
4. buka PNG Desktop dan Mobile yang sesuai di folder ini;
5. implementasikan menggunakan stack project yang sudah ditetapkan;
6. jangan redesign UI;
7. lakukan visual QA terhadap PNG setelah implementasi.

## Struktur

- `public/` — referensi website pembaca.
- `admin/` — referensi Newsroom / Filament.
- `manifest.json` — mapping machine-readable screen → feature → route → PNG.
- `visual-index.jpg` — indeks thumbnail seluruh export, jika tersedia.

## Penting

- Isi berita, nama, tanggal, view count, foto, email, dan data contoh pada PNG adalah mock content kecuali ditetapkan sebagai data produksi.
- PNG adalah referensi visual, bukan sumber business logic.
- Business logic tetap mengikuti PRD, Features, Database, Routes, dan Acceptance Criteria.
- Desktop reference menggunakan lebar 1440 px.
- Mobile reference menggunakan lebar 390 px.
