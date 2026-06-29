# Spec Delta: VPoint Care AI Agent UI/UX

## ADDED Requirements

### Requirement: Consistent AI Agent Component System

Halaman AI Agent SHALL memakai component style map yang seragam untuk breadcrumb, hero, card title, card body, form field, button, badge, dan preview.

#### Scenario: Breadcrumb konsisten

- **GIVEN** breadcrumb ditampilkan pada halaman AI Agent
- **WHEN** pengguna melihat navigasi halaman
- **THEN** breadcrumb SHALL memakai warna muted, ukuran kecil, separator konsisten, dan hover state ringan
- **AND** SHALL NOT memakai shadow atau gradient

#### Scenario: Hero dan card memakai sistem yang sama

- **GIVEN** hero/header dan card konfigurasi ditampilkan
- **WHEN** halaman dirender
- **THEN** hero/header dan card SHALL memakai background solid, border outline, radius konsisten, dan spacing yang seirama
- **AND** card title SHALL memakai ukuran, weight, dan margin yang seragam
- **AND** card body SHALL memakai spacing dan helper text yang seragam

#### Scenario: Action dan badge seragam

- **GIVEN** tombol, badge, atau icon provider ditampilkan
- **WHEN** pengguna berinteraksi dengan elemen tersebut
- **THEN** elemen SHALL memakai style solid atau outline yang konsisten
- **AND** SHALL menunjukkan hover/focus lewat border/background solid
- **AND** SHALL NOT memakai shadow atau gradient

### Requirement: AI Agent Light Outline Theme

Halaman AI Agent SHALL menggunakan visual ringan berbasis outline, background solid, dan radius konsisten.

#### Scenario: Card AI Agent tampil ringan

- **GIVEN** pengguna membuka halaman AI Agent
- **WHEN** card, panel, atau section konfigurasi ditampilkan
- **THEN** elemen tersebut SHALL memakai border outline yang jelas
- **AND** SHALL memakai background solid
- **AND** SHALL memakai radius yang konsisten antar card sejenis
- **AND** SHALL NOT memakai shadow atau gradient

#### Scenario: Hover dan focus tanpa shadow

- **GIVEN** pengguna mengarahkan cursor atau fokus keyboard pada kontrol AI Agent
- **WHEN** state hover/focus aktif
- **THEN** UI SHALL menunjukkan state lewat border, ring, atau background solid
- **AND** SHALL NOT menampilkan drop shadow
- **AND** SHALL NOT menampilkan gradient

### Requirement: Monospace Text Areas for Technical Text

Textarea editable dan readonly pada halaman AI Agent SHALL memakai font monospace untuk menjaga presisi teks prompt/template.

#### Scenario: Editable textarea memakai monospace

- **GIVEN** pengguna mengedit prompt, template, instruksi, atau teks konfigurasi teknis
- **WHEN** textarea ditampilkan
- **THEN** textarea SHALL memakai font monospace
- **AND** SHALL memiliki line-height yang nyaman untuk teks multiline
- **AND** SHALL tetap mempertahankan label dan helper text dengan font UI normal

#### Scenario: Readonly textarea atau preview memakai monospace

- **GIVEN** pengguna melihat teks prompt/template dalam mode readonly atau preview
- **WHEN** area teks ditampilkan
- **THEN** area teks SHALL memakai font monospace yang sama dengan textarea editable
- **AND** SHALL terlihat readonly melalui background/border solid, bukan shadow

### Requirement: No Active Gradients or Shadows in AI Agent UI

UI AI Agent SHALL bebas dari shadow dan gradient aktif.

#### Scenario: Audit class visual selesai

- **GIVEN** source AI Agent sudah diaudit
- **WHEN** pencarian class visual dilakukan pada Blade, CSS tema, dan metadata page
- **THEN** tidak boleh ada `shadow-*`, `bg-gradient-*`, `from-*`, `via-*`, atau `to-*` yang aktif untuk UI AI Agent
- **AND** `box-shadow` yang tersisa hanya boleh berupa override eksplisit `none`

#### Scenario: Provider icon tanpa gradient

- **GIVEN** provider AI memiliki icon atau badge visual
- **WHEN** provider ditampilkan di AI Agent
- **THEN** icon/badge SHALL memakai warna solid atau outline
- **AND** SHALL NOT memakai gradient background

