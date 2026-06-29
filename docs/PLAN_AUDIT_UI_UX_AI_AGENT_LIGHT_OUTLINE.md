# PLAN Global Filament Admin UI: Light Outline, Monospace, No Shadow/Gradient

**Tanggal:** 2026-06-29  
**Status:** Implementasi global berjalan  
**OpenSpec:** `openspec/changes/audit-ai-agent-light-outline-ui/`  
**Target utama:** Semua menu/pages Filament admin ringan, konsisten, rapi, dan presisi.

---

## 1. Tujuan

Mengaudit dan menerapkan UI/UX global seluruh Filament admin agar:

- Theme terasa ringan dan bersih di semua menu/pages.
- Breadcrumb, header/hero, section, card, table, form, modal, dropdown, button, badge, dan sidebar konsisten.
- Semua card/container memakai outline border dan rounded konsisten.
- Textarea teknis editable dan readonly/preview memakai monospace.
- Tidak ada shadow dan gradient aktif secara visual.
- Ukuran font hero/page title dan card title lebih compact.
- Estetika tetap baik lewat spacing, border, warna solid, dan hierarchy visual.

---

## 2. File Audit dan Implementasi

| File/Area | Fokus Audit |
|---|---|
| `src/resources/css/filament/admin/theme.css` | Global design tokens, Filament selectors, no-shadow/no-gradient, typography, card/table/form/modal |
| `src/resources/views/filament/pages/*.blade.php` | Custom page hero/card/textarea/button/stat |
| `src/resources/views/components/**/*.blade.php` | Reusable stat/card/body component |
| `src/app/Filament/**/*.php` | Resource/page metadata, breadcrumb, icon/badge/action class |
| `src/public/build/**` | Output build Vite; tidak diedit manual |

---

## 3. Skala Tipografi Global

| Elemen | Ukuran Target | Weight | Batasan |
|---|---:|---:|---|
| Breadcrumb | `text-xs` / 12px | 500-600 | Muted |
| Page/header title | `text-xl` / 20px | 700 | Maksimal `text-2xl` |
| Hero title custom | `text-xl` / 20px | 700 | Maksimal `text-2xl`, hindari `text-3xl`+ |
| Hero/page description | `text-sm` / 14px | 400-500 | Muted |
| Section/card title | `text-base` / 16px | 700 | Hindari `text-xl`+ |
| Nested title/table header | `text-sm` / 14px | 700 | Compact |
| Body/form/table content | `text-sm` / 14px | 400-500 | Default content |
| Form label | `text-sm` / 14px | 600 | Konsisten |
| Helper/meta/badge | `text-xs` / 12px | 400-600 | Muted/compact |
| Textarea monospace | `text-sm` / 14px | 400 | Line-height 1.55-1.65 |

---

## 4. Component Style Map Global

| Komponen | Style Seragam |
|---|---|
| Breadcrumb | Text kecil, muted, separator konsisten, outline ringan, hover solid |
| Page Header | Outline card, background solid, radius global, title compact |
| Hero Custom | Satu keluarga dengan page header/card, tanpa gradient/shadow |
| Card/Section | Background solid, border outline, radius 1rem, padding konsisten |
| Card Title | `text-base`, weight 700, margin seragam |
| Card Body | `text-sm`, spacing vertikal konsisten, helper muted |
| Table | Container outline, header compact, row hover solid, no shadow |
| Form Field | Label/control/helper spacing konsisten, border outline |
| Input/Textarea/Select | Radius 0.75rem, focus border/ring ringan, no shadow |
| Button | Solid/outline, radius konsisten, hover solid, no shadow/gradient |
| Badge/Icon | Warna solid lembut, border aksen opsional, no gradient |
| Modal/Dropdown | Solid surface, border outline, radius global, no shadow |
| Sidebar/Topbar | Solid surface, active/hover solid, no gradient/shadow |
| Divider | Border tipis atau spacing, bukan shadow |

---

## 5. Rencana Eksekusi

### Fase A — Audit Global

- [x] Audit `theme.css` global.
- [x] Audit custom Blade pages.
- [x] Audit metadata PHP provider/icon.
- [x] Tentukan selector global aman untuk Filament/custom admin.

### Fase B — Revisi OpenSpec

- [x] Ubah scope dari AI Agent only ke global Filament admin.
- [x] Tambahkan requirement typography global.
- [x] Tambahkan requirement component consistency global.
- [x] Tambahkan requirement textarea monospace global.

### Fase C — Implementasi Global

- [x] Tambah token CSS global admin.
- [x] Tambah reset no-shadow/no-gradient global.
- [x] Standarisasi header, breadcrumb, section, card, table, form, modal, dropdown, button, badge.
- [x] Standarisasi font size global.
- [x] Standarisasi textarea monospace global.
- [x] Pertahankan patch khusus AI Agent sebagai class lokal tambahan.

### Fase D — Verifikasi Teknis

- [x] Jalankan regex audit global.
- [x] Jalankan PHP syntax check target.
- [x] Jalankan build Vite.
- [ ] Verifikasi browser manual semua menu utama.
- [ ] Verifikasi light/dark mode semua menu utama.

---

## 6. Acceptance Checklist

- [x] OpenSpec dan plan sudah mencakup seluruh menu/pages.
- [x] CSS global berlaku ke komponen Filament umum.
- [x] Breadcrumb global ringan, muted, dan seragam.
- [x] Page/header/hero title compact, maksimal `text-2xl`.
- [x] Card/section title compact, `text-base`.
- [x] Body/table/form content memakai `text-sm`.
- [x] Helper/meta/badge memakai `text-xs`.
- [x] Card/container memakai outline border dan rounded konsisten.
- [x] Shadow aktif direset global.
- [x] Gradient aktif direset global.
- [x] Textarea teknis memakai monospace.
- [x] Provider icon AI Agent tidak memakai gradient.
- [x] Build asset berhasil.
- [ ] Browser manual seluruh menu utama selesai.

---

## 7. Catatan Implementasi

Implementasi global dilakukan terutama melalui `src/resources/css/filament/admin/theme.css` agar seluruh page/resource Filament ikut berubah tanpa harus mengedit setiap Blade satu per satu. Custom AI Agent tetap memiliki class `wacs-ai-agent-*` untuk precision styling tambahan, tetapi global tokens juga berlaku ke pages lain.