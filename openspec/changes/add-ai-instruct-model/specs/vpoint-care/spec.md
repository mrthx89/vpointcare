# Spec Delta: Model Instruct untuk VPoint Assistant dan Inbox WhatsApp

## ADDED Requirements

### Requirement: Pengaturan AI menyediakan Model Instruct

Sistem SHALL menyediakan field konfigurasi `ModelInstructAi` pada pengaturan AI default untuk menyimpan nama model khusus VPoint Assistant.

#### Scenario: Admin menyimpan Model Instruct

- **GIVEN** admin memiliki akses manage AI Agent
- **WHEN** admin mengisi `Model Instruct` dan menyimpan pengaturan
- **THEN** sistem menyimpan nilai tersebut pada `MPengaturanAi.ModelInstructAi`
- **AND** nilai `ModelAi` existing tetap tersimpan sebagai Model Utama

#### Scenario: Model Instruct kosong

- **GIVEN** admin mengosongkan field `Model Instruct`
- **WHEN** pengaturan disimpan
- **THEN** sistem menerima nilai kosong/null
- **AND** runtime VPoint Assistant melakukan fallback ke Model Utama

### Requirement: VPoint Assistant memakai Model Instruct bila tersedia

Sistem SHALL memakai `ModelInstructAi` sebagai model efektif untuk request VPoint Assistant bila field tersebut terisi.

#### Scenario: VPoint Assistant dengan Model Instruct terisi

- **GIVEN** `ModelInstructAi` berisi `fast-instruct-model`
- **AND** `ModelAi` berisi `primary-model`
- **WHEN** user mengirim pesan melalui VPoint Assistant
- **THEN** request provider memakai `fast-instruct-model`
- **AND** suggested replies tetap diproses seperti sebelumnya

#### Scenario: VPoint Assistant fallback ke Model Utama

- **GIVEN** `ModelInstructAi` kosong
- **AND** `ModelAi` berisi `primary-model`
- **WHEN** user mengirim pesan melalui VPoint Assistant
- **THEN** request provider memakai `primary-model`


### Requirement: Opsi jawaban ringan WhatsApp memakai Model Instruct

Sistem SHALL memakai `ModelInstructAi` untuk membuat opsi jawaban ringan ke WhatsApp di VPoint Assistant maupun Inbox WhatsApp bila hasilnya berupa saran/draft yang direview user sebelum dikirim.

#### Scenario: Opsi jawaban VPoint Assistant memakai Model Instruct

- **GIVEN** `ModelInstructAi` berisi `fast-instruct-model`
- **WHEN** VPoint Assistant membuat `suggested_replies`
- **THEN** request provider memakai `fast-instruct-model`
- **AND** opsi hanya tampil sebagai saran untuk user internal

#### Scenario: Opsi jawaban Inbox WhatsApp memakai Model Instruct

- **GIVEN** `ModelInstructAi` berisi `fast-instruct-model`
- **AND** user meminta opsi jawaban ringan dari Inbox WhatsApp
- **WHEN** sistem membuat rekomendasi balasan WhatsApp
- **THEN** request provider memakai `fast-instruct-model`
- **AND** hasilnya masuk sebagai draft/saran yang perlu direview user sebelum dikirim ke WAHA

#### Scenario: Opsi jawaban ringan fallback ke Model Utama

- **GIVEN** `ModelInstructAi` kosong
- **AND** `ModelAi` berisi `primary-model`
- **WHEN** sistem membuat opsi jawaban ringan
- **THEN** request provider memakai `primary-model`

### Requirement: Jawaban pertama Inbox WhatsApp memakai Model Instruct

Sistem SHALL memakai `ModelInstructAi` untuk jawaban pertama pada sesi Inbox WhatsApp agar pembuka/routing ringan lebih hemat token, lalu memakai `ModelAi` untuk balasan sesi berikutnya.

#### Scenario: Sesi Inbox WhatsApp baru memakai Model Instruct

- **GIVEN** chat Inbox WhatsApp baru belum memiliki balasan AI/agent sebelumnya
- **AND** `ModelInstructAi` berisi `fast-instruct-model`
- **WHEN** sistem membuat jawaban pertama untuk sesi tersebut
- **THEN** request provider memakai `fast-instruct-model`
- **AND** jawaban hanya digunakan untuk pembuka, sapaan, routing, atau draft ringan

#### Scenario: Sesi Inbox WhatsApp lanjutan memakai Model Utama

- **GIVEN** chat Inbox WhatsApp sudah memiliki balasan AI/agent sebelumnya
- **AND** `ModelAi` berisi `primary-model`
- **WHEN** sistem membuat jawaban berikutnya untuk sesi tersebut
- **THEN** request provider memakai `primary-model`

#### Scenario: Model Instruct kosong pada sesi pertama

- **GIVEN** chat Inbox WhatsApp baru belum memiliki balasan AI/agent sebelumnya
- **AND** `ModelInstructAi` kosong
- **AND** `ModelAi` berisi `primary-model`
- **WHEN** sistem membuat jawaban pertama untuk sesi tersebut
- **THEN** request provider memakai `primary-model`

### Requirement: Auto-reply tetap memakai Model Utama

Sistem SHALL mempertahankan `ModelAi` sebagai model untuk auto-reply customer-facing dan test koneksi AI.

#### Scenario: Auto-reply tidak memakai Model Instruct

- **GIVEN** `ModelInstructAi` berisi `fast-instruct-model`
- **AND** `ModelAi` berisi `primary-model`
- **WHEN** auto-reply memproses pesan customer
- **THEN** request provider memakai `primary-model`
- **AND** sistem tidak memakai `fast-instruct-model` untuk auto-reply

#### Scenario: Test koneksi AI memakai Model Utama

- **GIVEN** admin menekan test koneksi AI
- **WHEN** sistem mengirim prompt test ke provider
- **THEN** request provider memakai `ModelAi`
- **AND** test tidak mengirim pesan ke WAHA/customer

## MODIFIED Requirements

### Requirement: Pengaturan provider AI membedakan fungsi model

Sistem SHALL menampilkan label yang membedakan Model Utama untuk auto-reply/customer-facing dan Model Instruct untuk VPoint Assistant dan Inbox WhatsApp/internal suggestion.

#### Scenario: Admin melihat dua model

- **GIVEN** admin membuka halaman AI Agent
- **WHEN** section provider ditampilkan
- **THEN** admin melihat input `Model Utama`
- **AND** admin melihat input `Model Instruct`
- **AND** setiap input memiliki helper text yang menjelaskan penggunaannya

### Requirement: UI Model Instruct mendukung multilingual

Sistem SHALL menggunakan translation key untuk semua label dan helper text baru pada Pengaturan AI.

#### Scenario: Locale Bahasa Indonesia aktif

- **GIVEN** locale aplikasi adalah `id`
- **WHEN** admin membuka halaman AI Agent
- **THEN** label dan helper text Model Utama serta Model Instruct tampil dalam Bahasa Indonesia

#### Scenario: Locale English aktif

- **GIVEN** locale aplikasi adalah `en`
- **WHEN** admin membuka halaman AI Agent
- **THEN** label dan helper text Primary Model serta Instruct Model tampil dalam English

#### Scenario: Key translation konsisten

- **GIVEN** key translation baru ditambahkan
- **WHEN** sistem membaca `src/resources/lang/id/ui.php` dan `src/resources/lang/en/ui.php`
- **THEN** kedua file memiliki key yang sama untuk label/help text Model Utama dan Model Instruct





