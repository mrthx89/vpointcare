# PLAN Audit UI/UX AI Agent: Light Outline, Monospace, No Shadow/Gradient

**Tanggal:** 2026-06-29  
**Status:** Draft audit plan  
**OpenSpec:** `openspec/changes/audit-ai-agent-light-outline-ui/`  
**Target utama:** UI AI Agent ringan, konsisten, rapi, dan presisi.

---

## 1. Tujuan

Mengaudit dan menyiapkan implementasi UI/UX halaman AI Agent agar:

- Theme terasa ringan dan bersih.
- Semua card memakai outline border dan rounded yang konsisten.
- Textarea editable memakai font monospace.
- Textarea readonly/preview juga memakai font monospace.
- Tidak ada shadow dan gradient aktif.
- Estetika tetap baik melalui spacing, border, warna solid, dan hierarchy visual.
- Breadcrumb, hero, card title, card body, form field, button, badge, dan preview memiliki style yang seragam.

---

## 2. File Audit

| File | Fokus Audit |
|---|---|
| `src/resources/views/filament/pages/ai-agent.blade.php` | Struktur card, class Tailwind, textarea, readonly preview, tombol, layout |
| `src/resources/css/filament/admin/theme.css` | Override theme, font global, card outline, textarea style, shadow reset |
| `src/app/Filament/Pages/AiAgent.php` | Metadata provider/icon yang masih memakai gradient |
| `src/public/css/filament/filament/app.css` | Hanya output build; jangan diedit manual kecuali workflow proyek mengharuskan |

---

## 3. Temuan Awal

### 3.1 Gradient Provider

`src/app/Filament/Pages/AiAgent.php` masih memiliki class provider seperti:

- `bg-gradient-to-br from-blue-500 to-indigo-600 text-white`
- `bg-gradient-to-br from-slate-900 to-sky-600 ...`
- `bg-gradient-to-br from-orange-500 to-amber-400 text-white`

Ini perlu diganti menjadi style solid/outline agar sesuai target tanpa gradient.

### 3.2 Font Global Menggunakan `!important`

`src/resources/css/filament/admin/theme.css` punya aturan global `* { font-family: ... !important; }`. Karena itu, textarea monospace perlu selector dengan spesifisitas cukup dan `!important` agar tidak tertimpa.

### 3.3 Theme Sudah Mengarah ke No Shadow

Theme CSS sudah memiliki banyak `box-shadow: none !important`. Audit tetap diperlukan untuk memastikan tidak ada class `shadow-*` aktif pada halaman AI Agent dan tidak ada shadow bawaan Filament yang muncul.

---

## 4. Prinsip Desain

### 4.0 Component Style Map

| Komponen | Style Seragam |
|---|---|
| Breadcrumb | Text kecil, muted, separator konsisten, hover ringan, tanpa shadow/gradient |
| Hero/Header | Outline card, background solid, radius card utama, padding luas tapi ringan |
| Card Title | Ukuran dan weight seragam, warna heading konsisten, margin bawah tetap |
| Card Body | Spacing vertikal konsisten, helper text muted, tanpa efek dekoratif berat |
| Field Group | Label, control, helper, dan error message memakai jarak konsisten |
| Input/Textarea | Border outline, radius input, focus border/ring tanpa shadow |
| Button | Solid/outline, radius konsisten, hover background solid, tanpa shadow/gradient |
| Badge/Icon | Warna solid lembut, border aksen bila perlu, tanpa gradient |
| Divider | Border tipis atau spacing, bukan shadow |

### 4.1 Light Outline Card

- Gunakan background solid: putih/surface di light mode, slate/zinc gelap di dark mode.
- Gunakan border halus sebagai pemisah utama.
- Hindari drop shadow, inner shadow, glow, dan gradient.
- Gunakan spacing cukup agar card tetap terasa premium tanpa efek berat.

### 4.2 Rounded Konsisten

Standar yang disarankan:

| Komponen | Radius |
|---|---|
| Card utama | `rounded-2xl` atau token CSS setara |
| Nested card/panel | `rounded-xl` atau ikut card utama bila visual lebih rapi |
| Input/textarea/select | `rounded-xl` |
| Badge/button kecil | `rounded-lg` atau `rounded-full` hanya jika pill |

Pilih satu pola lalu konsisten di seluruh halaman.

### 4.3 Monospace untuk Teks Teknis

Target monospace:

- Prompt sistem.
- Template pesan.
- Textarea instruksi/model behavior.
- Textarea readonly.
- Preview prompt/template yang tampil sebagai blok teks.

Bukan target monospace:

- Heading.
- Label form.
- Helper text.
- Tombol.
- Navigasi/tab.

### 4.4 No Shadow dan No Gradient

