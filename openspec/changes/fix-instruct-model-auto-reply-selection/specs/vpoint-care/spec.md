# Spec Delta: Pemilihan Model Instruct pada Auto Reply

## MODIFIED Requirements

### Requirement: Auto-Reply Model Selection

Menggantikan requirement "Auto-Reply uses Primary Model Only" pada change `add-model-instruct` dan requirement "Auto-reply tetap memakai Model Utama" pada change `add-ai-instruct-model`, yang saling bertentangan dengan requirement "Jawaban pertama Inbox WhatsApp memakai Model Instruct".

Sistem SHALL memakai `ModelInstructAi` hanya untuk **balasan AI pertama** pada sebuah sesi chat, dan SHALL memakai `ModelAi` untuk seluruh balasan berikutnya, pesan penutup chat, serta test koneksi AI. Aturan ini SHALL berlaku identik untuk seluruh provider yang didukung: OpenAI, DeepSeek, OpenRouter, dan 9Router.

#### Scenario: Balasan AI pertama pada chat baru

- **GIVEN** `ModelInstructAi` berisi `fast-instruct-model` dan `ModelAi` berisi `primary-model`
- **AND** chat belum memiliki satu pun `TChatD` keluar dengan `DihasilkanOlehAi = 1`
- **WHEN** auto-reply memproses pesan masuk pada chat tersebut
- **THEN** request ke provider memakai `fast-instruct-model`
- **AND** `TAiPermintaan.ModelAi` untuk permintaan tersebut berisi `fast-instruct-model`

#### Scenario: Balasan AI lanjutan

- **GIVEN** `ModelInstructAi` berisi `fast-instruct-model` dan `ModelAi` berisi `primary-model`
- **AND** chat sudah memiliki minimal satu `TChatD` keluar dengan `DihasilkanOlehAi = 1`
- **WHEN** auto-reply memproses pesan masuk berikutnya
- **THEN** request ke provider memakai `primary-model`
- **AND** `TAiPermintaan.ModelAi` untuk permintaan tersebut berisi `primary-model`

#### Scenario: Aturan berlaku untuk provider chat-completions

- **GIVEN** `ProviderAi` bernilai `DeepSeek`, `OpenRouter`, atau `9Router`
- **AND** `ModelInstructAi` berisi `fast-instruct-model`
- **AND** chat belum memiliki balasan AI sebelumnya
- **WHEN** auto-reply memproses pesan masuk
- **THEN** body request `model` yang dikirim ke endpoint `chat/completions` berisi `fast-instruct-model`
- **AND** sistem tidak memakai `ModelAi` untuk balasan pertama tersebut

#### Scenario: Aturan berlaku untuk provider OpenAI

- **GIVEN** `ProviderAi` bernilai `OpenAI`
- **AND** `ModelInstructAi` berisi `fast-instruct-model`
- **AND** chat belum memiliki balasan AI sebelumnya
- **WHEN** auto-reply memproses pesan masuk
- **THEN** body request `model` yang dikirim ke Responses API berisi `fast-instruct-model`

#### Scenario: Pesan penutup chat memakai Model Utama

- **GIVEN** `ModelInstructAi` terisi
- **WHEN** agent menutup percakapan dan sistem membuat pesan penutup
- **THEN** request ke provider memakai `ModelAi`
- **AND** sistem tidak memakai `ModelInstructAi` meskipun chat belum pernah dibalas AI

#### Scenario: Test koneksi memakai Model Utama

- **GIVEN** `ModelInstructAi` terisi
- **WHEN** administrator menekan test koneksi AI
- **THEN** request ke provider memakai `ModelAi`
- **AND** tidak ada baris `TChatD` yang dibuat
- **AND** tidak ada pesan yang dikirim ke WAHA

## ADDED Requirements

### Requirement: Audit Model Auto Reply Akurat

Sistem SHALL mencatat pada `TAiPermintaan.ModelAi` nama model yang benar-benar dikirim ke provider untuk permintaan tersebut.

#### Scenario: Log audit balasan pertama

- **GIVEN** balasan pertama sebuah chat diproses memakai `ModelInstructAi`
- **WHEN** baris `TAiPermintaan` untuk permintaan tersebut dibaca
- **THEN** kolom `ModelAi` berisi nilai `ModelInstructAi` yang dipakai
- **AND** nilainya sama dengan model pada request yang dikirim ke provider

#### Scenario: Penentuan balasan pertama dievaluasi satu kali

- **WHEN** auto-reply memproses satu pesan masuk
- **THEN** status "balasan pertama" dievaluasi sebelum baris `TAiPermintaan` dibuat
- **AND** nilai yang sama dipakai untuk pencatatan audit dan untuk pemanggilan provider
- **AND** tidak ada variabel yang dibaca sebelum didefinisikan

### Requirement: Penanganan Model Instruct Kosong

Sistem SHALL memperlakukan `ModelInstructAi` yang bernilai `NULL`, string kosong, atau tidak tersedia sebagai kolom sebagai "tidak dikonfigurasi", dan SHALL melakukan fallback berjenjang ke `ModelAi` lalu ke model default konfigurasi.

#### Scenario: Model Instruct bernilai string kosong

- **GIVEN** `MPengaturanAi.ModelInstructAi` berisi string kosong
- **AND** `ModelAi` berisi `primary-model`
- **WHEN** auto-reply membuat balasan pertama pada sebuah chat
- **THEN** request ke provider memakai `primary-model`
- **AND** sistem tidak mengirim nama model kosong ke provider

#### Scenario: Kolom Model Instruct belum ada pada database

- **GIVEN** database belum memiliki kolom `ModelInstructAi`
- **AND** `ModelAi` berisi `primary-model`
- **WHEN** auto-reply membuat balasan pertama pada sebuah chat
- **THEN** request ke provider memakai `primary-model`
- **AND** sistem tidak melempar error properti yang tidak ada

#### Scenario: Model Utama juga kosong

- **GIVEN** `ModelInstructAi` dan `ModelAi` keduanya kosong
- **WHEN** auto-reply membuat balasan
- **THEN** request ke provider memakai nilai `config('services.openai.model')`

#### Scenario: Administrator mengosongkan Model Instruct

- **GIVEN** administrator memiliki permission `ai_agent.manage`
- **WHEN** administrator mengosongkan field Model Instruct dan menyimpan pengaturan
- **THEN** sistem menyimpan `NULL` pada `MPengaturanAi.ModelInstructAi`, bukan string kosong
- **AND** cache pengaturan AI di-flush
- **AND** balasan berikutnya memakai `ModelAi`
