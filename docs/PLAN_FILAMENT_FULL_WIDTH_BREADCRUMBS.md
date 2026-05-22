# Plan Refaktur UI Full Width dan Breadcrumbs Filament

## Tujuan

Membuat halaman admin Filament memakai lebar layar penuh, bukan dibatasi max-width default, dan memperbaiki tampilan breadcrumbs agar user jelas sedang berada di modul/menu mana.

Target utama:

- Konten halaman admin melebar mengikuti layar desktop.
- Mobile tetap nyaman dan tidak overflow.
- Breadcrumbs tetap memakai data bawaan Filament agar resource/page tidak perlu di-hardcode satu per satu.
- Breadcrumbs diberi visual hierarchy yang lebih jelas, termasuk icon/indicator untuk posisi halaman.
- Tetap mempertahankan multilanguage dari title/navigation label yang sudah ada.

## Temuan Saat Ini

- `AdminPanelProvider` belum mengatur `maxContentWidth`.
- Filament default memakai `Width::SevenExtraLarge`, sehingga `<main class="fi-main fi-width-7xl">` membatasi lebar konten.
- Theme utama ada di:
  - `resources/css/filament/admin/theme.css`
- Panel sudah memakai:
  - `->viteTheme('resources/css/filament/admin/theme.css')`
- Breadcrumbs sudah aktif dari Filament:
  - `filament()->hasBreadcrumbs()`
  - render di header bawaan melalui `<x-filament::breadcrumbs />`
- Breadcrumbs bisa diperindah lewat CSS terlebih dahulu tanpa mengubah setiap Page/Resource.

## Prinsip Desain

- Full width bukan berarti konten menempel ke tepi layar. Tetap perlu padding yang konsisten.
- Dashboard dan datatable harus punya ruang horizontal lebih lega.
- Breadcrumbs dibuat ringkas, jelas, dan tidak mengganggu action button di kanan header.
- Breadcrumbs jangan dibuat seperti card besar; cukup sebagai navigasi kecil yang terlihat profesional.
- Warna tetap mengikuti tema Filament/VPoint: dominan putih/slate dengan aksen biru.
- Dark mode harus tetap rapi.

## Rencana Implementasi

### 1. Aktifkan Full Width Panel

File:

- `app/Providers/Filament/AdminPanelProvider.php`

Perubahan:

- Tambahkan import:
  - `Filament\Support\Enums\Width`
- Tambahkan pada konfigurasi panel:
  - `->maxContentWidth(Width::Full)`

Efek:

- Semua halaman admin menggunakan `fi-width-full`.
- Dashboard, datatable, dan form resource mendapat ruang layar lebih luas.

Catatan:

- Simple page seperti login/register jangan ikut dibuat terlalu lebar.
- Jika perlu, biarkan simple page memakai default atau set eksplisit:
  - `->simplePageMaxContentWidth(Width::Large)`

### 2. Rapikan Container dan Spacing Global

File:

- `resources/css/filament/admin/theme.css`

CSS yang akan ditambahkan:

- `.fi-main` untuk memastikan full width tetap punya padding kanan/kiri yang nyaman.
- `.fi-page` dan `.fi-page-main` untuk mengurangi rasa sempit di halaman resource.
- Penyesuaian responsive:
  - mobile: padding kecil.
  - desktop: padding lebih lega.
  - wide desktop: tetap full width, tetapi spacing antar section tidak berantakan.

Rencana CSS:

```css
.fi-main.fi-width-full {
    width: 100%;
    max-width: none;
}

.fi-main {
    padding-inline: clamp(1rem, 1.5vw, 2rem);
}
```

Catatan:

- Detail selector akan disesuaikan dengan class Filament aktual agar tidak memecah layout topbar/sidebar.
- Tidak override terlalu agresif pada auth page.

### 3. Breadcrumb UI Enhancement

File:

- `resources/css/filament/admin/theme.css`

Pendekatan awal:

- Tetap gunakan `<x-filament::breadcrumbs />` bawaan.
- Styling class bawaan:
  - `.fi-breadcrumbs`
  - `.fi-breadcrumbs-list`
  - `.fi-breadcrumbs-item`
  - `.fi-breadcrumbs-item-label`
  - `.fi-breadcrumbs-item-separator`

Visual yang akan dibuat:

- Breadcrumbs tampil seperti compact path bar.
- Item terakhir lebih tegas sebagai lokasi aktif.
- Separator dibuat lebih halus.
- Item pertama diberi home/location indicator dengan pseudo-element atau icon-style marker.
- Hover state untuk breadcrumb link.
- Dark mode support.

Contoh arah UI:

```text
Home / Master Data / Pengaturan Hak Akses
```

Dengan tampilan:

- `Home` memakai icon kecil.
- Intermediate item warna slate.
- Current item warna biru/tebal.

### 4. Breadcrumb Label Multilanguage

File yang akan diaudit:

