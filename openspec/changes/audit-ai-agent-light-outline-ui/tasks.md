# Tasks: Global Filament Admin Light Outline UI

Urutan audit dan implementasi global untuk seluruh halaman/menu Filament admin.

---

## A. Revisi Scope dan Audit Sumber

- [x] A1. Revisi scope dari AI Agent only menjadi global Filament admin
  - Target mencakup semua custom page, resource page, widget/card, breadcrumb, header, table, form, modal, tabs, action, dan sidebar.

- [x] A2. Inventaris style global di `resources/css/filament/admin/theme.css`
  - Audit selector `.fi-*`, `.vc-*`, `.wacs-*`, `.fi-header`, `.fi-breadcrumbs`, `.fi-section`, `.fi-card`, `.fi-ta`, `.fi-fo`, `.fi-modal`.
  - Catat override shadow, rounded, gradient, font size, dan textarea.

- [x] A3. Inventaris custom page Blade
  - Audit `resources/views/filament/pages/*.blade.php`.
  - Cari class `shadow-*`, `bg-gradient-*`, `from-*`, `via-*`, `to-*`, `text-2xl+`, dan `rounded-2xl` yang perlu dinormalisasi.

- [x] A4. Inventaris metadata PHP Filament
  - Audit `app/Filament/**/*.php` untuk class gradient/shadow pada icon, badge, action, atau custom metadata.

---

## B. Standar Desain Global

- [x] B1. Tetapkan skala tipografi global admin
  - Breadcrumb: `text-xs` / 12px.
  - Page/header title: `text-xl` / 20px, maksimal `text-2xl` / 24px.
  - Section/card title: `text-base` / 16px.
  - Nested title/table header/label: `text-sm` / 14px.
  - Body/form/table content: `text-sm` / 14px.
  - Helper/meta/badge: `text-xs` / 12px.
  - Textarea/code-like content: `text-sm` / 14px monospace bila teknis.

- [x] B2. Tetapkan sistem visual global
  - Background solid surface, tanpa gradient.
  - Card/header/section/table/modal memakai outline border.
  - Rounded konsisten: container/card `1rem`, controls `0.75rem`, small badge/button `0.5rem` atau pill.
  - Tidak ada shadow aktif; depth dibuat lewat border, background, spacing.

- [x] B3. Tetapkan state interaktif global
  - Hover memakai background solid/muted dan border lebih kuat.
  - Focus memakai outline/ring ringan tanpa shadow.
  - Active/sidebar/tab memakai background solid, bukan gradient.

- [x] B4. Tetapkan textarea monospace global yang aman
  - Semua textarea Filament/custom memakai font monospace jika berisi prompt/template/code-like operational text.
  - Textarea chat bebas tetap terbaca; line-height 1.55-1.65.

---

## C. Implementasi Global

- [x] C1. Tambah token CSS global admin
  - Token surface, surface-muted, border, border-strong, text, muted.
  - Selector ditempatkan di `theme.css` agar berlaku ke semua halaman Filament.

- [x] C2. Terapkan no-shadow global
  - Reset `box-shadow` dan Tailwind shadow variable pada `.fi-main`, `.fi-page`, `.fi-header`, `.fi-section`, `.fi-card`, `.fi-modal`, `.fi-dropdown-panel`, `.fi-ta`, `.fi-fo`, `.fi-btn`, custom `.vc-*`, dan `.wacs-*`.

- [x] C3. Terapkan no-gradient global
  - Override background image gradient pada container, button, badge, icon, card, section, stat, dan custom elements.
  - Ganti metadata provider AI dari gradient ke solid outline.

- [x] C4. Terapkan outline rounded global
  - Header, breadcrumb, section, card, table container, modal, dropdown, field wrapper, tabs, action group, stat card mengikuti radius konsisten.

- [x] C5. Terapkan skala tipografi global
  - Page title/hero title compact.
  - Section/card title `text-base`.
  - Body/form/table `text-sm`.
  - Helper/breadcrumb/badge `text-xs`.

- [x] C6. Terapkan textarea monospace global
  - Selector `.fi-textarea textarea`, `.fi-input-wrp textarea`, custom page textarea, dan `.wacs-ai-agent-mono` memakai monospace.

- [x] C7. Normalisasi custom AI Agent page
  - Wrapper scoped tetap ada untuk class lokal, tetapi style global juga menutup halaman lain.

---

## D. Verifikasi

- [x] D1. Jalankan regex audit global
  - `rg -n "shadow|bg-gradient|from-|via-|to-|text-3xl|text-4xl" src/resources/views src/app/Filament`
  - Sisa hasil harus berupa konten nonaktif, teks dokumentasi, atau telah di-override global.

- [x] D2. Jalankan syntax check PHP target
  - `php -l src/app/Filament/Pages/AiAgent.php`.

- [x] D3. Jalankan build asset
  - `npm run build` dari folder `src`.

- [ ] D4. Verifikasi browser manual semua menu utama
  - Dashboard/home.
  - AI Agent.
  - VPoint Assistant.
  - Inbox WhatsApp.
  - Ticketing.
  - Resource table/form Master.
  - Resource table/form AI Knowledge.
  - Modal/action/dropdown.

- [ ] D5. Verifikasi visual light/dark mode
  - Border terlihat.
  - Text kontras.
  - No shadow/no gradient aktif secara visual.
  - Header, breadcrumb, card title, card body, table, form, modal seragam.

---

## E. Definition of Done

- [x] OpenSpec dan plan sudah global, bukan AI Agent only.
- [x] CSS global terscope ke admin/Filament dan custom components.
- [x] AI Agent tetap sesuai acceptance criteria awal.
- [x] Build asset berhasil.
- [ ] Browser manual seluruh menu utama selesai.