# Spec Delta: VPoint Care Global Filament Admin UI/UX

## ADDED Requirements

### Requirement: Global Filament Admin Light Outline System

Seluruh halaman/menu Filament admin SHALL memakai visual ringan berbasis outline, background solid, radius konsisten, tanpa shadow aktif, dan tanpa gradient aktif.

#### Scenario: Semua container admin konsisten

- **GIVEN** pengguna membuka halaman/menu Filament mana pun
- **WHEN** header, breadcrumb, section, card, table, form, modal, dropdown, atau action group ditampilkan
- **THEN** elemen tersebut SHALL memakai background solid
- **AND** SHALL memakai border outline yang jelas
- **AND** SHALL memakai radius konsisten sesuai token global
- **AND** SHALL NOT memakai shadow aktif
- **AND** SHALL NOT memakai gradient aktif

#### Scenario: Hover dan focus global tanpa shadow

- **GIVEN** pengguna berinteraksi dengan tombol, input, tab, sidebar item, table row, badge, atau action
- **WHEN** state hover/focus/active aktif
- **THEN** UI SHALL menunjukkan state lewat border, outline, text color, atau background solid
- **AND** SHALL NOT memakai drop shadow, glow berat, atau gradient

### Requirement: Global Filament Admin Typography Scale

Seluruh halaman/menu Filament admin SHALL memakai skala font compact dan konsisten untuk breadcrumb, page title, hero title, section/card title, body, table, form label, helper text, badge, dan textarea.

#### Scenario: Page title dan hero title tidak terlalu besar

- **GIVEN** page header atau custom hero ditampilkan
- **WHEN** pengguna melihat judul halaman
- **THEN** title SHALL memakai ukuran compact `text-xl` atau maksimal `text-2xl`
- **AND** SHALL NOT memakai `text-3xl`, `text-4xl`, atau ukuran lebih besar untuk page title normal
- **AND** description SHALL memakai ukuran body `text-sm`

#### Scenario: Card dan section title compact

- **GIVEN** card, widget, section, atau panel konfigurasi ditampilkan
- **WHEN** pengguna melihat title komponen
- **THEN** title utama SHALL memakai ukuran `text-base`
- **AND** nested title SHALL memakai ukuran `text-sm`
- **AND** title SHALL NOT memakai `text-xl` atau ukuran lebih besar kecuali dashboard hero khusus yang diset maksimal `text-2xl`

#### Scenario: Body, table, dan helper text konsisten

- **GIVEN** isi card, table, form label, helper text, badge, dan textarea ditampilkan
- **WHEN** halaman dirender
- **THEN** body/form/table content SHALL memakai `text-sm`
- **AND** helper text, breadcrumb, badge, dan meta SHALL memakai `text-xs`
- **AND** textarea teknis SHALL memakai `text-sm` dengan line-height nyaman

### Requirement: Global Technical Textarea Monospace

Textarea dan area teks teknis di seluruh admin SHALL memakai font monospace agar prompt, template, konfigurasi, nomor, dan payload multiline presisi.

#### Scenario: Editable textarea teknis memakai monospace

- **GIVEN** pengguna mengedit prompt, template pesan, instruksi AI, nomor multiline, payload, atau teks konfigurasi teknis
- **WHEN** textarea ditampilkan
- **THEN** textarea SHALL memakai font monospace
- **AND** SHALL memakai `text-sm`
- **AND** SHALL memiliki line-height 1.55-1.65

#### Scenario: Readonly preview teknis memakai monospace

- **GIVEN** preview hasil test, prompt, template, payload, atau teks teknis readonly ditampilkan
- **WHEN** area teks ditampilkan
- **THEN** area teks SHALL memakai font monospace yang sama
- **AND** readonly state SHALL dibedakan lewat background solid muted dan border outline

### Requirement: Global Component Consistency

Breadcrumb, page header, hero, card title, card body, form field, button, badge, icon, table, tabs, modal, dropdown, and empty/help state SHALL memakai component style map global yang seragam.

#### Scenario: Breadcrumb global konsisten

- **GIVEN** breadcrumb ditampilkan pada halaman Filament mana pun
- **WHEN** pengguna melihat navigasi halaman
- **THEN** breadcrumb SHALL memakai warna muted, ukuran kecil, separator konsisten, border outline ringan, dan hover state solid
- **AND** SHALL NOT memakai shadow atau gradient

#### Scenario: Table dan form global konsisten

- **GIVEN** resource table atau form ditampilkan
- **WHEN** pengguna melihat table/form
- **THEN** table container, row, field wrapper, label, helper text, and action SHALL memakai spacing, border, radius, dan font size global
- **AND** SHALL NOT memakai shadow atau gradient aktif

#### Scenario: Modal dan dropdown global konsisten

- **GIVEN** modal, dropdown, select panel, atau action panel ditampilkan
- **WHEN** overlay terbuka
- **THEN** panel SHALL memakai background solid, border outline, radius global, dan no shadow aktif
- **AND** focus/hover item SHALL memakai background solid muted