- `app/Filament/Pages/*.php`
- `app/Filament/Resources/**/*.php`
- `resources/lang/id/ui.php`
- `resources/lang/en/ui.php`

Yang dicek:

- `getNavigationLabel()`
- `getTitle()`
- `getBreadcrumb()` jika ada.
- `getModelLabel()` / `getPluralModelLabel()` untuk Resource.

Target:

- Breadcrumb tetap menampilkan label sesuai bahasa aktif.
- Tidak ada string hardcoded baru.
- Page custom seperti:
  - Dashboard
  - Inbox WhatsApp
  - AI Agent
  - Master Customer
  - Log Data
  - Ticketing
  tetap punya title/breadcrumb yang jelas.

Jika dibutuhkan, tambahkan override ringan:

```php
public function getBreadcrumb(): ?string
{
    return static::getNavigationLabel();
}
```

### 5. Breadcrumb untuk Page Non-Resource

Filament resource otomatis punya breadcrumb yang lebih baik daripada custom page.

Untuk custom page, rencana mapping:

- Dashboard:
  - Breadcrumb dapat kosong atau `Dashboard`.
  - Karena Dashboard root, jangan dibuat path panjang.
- Inbox WhatsApp:
  - `Operasional / Inbox WhatsApp`
- Ticketing:
  - `Operasional / Ticketing`
- AI Agent:
  - `Asisten / AI Agent`
- Master Customer:
  - `Master Data / Master Customer`
- Log Data:
  - `Monitoring / Log Data`

Implementasi:

- Utamakan helper dari `NavigationHelper` agar group label mengikuti `MHakAkses` dan multilanguage.
- Jika `getBreadcrumbs()` perlu dioverride, buat trait/helper kecil agar tidak copy-paste di semua page.

Calon helper:

- `app/Support/FilamentBreadcrumbs.php`

Fungsi:

- `forMenu(string $menuCode, string $fallbackLabel): array`
- Menghasilkan array breadcrumbs sesuai standar Filament.

### 6. Optional: Component Override Jika CSS Tidak Cukup

Jika styling CSS bawaan tidak cukup untuk icon per item, opsi kedua:

- Override component breadcrumbs Filament via view publish/override.
- Atau buat render hook sebelum heading dengan custom breadcrumb component.

Namun ini tahap kedua, bukan langkah pertama.

Alasan:

- Override vendor component lebih rawan drift saat update Filament.
- CSS terhadap component bawaan lebih aman untuk perubahan awal.

### 7. Page dan Resource Audit

Halaman yang wajib dicek setelah perubahan:

- `/admin`
- `/admin/inbox-whatsapp`
- `/admin/ai-agent`
- `/admin/master-customer`
- `/admin/log-data`
- `/admin/ticketing`
- `/admin/master/hak-akses`
- `/admin/master/penggunas`
- `/admin/master/instansis`
- `/admin/settings/job-schedules`

Yang dicek:

- Konten melebar full width.
- Tidak ada table/form yang terlalu mepet tepi layar.
- Header action tetap sejajar.
- Breadcrumb tidak bertabrakan dengan tombol action.
- Bahasa ID/EN tetap benar.
- Dark mode tetap terbaca.

## Rencana Verifikasi

Command:

```powershell
php -l app/Providers/Filament/AdminPanelProvider.php
php artisan optimize:clear
npm run build
php artisan route:list --path=admin
```

Verifikasi browser:

- Desktop 1366px.
- Desktop wide 1920px.
- Mobile responsive.

Jika Playwright tersedia, ambil screenshot:

- `/admin/master/hak-akses`
- `/admin/inbox-whatsapp`
- `/admin`

Acceptance criteria:

- `.fi-main` tidak lagi terbatas `7xl`.
- Breadcrumb jelas menunjukkan lokasi halaman.
- Breadcrumb tidak hardcoded satu bahasa.
- Table besar seperti Hak Akses/User lebih lega.
- Tidak ada horizontal scroll global yang tidak perlu.

## Risiko dan Mitigasi

- Risiko: full width membuat dashboard terlalu kosong di monitor besar.
  - Mitigasi: grid dashboard tetap punya responsive columns, bukan elemen stretched tanpa kontrol.
- Risiko: CSS breadcrumb terlalu spesifik dan pecah saat Filament update.
  - Mitigasi: gunakan class bawaan Filament yang stabil dan minim override.
- Risiko: custom page breadcrumb tidak otomatis.
  - Mitigasi: buat helper/trait kecil jika perlu, bukan hardcode di setiap page.
- Risiko: auth page ikut melebar.
  - Mitigasi: atur `simplePageMaxContentWidth` tetap compact.

## Urutan Eksekusi

1. Set panel `maxContentWidth(Width::Full)`.
2. Tambahkan CSS full-width safe spacing.
3. Tambahkan CSS breadcrumb UI.
4. Audit custom page breadcrumb.
5. Tambahkan helper breadcrumb jika custom page belum cukup jelas.
6. Jalankan build dan cache clear.
7. Verifikasi desktop/mobile dan dark mode.
