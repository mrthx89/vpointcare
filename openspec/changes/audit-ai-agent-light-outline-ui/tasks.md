# Tasks: Audit UI/UX AI Agent Light Outline Theme

Urutan audit dan implementasi yang direkomendasikan.

---

## A. Audit Sumber UI

- [ ] A1. Inventaris semua card/panel di `resources/views/filament/pages/ai-agent.blade.php`
  - Catat class `rounded-*`, `border-*`, `bg-*`, `shadow-*`, dan layout spacing.
  - Kelompokkan menjadi card utama, nested card, form control, action button, dan readonly preview.

- [ ] A2. Inventaris semua textarea dan area teks teknis
  - Tandai textarea editable seperti prompt sistem, template, instruksi, dan input konfigurasi.
  - Tandai readonly textarea/preview jika ada.
  - Pastikan target monospace tidak mengenai label, helper text, atau heading.

- [ ] A3. Inventaris semua shadow dan gradient
  - Cari `shadow`, `box-shadow`, `bg-gradient`, `from-`, `via-`, dan `to-` pada Blade/CSS/PHP metadata.
  - Bedakan class yang aktif untuk AI Agent dan class global yang tidak berdampak langsung.

- [ ] A4. Audit `app/Filament/Pages/AiAgent.php`
  - Periksa metadata provider seperti `icon_class`.
  - Ganti rencana class gradient menjadi warna solid/outline bila metadata dipakai oleh Blade.

---

## B. Standar Desain

- [ ] B0. Tetapkan component style map
  - Breadcrumb: muted, small, separator konsisten, tanpa shadow.
  - Hero/header: outline card, background solid, radius sama dengan card utama.
  - Card title: ukuran, weight, margin, dan warna seragam.
  - Card body: spacing, helper text, dan section gap seragam.
  - Button/badge/icon: solid/outline tanpa gradient dan tanpa shadow.

- [ ] B1. Tetapkan token visual AI Agent
  - Card radius: konsisten untuk semua card utama.
  - Input radius: konsisten dan sedikit lebih kecil dari card bila diperlukan.
  - Border: gunakan outline lembut untuk light/dark mode.
  - Background: solid surface, tanpa gradient.

- [ ] B2. Tetapkan standar textarea monospace
  - Gunakan stack `ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace`.
  - Gunakan line-height nyaman untuk prompt/template panjang.
  - Pastikan readonly dan editable memakai standar yang sama.

- [ ] B3. Tetapkan state interaktif tanpa shadow
  - Hover memakai perubahan border/background solid.
  - Focus memakai ring/border yang jelas tanpa drop shadow.
  - Disabled/readonly tetap terlihat berbeda tanpa opacity berlebihan.

---

## C. Implementasi Terarah

- [ ] C1. Tambah wrapper class khusus halaman AI Agent bila belum ada
  - Contoh target: `.wacs-ai-agent` atau class existing yang spesifik.
  - Tujuan: membatasi override CSS agar tidak merusak halaman lain.

- [ ] C2. Rapikan struktur halaman seragam di Blade
  - Breadcrumb, hero, card title, card body, field group, dan action row mengikuti component style map.
  - Hindari variasi class lokal yang tidak perlu.

- [ ] C3. Rapikan card outline di Blade
  - Hapus class shadow pada card/panel.
  - Samakan `rounded-*` antar card sejenis.
  - Ganti gradient/background berat dengan solid surface.

- [ ] C4. Terapkan monospace ke textarea editable
  - Tambahkan class utility atau selector CSS khusus AI Agent.
  - Pastikan tidak tertimpa aturan global `* { font-family: ... !important; }`.

- [ ] C5. Terapkan monospace ke textarea readonly/preview
  - Gunakan class yang sama dengan editable textarea.
  - Pastikan visual readonly tetap jelas lewat border/background, bukan shadow.

- [ ] C6. Hilangkan gradient provider/icon
  - Update class metadata di `AiAgent.php` dari `bg-gradient-to-br from-* to-*` ke solid color + border.
  - Pastikan icon tetap punya kontras cukup.

- [ ] C7. Rapikan CSS tema
  - Tambah selector khusus AI Agent untuk `box-shadow: none !important` bila perlu.
  - Tambah selector textarea monospace dengan spesifisitas cukup.
  - Hindari override global baru yang terlalu agresif.

---

## D. Verifikasi

- [ ] D1. Jalankan pencarian ulang class terlarang
  - `rg -n "shadow|bg-gradient|from-|via-|to-|box-shadow" resources/views/filament/pages/ai-agent.blade.php resources/css/filament/admin/theme.css app/Filament/Pages/AiAgent.php`
  - Pastikan hasil yang tersisa bukan UI aktif AI Agent atau memang override `box-shadow: none`.

- [ ] D2. Verifikasi font textarea
  - Inspect textarea editable dan readonly di browser.
  - Pastikan computed `font-family` adalah monospace.

- [ ] D3. Verifikasi light/dark mode
  - Border tetap terlihat.
  - Text kontras.
  - Focus state jelas.
  - Tidak ada shadow/gradient visual.

- [ ] D4. Jalankan build asset sesuai workflow proyek
  - Gunakan perintah npm/Vite yang tersedia di `src/package.json`.
  - Jangan edit manual file CSS hasil build kecuali repo memang mensyaratkan commit artifact.

---

## E. Definition of Done

- [ ] Semua acceptance criteria pada proposal terpenuhi.
- [ ] Tidak ada perubahan logic/backend AI Agent.
- [ ] Perubahan CSS terscope ke halaman AI Agent atau komponen yang memang dimaksud.
- [ ] Dokumentasi plan ini diperbarui dengan catatan implementasi aktual.