Dilarang untuk area AI Agent:

- `shadow-sm`, `shadow`, `shadow-md`, `shadow-lg`, dan variasinya.
- `box-shadow` selain nilai `none` pada override.
- `bg-gradient-to-*`.
- `from-*`, `via-*`, `to-*` yang dipakai untuk gradient.
- Glow/ring visual yang menyerupai shadow berat.

Pengganti:

- Border aksen.
- Background solid lembut.
- Kontras teks.
- State hover via `border-color` dan `background-color`.

---

## 5. Rencana Eksekusi

### Fase A — Audit Class Aktual

- [ ] Cari semua card/panel di `ai-agent.blade.php`.
- [ ] Catat semua class `rounded-*`, `border-*`, `bg-*`, `shadow-*`.
- [ ] Cari semua textarea dan tentukan editable vs readonly.
- [ ] Cari semua gradient/shadow di Blade, CSS, dan metadata PHP.

Perintah bantu:

```bash
rg -n "shadow|bg-gradient|from-|via-|to-|rounded|textarea|font-|monospace" src/resources/views/filament/pages/ai-agent.blade.php src/resources/css/filament/admin/theme.css src/app/Filament/Pages/AiAgent.php
```

### Fase B — Standarisasi UI

- [ ] Tentukan wrapper khusus halaman, misalnya `.wacs-ai-agent`.
- [ ] Tentukan style breadcrumb yang konsisten.
- [ ] Tentukan style hero/header yang satu keluarga dengan card.
- [ ] Tentukan style card title dan card body.
- [ ] Tentukan style field group, action row, button, badge, dan icon.
- [ ] Tentukan class standar card outline.
- [ ] Tentukan class standar textarea monospace.
- [ ] Tentukan class provider/icon solid tanpa gradient.

### Fase C — Implementasi UI

- [ ] Update breadcrumb agar ringan dan seragam.
- [ ] Update hero/header menjadi outline rounded tanpa gradient/shadow.
- [ ] Update card title dan card body agar spacing dan tipografi seragam.
- [ ] Update card utama dan nested card menjadi outline rounded konsisten.
- [ ] Hapus semua shadow class pada halaman AI Agent.
- [ ] Hapus semua gradient class pada halaman AI Agent.
- [ ] Tambahkan class/selector monospace untuk textarea editable.
- [ ] Tambahkan class/selector monospace untuk readonly textarea/preview.
- [ ] Update provider/icon class di `AiAgent.php`.

### Fase D — Verifikasi Visual

- [ ] Buka halaman AI Agent di browser.
- [ ] Cek light mode.
- [ ] Cek dark mode.
- [ ] Cek hover/focus tanpa shadow.
- [ ] Cek computed font textarea adalah monospace.
- [ ] Cek tidak ada gradient aktif di provider/icon/card.

### Fase E — Verifikasi Teknis

- [ ] Jalankan `rg` ulang untuk class terlarang.
- [ ] Jalankan build asset sesuai script proyek.
- [ ] Pastikan tidak ada perubahan logic Livewire/Filament.
- [ ] Review diff agar perubahan tetap minimal.

---

## 6. Acceptance Checklist

- [ ] Breadcrumb ringan, muted, dan seragam.
- [ ] Hero/header memakai style satu keluarga dengan card.
- [ ] Card title dan card body seragam.
- [ ] Semua card AI Agent menggunakan outline border.
- [ ] Rounded card konsisten.
- [ ] Tidak ada shadow aktif.
- [ ] Tidak ada gradient aktif.
- [ ] Textarea editable memakai monospace.
- [ ] Textarea readonly/preview memakai monospace.
- [ ] Focus state tetap jelas.
- [ ] Light mode terbaca baik.
- [ ] Dark mode terbaca baik.
- [ ] Tidak ada perubahan logic backend.

---

## 7. Catatan Implementasi CSS

Contoh pendekatan selector aman:

```css
.wacs-ai-agent textarea,
.wacs-ai-agent .wacs-ai-agent-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
    line-height: 1.6;
}

.wacs-ai-agent .wacs-ai-agent-card {
    box-shadow: none !important;
    border: 1px solid var(--md-sys-color-outline-variant);
    background: var(--md-sys-color-surface);
    border-radius: var(--md-sys-shape-large);
}
```

Catatan: selector final harus mengikuti struktur Blade aktual, bukan dipaksakan jika class existing sudah lebih tepat.

---

## 8. Definition of Done

Audit dianggap selesai jika OpenSpec, plan, dan checklist implementasi sudah jelas. Implementasi dianggap selesai jika semua acceptance checklist terpenuhi dan hasil visual sesuai arahan: ringan, outline, rounded konsisten, monospace, tanpa shadow, tanpa gradient.

