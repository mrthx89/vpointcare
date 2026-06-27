## ADDED Requirements

### Requirement: Reviewed AI Knowledge Learning

Sistem SHALL menyediakan mekanisme membuat draft knowledge dari chat customer dan mewajibkan review manusia sebelum knowledge aktif dipakai AI Auto Reply.

#### Scenario: Membuat draft knowledge dari chat valid

- **GIVEN** user memiliki izin kelola knowledge
- **AND** chat memiliki riwayat pesan teks yang cukup
- **WHEN** user memilih aksi membuat draft knowledge dari chat
- **THEN** sistem SHALL membuat record draft knowledge dengan status `Draft`
- **AND** draft SHALL menyimpan referensi chat sumber
- **AND** draft SHALL tidak langsung aktif di `MPengetahuan`

#### Scenario: Chat tidak punya knowledge reusable

- **GIVEN** chat hanya berisi sapaan, ucapan terima kasih, atau percakapan tanpa informasi reusable
- **WHEN** user membuat draft knowledge
- **THEN** sistem SHALL menampilkan pesan bahwa chat tidak layak menjadi knowledge
- **AND** sistem SHALL tidak membuat knowledge aktif

### Requirement: Sensitive Data Sanitization

Sistem SHALL menyamarkan data sensitif dasar sebelum menyimpan cuplikan sumber draft knowledge.

#### Scenario: Chat mengandung email atau nomor telepon

- **GIVEN** chat sumber mengandung email atau nomor telepon customer
- **WHEN** draft knowledge dibuat
- **THEN** cuplikan sumber draft SHALL menyamarkan email sebagai `[email]`
- **AND** nomor telepon panjang SHALL disamarkan sebagai `[nomor]`

### Requirement: Knowledge Review Workflow

Sistem SHALL menyediakan workflow review draft knowledge dengan status draft, revisi, disetujui, ditolak, dan diarsipkan.

#### Scenario: Reviewer menyetujui draft

- **GIVEN** reviewer memiliki izin kelola knowledge
- **AND** draft knowledge berstatus `Draft` atau `PerluRevisi`
- **WHEN** reviewer menyetujui draft
- **THEN** sistem SHALL membuat atau memperbarui record `MPengetahuan`
- **AND** draft SHALL berubah status menjadi `Disetujui`
- **AND** draft SHALL menyimpan reviewer dan waktu review

#### Scenario: Reviewer menolak draft

- **GIVEN** reviewer memiliki izin kelola knowledge
- **WHEN** reviewer menolak draft
- **THEN** sistem SHALL mengubah status draft menjadi `Ditolak`
- **AND** sistem SHALL tidak membuat record `MPengetahuan`

### Requirement: Auto Reply Uses Approved Knowledge Only

AI Auto Reply SHALL only use active records from `MPengetahuan` and SHALL NOT use draft knowledge directly.

#### Scenario: Draft belum disetujui

- **GIVEN** ada draft knowledge yang belum disetujui
- **WHEN** AI Auto Reply membuat prompt jawaban
- **THEN** prompt SHALL NOT include draft knowledge tersebut

#### Scenario: Draft sudah disetujui

- **GIVEN** draft sudah disetujui menjadi `MPengetahuan` aktif
- **WHEN** AI Auto Reply membuat prompt untuk chat relevan
- **THEN** prompt MAY include knowledge tersebut berdasarkan retrieval relevansi

### Requirement: Lightweight Knowledge Retrieval

The system SHALL retrieve only a small, relevant subset of approved knowledge before calling the AI provider.

#### Scenario: Knowledge base contains many active records

- **GIVEN** `MPengetahuan` contains many active records
- **WHEN** AI Auto Reply builds a prompt
- **THEN** the system SHALL prefilter candidate knowledge locally
- **AND** SHALL include only top relevant knowledge items in the AI prompt
- **AND** SHALL enforce maximum knowledge count and maximum knowledge context length

#### Scenario: No knowledge reaches minimum relevance score

- **GIVEN** no approved knowledge is sufficiently relevant to the latest customer message
- **WHEN** AI Auto Reply builds a prompt
- **THEN** the system SHALL omit knowledge context
- **AND** SHALL instruct AI not to invent unavailable facts

### Requirement: Prompt Budget Control

The system SHALL enforce prompt budget limits for knowledge context.

#### Scenario: Relevant knowledge content is too long

- **GIVEN** selected knowledge entries contain long content
- **WHEN** AI Auto Reply inserts knowledge into the prompt
- **THEN** each knowledge entry SHALL be truncated to a configured safe length
- **AND** total inserted knowledge context SHALL not exceed the configured maximum

### Requirement: Knowledge Retrieval Metadata

The system SHOULD support lightweight retrieval metadata on approved knowledge.

#### Scenario: Reviewer approves draft knowledge

- **GIVEN** reviewer approves a draft knowledge
- **WHEN** the system creates `MPengetahuan`
- **THEN** the system SHOULD store searchable keywords or tags for lightweight retrieval
- **AND** the knowledge MAY include AI priority metadata if configured

### Requirement: Per-Chat Knowledge Mode Control

The system SHALL allow authorized CS users to change knowledge retrieval mode per active chat.

#### Scenario: CS selects lightweight mode

- **GIVEN** CS is authorized to manage inbox
- **WHEN** CS selects `Ringan` for a chat
- **THEN** AI Auto Reply SHALL use lightweight top-N knowledge retrieval for that chat

#### Scenario: CS selects all knowledge mode

- **GIVEN** CS is authorized to manage inbox
- **WHEN** CS selects `AllKnowledge` for a chat
- **THEN** AI Auto Reply SHALL allow broader approved knowledge retrieval
- **AND** SHALL still enforce maximum item and prompt length limits

#### Scenario: CS disables knowledge for a chat

- **GIVEN** CS is authorized to manage inbox
- **WHEN** CS selects `Nonaktif` for a chat
- **THEN** AI Auto Reply SHALL omit Knowledge Base context for that chat
