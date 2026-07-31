## ADDED Requirements

### Requirement: Simpan Nama Grup dari WAHA ke Database

Sistem SHALL menyimpan nama grup yang diperoleh dari WAHA API ke kolom \TChat.NamaGrupWaha\ melalui background job asynchronous.

#### Scenario: Job berhasil mengambil nama grup dari WAHA

- **WHEN** \SyncWahaChatIdentityJob\ diproses untuk chat grup dengan JID valid \@g.us\
- **THEN** sistem SHALL memanggil WAHA API untuk metadata grup
- **AND** sistem SHALL menyimpan nama grup ke \TChat.NamaGrupWaha\
- **AND** sistem SHALL mencatat waktu sinkronisasi di \TChat.TglIdentitasWahaDiambil\
- **AND** sistem SHALL menandai status sebagai \success\ di \TChat.StatusIdentitasWaha\

#### Scenario: Job gagal karena WAHA unavailable

- **WHEN** \SyncWahaChatIdentityJob\ tidak dapat menghubungi WAHA API
- **THEN** sistem SHALL retry hingga 3 kali dengan backoff 30/120 detik
- **AND** setelah retry habis, sistem SHALL menandai status sebagai \ailed\
- **AND** sistem SHALL menyimpan pesan error tersanitasi di \TChat.PesanErrorIdentitasWaha\
- **AND** snapshot lama (jika ada) TIDAK dihapus

#### Scenario: Deduplikasi job untuk chat yang sama

- **WHEN** multiple webhook memicu job untuk \IdChat\ yang sama dalam 60 detik
- **THEN** sistem SHALL memproses hanya satu instance job
- **AND** instance lain SHALL dibatalkan atau dilewati

### Requirement: Prioritas Fallback Nama Grup di Tab WhatsApp Asli

Sistem SHALL menampilkan nama grup di Tab WhatsApp Asli dengan urutan prioritas: snapshot WAHA > payload pesan terakhir > master mapping > raw JID.

#### Scenario: Snapshot WAHA tersedia

- **WHEN** \TChat.NamaGrupWaha\ berisi nilai non-empty
- **THEN** sistem SHALL menampilkan nilai tersebut sebagai nama grup utama
- **AND** sistem SHALL menampilkan badge \"WAHA\" sebagai sumber data

#### Scenario: Snapshot kosong, payload tersedia

- **WHEN** \TChat.NamaGrupWaha\ kosong DAN payload pesan terakhir mengandung \group.subject\ atau \group.name\
- **THEN** sistem SHALL menampilkan nama dari payload
- **AND** sistem SHALL menampilkan badge \"Payload\" sebagai sumber data

#### Scenario: Snapshot dan payload kosong, master tersedia

- **WHEN** \TChat.NamaGrupWaha\ kosong DAN payload tidak mengandung nama grup DAN \MGrupWhatsapp.NamaGrup\ tersedia
- **THEN** sistem SHALL menampilkan nama dari master mapping
- **AND** sistem SHALL menampilkan badge \"Internal\" sebagai sumber data

#### Scenario: Semua sumber kosong

- **WHEN** snapshot, payload, dan master mapping semua kosong
- **THEN** sistem SHALL menampilkan raw JID grup (\@g.us\)
- **AND** sistem SHALL menampilkan badge \"JID\" sebagai sumber data

### Requirement: Tampilkan Icon Grup di List Chat

Sistem SHALL menampilkan icon yang membedakan chat grup dan chat pribadi di list Inbox WhatsApp.

#### Scenario: Chat grup ditampilkan

- **WHEN** \TChat.JenisChat = 'Grup'\
- **THEN** sistem SHALL menampilkan icon \heroicon-o-user-group\ di sebelah nama chat
- **AND** sistem SHALL menampilkan badge \"Grup\" berwarna biru

#### Scenario: Chat pribadi ditampilkan

- **WHEN** \TChat.JenisChat = 'Pribadi'\
- **THEN** sistem SHALL menampilkan icon \heroicon-o-user\ di sebelah nama chat
- **AND** sistem SHALL menampilkan badge \"Pribadi\" berwarna hijau

### Requirement: Pembedaan Sumber Data di UI

Sistem SHALL menunjukkan sumber data identitas (WAHA vs Internal) secara visual di header chat.

#### Scenario: Data berasal dari snapshot WAHA

- **WHEN** nama chat ditampilkan dari \TChat.NamaGrupWaha\ atau \TChat.NamaKontakWaha\
- **THEN** sistem SHALL menampilkan label kecil \"WAHA\" dengan warna abu-abu muda
- **AND** hover pada label SHALL menampilkan tooltip \"Dari WhatsApp API\"

#### Scenario: Data berasal dari master mapping internal

- **WHEN** nama chat ditampilkan dari \MGrupWhatsapp.NamaGrup\ atau \MNomorWhatsapp.NamaKontak\
- **THEN** sistem SHALL menampilkan label kecil \"Internal\" dengan warna biru muda
- **AND** hover pada label SHALL menampilkan tooltip \"Dari database WACS\"

### Requirement: Refresh Metadata WAHA Manual

Sistem SHALL menyediakan aksi manual untuk me-refresh identitas WAHA pada chat aktif.

#### Scenario: User klik refresh identity

- **WHEN** user dengan permission \INBOX_MANAGE\ klik tombol \"Refresh Identity\" pada chat aktif
- **THEN** sistem SHALL dispatch \SyncWahaChatIdentityJob\ untuk chat tersebut
- **AND** sistem SHALL menampilkan indikator loading
- **AND** setelah job selesai, sistem SHALL reload data chat tanpa full page refresh

#### Scenario: User tanpa permission mencoba refresh

- **WHEN** user tanpa permission \INBOX_MANAGE\ mencoba mengakses endpoint refresh
- **THEN** sistem SHALL menolak dengan HTTP 403
- **AND** tombol refresh TIDAK ditampilkan di UI
