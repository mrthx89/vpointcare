# Spec Delta: Nama Pembuat Catatan Internal Chat

## ADDED Requirements

### Requirement: Atribusi Pembuat Catatan Internal

Sistem SHALL menampilkan nama pengguna pembuat setiap catatan internal chat berdasarkan `TChatDCatatanInternal.DibuatOleh` yang di-resolve ke `MPengguna.NamaPengguna`.

#### Scenario: Agent membuat catatan internal baru

- **GIVEN** agent memiliki permission `inbox.manage` dan sedang membuka sebuah chat
- **WHEN** agent menyimpan catatan internal
- **THEN** sistem menyimpan `DibuatOleh` berisi `MPengguna.Id` agent tersebut
- **AND** daftar catatan internal menampilkan `NamaPengguna` agent tersebut sebagai pembuat
- **AND** nama tetap tampil setelah halaman dimuat ulang

#### Scenario: Catatan internal lama ditampilkan

- **GIVEN** terdapat catatan internal yang tersimpan sebelum perbaikan ini dengan `DibuatOleh` terisi valid
- **WHEN** pengguna membuka chat tersebut di Inbox WhatsApp
- **THEN** sistem menampilkan nama pengguna pembuat catatan tersebut
- **AND** sistem tidak memerlukan migrasi atau perbaikan data untuk mencapainya

#### Scenario: Konsistensi antara Inbox dan Detail Sesi Chat

- **GIVEN** sebuah chat memiliki catatan internal dengan pembuat yang diketahui
- **WHEN** pengguna membuka chat tersebut di Inbox WhatsApp dan di halaman Detail Sesi Chat
- **THEN** nama pembuat yang ditampilkan pada kedua halaman identik

#### Scenario: Pembuat catatan tidak diketahui

- **GIVEN** sebuah catatan internal memiliki `DibuatOleh` NULL, atau merujuk `MPengguna.Id` yang sudah tidak ada
- **WHEN** catatan tersebut ditampilkan
- **THEN** sistem menampilkan label `ui.common.system`
- **AND** sistem tidak melempar error dan tidak menghentikan pemuatan catatan lain

#### Scenario: Pembuat catatan sudah nonaktif

- **GIVEN** pembuat catatan adalah `MPengguna` dengan `NonAktif = 1`
- **WHEN** catatan tersebut ditampilkan
- **THEN** sistem tetap menampilkan `NamaPengguna` pengguna tersebut
- **AND** jejak historis catatan tidak berubah menjadi `ui.common.system`

#### Scenario: Beban query resolusi nama

- **GIVEN** sebuah chat memiliki N catatan internal dari beberapa pengguna berbeda
- **WHEN** daftar catatan internal dimuat
- **THEN** sistem melakukan tepat satu query ke `MPengguna` untuk seluruh catatan tersebut
- **AND** tidak melakukan satu query per baris catatan

### Requirement: Referensi Tabel Pengguna yang Benar

Sistem SHALL merujuk tabel `MPengguna` untuk seluruh lookup pengguna dan SHALL NOT merujuk tabel bernama `Pengguna` yang tidak ada pada schema WACS.

#### Scenario: Tidak ada referensi tabel yang tidak ada

- **WHEN** source code aplikasi ditelusuri untuk `DB::table('Pengguna')` dan `Schema::hasTable('Pengguna')`
- **THEN** tidak ditemukan satu pun kemunculan
- **AND** seluruh lookup pengguna memakai `MPengguna`

#### Scenario: Lookup pengguna tidak dijaga pemeriksaan keberadaan tabel

- **GIVEN** `MPengguna` adalah tabel inti yang selalu ada pada schema WACS
- **WHEN** sistem me-resolve nama pengguna
- **THEN** sistem melakukan query langsung tanpa `Schema::hasTable()`
- **AND** hasil `false` dari pemeriksaan tabel lama tidak lagi dapat menyembunyikan data yang valid
