# Proposal: Audit UI/UX AI Agent Light Outline Theme

**Status:** Draft  
**Tanggal:** 2026-06-29  
**Area:** Filament AI Agent UI/UX  
**Terkait File:** `resources/views/filament/pages/ai-agent.blade.php`, `resources/css/filament/admin/theme.css`, `app/Filament/Pages/AiAgent.php`

---

## Ringkasan

Audit dan perapihan UI/UX halaman AI Agent agar tampil ringan, konsisten, presisi, dan bebas efek visual berat.

Target utama:

1. **Light outline theme** — seluruh card memakai background solid, border outline halus, radius konsisten, tanpa shadow.
2. **Konsistensi rounded card** — radius card, panel, input, textarea, dan tombol mengikuti token yang sama.
3. **Monospace untuk editor teks** — textarea editable dan readonly memakai font monospace agar prompt, template, dan konfigurasi teks rapi serta presisi.
4. **Tanpa shadow dan gradient** — hapus `shadow-*`, `box-shadow`, `bg-gradient-*`, `from-*`, `via-*`, dan `to-*` dari area AI Agent.
5. **Estetika minimal** — visual tetap indah melalui spacing, border, warna solid, hierarchy tipografi, dan state interaktif yang jelas.
6. **Komponen seragam** — breadcrumb, hero, card title, card body, form field, action button, badge, dan empty/help state memakai token style yang sama.

---

## Motivasi

Halaman AI Agent berisi pengaturan prompt, template pesan, model, dan teks operasional yang membutuhkan keterbacaan tinggi. Efek shadow dan gradient membuat tampilan terasa berat dan kurang konsisten dengan kebutuhan dashboard admin yang ringan.

Textarea yang berisi prompt/template juga lebih mudah diaudit jika memakai monospace, karena indentasi, placeholder, token, dan struktur teks menjadi lebih presisi.

---

## Scope

### Masuk Scope

- Audit UI pada `resources/views/filament/pages/ai-agent.blade.php`.
- Audit styling global/override pada `resources/css/filament/admin/theme.css` yang memengaruhi halaman AI Agent.
- Audit class provider di `app/Filament/Pages/AiAgent.php` yang masih menghasilkan gradient icon/provider card.
- Standarisasi class atau CSS utility untuk card outline ringan.
- Standarisasi font monospace untuk textarea editable dan readonly di halaman AI Agent.
- Dokumentasi acceptance criteria sebelum implementasi.

### Di Luar Scope

- Perubahan logic AI, provider, model, database, atau validasi backend.
- Redesign seluruh Filament admin di luar dampak langsung halaman AI Agent.
- Build ulang design system besar-besaran.
- Perubahan konten prompt atau template bisnis.

---

## Temuan Awal

| Area | Temuan | Dampak |
|---|---|---|
| `AiAgent.php` provider metadata | Masih ada `bg-gradient-to-br from-* to-*` untuk icon provider | Melanggar target tanpa gradient |
| `ai-agent.blade.php` | Perlu audit menyeluruh class `shadow-*`, `rounded-*`, card background, dan textarea | Risiko visual tidak konsisten |
| `theme.css` | Sudah banyak override `box-shadow: none`, tetapi perlu selector khusus AI Agent agar tidak tergantung class tersebar | Konsistensi lebih mudah dijaga |
| Textarea prompt/template | Perlu font monospace eksplisit untuk editable dan readonly | Prompt/template lebih presisi dibaca |

---

## Sistem Komponen Target

| Komponen | Standar Visual |
|---|---|
| Breadcrumb | Kecil, ringan, tanpa shadow, warna muted, separator konsisten, hover solid/underline halus |
| Hero/Header | Background solid surface, border outline, radius sama dengan card utama, tanpa gradient |
| Card | Background solid, border outline, radius konsisten, padding seragam, tanpa shadow |
| Card Title | Font heading konsisten, ukuran dan weight seragam, margin bawah tetap |
| Card Body | Font UI normal, spacing antar field konsisten, text muted untuk helper |
| Textarea/Input | Border outline, radius input konsisten, focus border/ring tanpa shadow |
| Editable Textarea | Monospace, line-height nyaman, resize/height konsisten |
| Readonly Textarea/Preview | Monospace, background solid muted, border outline, tanpa shadow |
| Button | Solid atau outline, radius konsisten, tanpa gradient/shadow |
| Badge/Icon | Warna solid lembut, border aksen opsional, tanpa gradient |
| Section Divider | Border tipis atau spacing, bukan shadow |

---

## Keputusan Desain

1. **Gunakan border, bukan shadow:** kedalaman visual dibuat dari `border`, `background`, dan spacing, bukan bayangan.
2. **Gunakan solid color, bukan gradient:** status/provider memakai warna solid lembut dan border aksen.
3. **Radius tunggal:** card utama, hero, dan panel memakai radius besar konsisten; input/textarea memakai radius sedikit lebih kecil namun tetap selaras.
4. **Monospace hanya untuk area teknis:** textarea, prompt preview, readonly template, dan code-like content memakai monospace; label dan heading tetap mengikuti font UI.
5. **Scope CSS aman:** tambah class wrapper khusus halaman AI Agent bila perlu, agar override tidak merusak halaman lain.

---

## Risiko dan Mitigasi

| Risiko | Mitigasi |
|---|---|
| Override CSS terlalu luas mengubah halaman lain | Gunakan wrapper/selector khusus AI Agent |
| Menghapus gradient membuat icon/provider kurang menonjol | Ganti dengan warna solid, border aksen, dan kontras teks |
| Monospace global membuat label terlihat kaku | Terapkan hanya ke textarea/readonly code-like content |
| Radius tidak konsisten karena class Tailwind tersebar | Buat token/class standar dan audit semua instance |
| CSS build belum dilakukan setelah perubahan | Masukkan langkah build/verifikasi asset ke plan |

---

## Acceptance Criteria

- Breadcrumb, hero/header, card, card title, card body, form control, button, badge, dan preview memakai style yang seragam.
- Semua card/panel AI Agent memakai outline border, background solid, dan rounded konsisten.
- Tidak ada `shadow-*`, `box-shadow`, `bg-gradient-*`, `from-*`, `via-*`, atau `to-*` yang aktif pada UI AI Agent.
- Textarea editable memakai font monospace, line-height nyaman, dan tabular spacing terasa presisi.
- Textarea readonly atau preview teks juga memakai font monospace.
- Tampilan light dan dark mode tetap terbaca dengan kontras border yang cukup.
- Hover/focus state tetap jelas tanpa shadow dan tanpa gradient.
- Perubahan tidak mengubah perilaku Livewire/Filament atau data yang disimpan.

---

## File yang Direncanakan

| File | Rencana |
|---|---|
| `resources/views/filament/pages/ai-agent.blade.php` | Audit dan rapikan class card, input, textarea, readonly preview, wrapper halaman |
| `resources/css/filament/admin/theme.css` | Tambah/rapikan selector khusus AI Agent untuk outline card, monospace textarea, no-shadow/no-gradient |
| `app/Filament/Pages/AiAgent.php` | Ganti metadata icon/provider dari gradient menjadi solid outline style bila masih dipakai view |
| `public/css/filament/filament/app.css` | Tidak diedit manual; hanya hasil build jika workflow repo memang commit asset publik |

---

## Catatan Implementasi

Implementasi sebaiknya dilakukan setelah audit class aktual pada Blade selesai. Hindari mengedit `public/css/filament/filament/app.css` secara manual karena file tersebut terlihat seperti hasil build/minified asset.

