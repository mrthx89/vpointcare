# PRD — VPoint Care (WACS: WhatsApp Customer Service)

## 0. Informasi Dokumen

| Item | Nilai |
| --- | --- |
| Nama produk | VPoint Care |
| Kode internal | WACS (WhatsApp Customer Service) |
| Versi dokumen | 1.0 |
| Tanggal | 2026-07-23 |
| Jenis dokumen | Product Requirements Document (reverse-engineered dari source code aktual) |
| Sumber kebenaran | `src/` (Laravel 13 + Filament 5), `openspec/specs/vpoint-care/spec.md`, `openspec/project.md`, `README.md`, `src/script/DATABASE_SCHEMA_WACS.sql` |
| Status | Menggambarkan perilaku sistem yang sudah terimplementasi per commit `3c10872` |
| Bahasa kerja | Bahasa Indonesia; nama class/tabel/kolom/route/command dipertahankan sesuai source code |

**Catatan metodologi.** Dokumen ini disusun dengan membaca source code, migration, konfigurasi, dan spec yang ada — bukan dari asumsi. Setiap requirement fungsional (FR) di bawah merujuk ke file/class/tabel nyata. Bagian yang belum terimplementasi ditandai eksplisit sebagai *Gap* atau dipindahkan ke Bagian 16 (Backlog) dan Bagian 15 (Technical Debt). Bila ditemukan perbedaan antara README dan source code, source code yang dipakai sebagai acuan dan perbedaannya dicatat.

---

## 1. Ringkasan Eksekutif

VPoint Care adalah aplikasi **customer service WhatsApp terpusat** untuk tim operasional VPoint. Pesan WhatsApp customer masuk melalui gateway **WAHA** ke endpoint webhook aplikasi, dinormalisasi dan disimpan ke SQL Server, lalu ditampilkan pada **Inbox WhatsApp** di panel admin Filament. Agent dapat membalas manual (teks/lampiran), menyimpan catatan internal, memetakan chat ke customer/instansi, menutup percakapan, dan mengeskalasi ke **Ticket** atau **Task**. Secara paralel, **AI Agent** dapat menghasilkan balasan otomatis (dikirim ke WhatsApp atau disimpan sebagai draft lokal) berdasarkan jam kerja, hari libur, sesi chat, dan knowledge base internal.

Nilai utama produk:

1. **Satu inbox** untuk seluruh percakapan WhatsApp customer (chat pribadi dan grup), lengkap dengan konteks instansi/customer.
2. **Tidak ada chat yang terlewat** — notifikasi internal otomatis untuk chat yang belum terbalas, plus indikator realtime.
3. **AI yang terkendali** — auto-reply hanya berjalan pada kondisi yang dikonfigurasi, dengan mode draft-lokal untuk fase adopsi.
4. **Jejak audit lengkap** — log webhook, log integrasi, log error, histori chat, histori penugasan, dan histori status ticket.
5. **Kontrol akses granular** — role dan permission berbasis data (`MPeran`, `MHakAkses`) yang sekaligus mengendalikan menu sidebar.

---

## 2. Latar Belakang dan Problem Statement

### 2.1 Kondisi tanpa produk

Tim CS VPoint menerima keluhan, pertanyaan, dan permintaan customer melalui WhatsApp pada perangkat/nomor yang tersebar. Konsekuensinya:

- Percakapan tidak terdokumentasi pada sistem yang dapat diaudit.
- Tidak ada mekanisme yang menjamin setiap chat terbalas dalam SLA.
- Konteks customer (instansi, produk, riwayat masalah) tidak tersedia saat agent membalas.
- Eskalasi ke developer dilakukan manual dan kehilangan jejak.
- Beban jawaban repetitif (jam operasional, prosedur standar) sepenuhnya manual.

### 2.2 Problem statement

> Tim customer service VPoint membutuhkan satu tempat kerja terpusat yang menerima seluruh pesan WhatsApp customer secara realtime, menyediakan konteks customer/instansi saat membalas, menjamin tidak ada chat yang terlewat, mengeskalasi masalah menjadi ticket/task yang terlacak, dan mengotomasi balasan repetitif dengan AI tanpa kehilangan kontrol manusia.

### 2.3 Kenapa arsitektur ini

- **WAHA** dipilih sebagai gateway WhatsApp karena tidak memerlukan onboarding WhatsApp Business API resmi dan dapat dijalankan self-hosted.
- **SQL Server** dipakai karena selaras dengan ekosistem data VPoint yang sudah ada (termasuk VToken).
- **Filament 5** dipakai agar seluruh CRUD master data, resource ticket/task, dan halaman kustom berada dalam satu panel dengan pola konsisten.
- **Queue + Reverb** dipakai agar webhook merespons cepat (pekerjaan berat asinkron) dan UI mendapat update realtime.

---

## 3. Tujuan Produk dan Metrik Keberhasilan

### 3.1 Tujuan produk

| ID | Tujuan |
| --- | --- |
| G1 | Seluruh pesan WhatsApp customer tercatat dan dapat ditelusuri di satu sistem |
| G2 | Agent dapat membalas customer langsung dari panel admin dengan konteks lengkap |
| G3 | Chat yang belum terbalas terdeteksi otomatis dan dinotifikasi ke tim internal |
| G4 | Masalah customer dapat diubah menjadi ticket/task dengan SLA, assignee, dan histori |
| G5 | Balasan repetitif dapat diotomasi AI dengan aturan yang dapat dikonfigurasi dan aman |
| G6 | Data instansi/customer tersinkron dari sumber resmi (VToken) |
| G7 | Akses menu dan aksi dikendalikan role/permission per pengguna |

### 3.2 Metrik yang sudah dihitung sistem

Sistem sudah menghitung metrik berikut pada `App\Filament\Pages\Dashboard` (periode dapat difilter dengan date range picker):

| Metrik | Definisi teknis |
| --- | --- |
| `incoming_messages` | `COUNT` `TChatD` dengan `ArahPesan='Masuk'` pada periode |
| `incoming_chats` | `COUNT DISTINCT IdChat` untuk pesan masuk pada periode |
| `outgoing_cs` | Pesan keluar dengan `DihasilkanOlehAi` = 0/NULL |
| `outgoing_ai` | Pesan keluar dengan `DihasilkanOlehAi` = 1 |
| `unanswered_chats` | Chat yang balasan CS terakhirnya lebih lama dari pesan masuk terakhir (atau belum pernah dibalas CS) |
| `unread_messages` | `SUM(TChat.JumlahPesanBelumDibaca)` |
| `sent_waha` / `failed_waha` | `StatusKirim` = `'Terkirim WAHA'` / `'Gagal WAHA'` |
| `avg_response_minutes` | Rata-rata selisih pesan masuk pertama → balasan keluar pertama per chat |
| `tickets_created` | `COUNT` `TTicket` pada periode |
| `mapped_chats` | Chat pada periode yang `IdInstansi` tidak NULL |
| `active_chats` / `closed_chats` | Chat yang `DiambilOleh` terisi, dibedakan status `DITUTUP` |
| `satisfaction.score` | Indeks komposit: `responseRate*0.4 + deliveryRate*0.2 + speedScore*0.25 + mappingRate*0.15` |

`speedScore` dipetakan dari `avg_response_minutes`: ≤5 mnt → 100, ≤15 → 85, ≤60 → 65, ≤240 → 45, selebihnya 25; tanpa data → 50. Label indeks: ≥85 *Excellent*, ≥70 *Good*, ≥55 *Needs attention*, selebihnya *Critical*.

### 3.3 Target yang direkomendasikan (belum ditegakkan sistem)

Nilai berikut **belum** dienforce oleh kode dan diusulkan sebagai target operasional:

| Metrik | Target |
| --- | --- |
| Response rate (chat terbalas) | ≥ 95% pada jam kerja |
| Rata-rata waktu respons pertama | ≤ 15 menit pada jam kerja |
| Delivery rate WAHA | ≥ 98% |
| Mapping rate chat ke instansi | ≥ 90% |
| Skor kepuasan komposit | ≥ 85 |

---

## 4. Ruang Lingkup

### 4.1 In scope (sudah terimplementasi)

- Panel admin `/admin` dengan autentikasi internal `MPengguna` + registrasi + Google/SSO OIDC.
- Inbox WhatsApp: daftar chat, thread pesan, balasan teks/lampiran, catatan internal, draft lokal, mulai chat baru, mapping ulang, refresh profil WAHA, tutup percakapan, kontrol AI per chat.
- Webhook WAHA masuk (token + HMAC opsional) dengan pemrosesan asinkron dan dedupe.
- Pengiriman WhatsApp keluar (teks, gambar, video, file) via WAHA + circuit breaker.
- Proxy media & foto profil WAHA melalui route terautentikasi.
- AI Agent: pengaturan provider (OpenAI/DeepSeek/OpenRouter/9Router), model utama & model instruct, prompt sistem, template per mode, jam kerja/hari libur, nomor pengecualian, batas riwayat, mode kirim, test koneksi, hapus API key.
- Auto-reply AI berbasis keputusan mode (Hari Libur / Luar Jam Kerja / Berlanjut / Sapaan Jam Kerja / Skip) dengan retrieval knowledge.
- AI Knowledge Learning: ekstraksi draft knowledge dari chat + review manual (`TAiDraftPengetahuan`).
- VPoint Assistant: chatbot internal untuk pengguna panel.
- Ticketing: dashboard statistik + resource CRUD + histori status + histori penugasan + lampiran privat + notifikasi assignee.
- Task: resource CRUD mandiri/terkait ticket + checklist + komentar + lampiran + histori penugasan + notifikasi.
- Master data: instansi, customer, nomor WhatsApp, grup WhatsApp, anggota grup, hari libur, pengetahuan, pengguna, hak akses, status/kategori/prioritas ticket, status task, job schedule.
- Histori chat (tabel arsip percakapan) + halaman detail sesi chat.
- Log Data: log integrasi, log webhook, status queue/job/failed job/batch.
- Integrasi VToken (import instansi) sync/queue.
- Scheduler berbasis tabel `job_schedules`.
- Notifikasi WhatsApp internal untuk chat belum terbalas.
- Lokalisasi ID/EN untuk UI admin.

### 4.2 Out of scope (versi ini)

- Kanal selain WhatsApp (email, Telegram, live chat web).
- WhatsApp Business API resmi (Meta Cloud API).
- Survei kepuasan (CSAT/NPS) ke customer.
- SLA breach escalation otomatis pada ticket (hanya penanda overdue).
- Laporan/ekspor terjadwal (PDF/Excel) dan BI eksternal.
- Aplikasi mobile native.
- Multi-tenant terpisah per instansi (aplikasi single-tenant untuk VPoint).
- Fine-tuning model AI dari data chat (desain sengaja memakai human-in-the-loop RAG).

---

## 5. Persona dan Peran

### 5.1 Persona

| Persona | Kebutuhan utama | Modul yang dipakai |
| --- | --- | --- |
| **Agent CS** | Membalas cepat, tahu siapa customer, tidak melewatkan chat | Inbox, Histori Chat, Ticket, Task, VPoint Assistant |
| **Supervisor CS** | Memantau beban & performa tim, mengatur AI dan knowledge | Dashboard, AI Agent, Knowledge, Hari Libur, Hak Akses |
| **Developer/Teknis** | Menerima eskalasi teknis dan menelusuri error integrasi | Ticket, Task, Log Data |
| **Administrator** | Mengelola pengguna, role, master data, jadwal job, deployment | Semua modul |
| **Viewer/Manajemen** | Melihat ringkasan operasional tanpa mengubah data | Dashboard, Histori Chat, Ticket/Task (read) |

### 5.2 Role bawaan dan permission

Didefinisikan di `App\Support\AccessPermissions::defaultRoles()` dan `defaultRolePermissions()`, di-seed oleh `DatabaseSeeder`.

| Kode role | Nama | Permission |
| --- | --- | --- |
| `ADMIN` | Admin | Seluruh permission (`AccessPermissions::codes()`) |
| `SUPERVISOR_CS` | Supervisor CS | dashboard, inbox (view/reply/manage), ticket (view/manage), task (view/manage), ai_agent (view/manage), log_data, master_customer (view/manage), knowledge (view/manage), holiday (view/manage), chat_history, chatbot, hak_akses (view/manage), job_schedule |
| `CS` | Customer Service | dashboard, inbox (view/reply), ticket (view/manage), task (view/manage), master_customer.view, chat_history, chatbot |
| `DEVELOPER` | Developer | dashboard, ticket (view/manage), task (view/manage), log_data, chat_history, chatbot |
| `VIEWER` | Viewer | dashboard, chat_history, ticket.view, task.view |

### 5.3 Katalog permission

| Kode | Fungsi |
| --- | --- |
| `dashboard.view` | Akses halaman Dashboard |
| `inbox.view` / `inbox.reply` / `inbox.manage` | Lihat inbox / kirim balasan & draft / kelola chat (tutup, mapping, toggle AI, refresh profil, mode knowledge) |
| `ticket.view` / `ticket.manage` | Lihat ticket & unduh lampiran ticket / CRUD ticket dan master ticketing |
| `task.view` / `task.manage` | Lihat task & unduh lampiran task / CRUD task |
| `ai_agent.view` / `ai_agent.manage` | Lihat pengaturan AI / ubah pengaturan, test koneksi, hapus API key |
| `knowledge.view` / `knowledge.manage` | Lihat knowledge / kelola knowledge & draft knowledge AI |
| `holiday.view` / `holiday.manage` | Lihat / kelola kalender hari libur |
| `master_customer.view` / `master_customer.manage` | Lihat / kelola master customer & mapping WhatsApp |
| `user.view` / `user.manage` | Lihat / kelola pengguna |
| `hak_akses.view` / `hak_akses.manage` | Lihat / kelola hak akses & struktur menu |
| `chat_history.view` | Akses Histori Chat |
| `chatbot.access` | Akses VPoint Assistant |
| `log_data.view` | Akses Log Data |
| `job_schedule.view` | Akses & edit Job Schedule |
| `menu.master.instansi`, `menu.master.customer`, `menu.master.nomor_whatsapp`, `menu.master.grup_whatsapp`, `menu.master.anggota_grup` | Penanda menu sidebar untuk resource master (bukan permission aksi) |

---

## 6. Arsitektur Sistem

### 6.1 Diagram alur utama

```text
                    ┌──────────────────┐
  WhatsApp Customer │  WhatsApp Client │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │   WAHA Server    │  (self-hosted gateway)
                    └───┬──────────┬───┘
             webhook    │          │  REST API (sendText/sendImage/sendVideo/sendFile,
              POST      │          │           contacts/profile-picture, lids)
                        │          │
   POST /webhooks/waha/{token?}    │
                        │          │
        ┌───────────────▼──────────┴────────────────────────────┐
        │            Laravel 13 (src/)                          │
        │                                                       │
        │  WahaWebhookController ─► ProcessWebhookJob (queue:    │
        │    (validasi token+HMAC)     webhooks, tries=3, 60s)   │
        │                               │                       │
        │                     WahaWebhookProcessor              │
        │                    (transaksi DB, dedupe, mapping)    │
        │                               │                       │
        │            ┌──────────────────┼───────────────────┐   │
        │            ▼                  ▼                   ▼   │
        │  SendBroadcastDebouncedJob  ProcessAiAutoReplyJob      │
        │   (queue: broadcasts)        (queue: ai-replies)       │
        │            │                  │                       │
        │      WahaInboxUpdated    AiAutoReplyService ──► WahaSender
        │      (ShouldBroadcastNow)      │                       │
        └────────────┬──────────────────┼───────────────────────┘
                     │                  │
              ┌──────▼──────┐   ┌───────▼────────┐   ┌──────────────┐
              │ Laravel     │   │  SQL Server    │   │ AI Provider  │
              │ Reverb (WS) │   │  DBVPointCare  │   │ OpenAI /     │
              └──────┬──────┘   └───────┬────────┘   │ DeepSeek /   │
                     │                  │            │ OpenRouter / │
              ┌──────▼──────────────────▼──────┐     │ 9Router      │
              │  Filament 5 Admin Panel /admin │     └──────────────┘
              │  (Livewire + Echo + Vite)      │
              └────────────────────────────────┘
                             ▲
                             │  import instansi
                    ┌────────┴─────────┐
                    │  VToken Open API │
                    └──────────────────┘
```

### 6.2 Komponen runtime yang wajib berjalan di production

| Proses | Perintah | Fungsi |
| --- | --- | --- |
| Web server | Apache/Nginx/IIS → `src/public` | Melayani HTTP request |
| Queue worker | `php artisan queue:work --sleep=3 --tries=3 --timeout=120` | Memproses webhook, auto-reply, broadcast, import |
| Scheduler | `php artisan schedule:work` | Menjalankan command dari `job_schedules` |
| Reverb | `php artisan reverb:start --host=0.0.0.0 --port=8080` | WebSocket untuk update realtime inbox |

### 6.3 Stack teknologi

| Area | Teknologi | Versi (composer.json / package.json) |
| --- | --- | --- |
| Bahasa | PHP | `^8.3` |
| Framework | Laravel | `^13.0` |
| Admin panel | Filament | `^5.6` |
| Realtime | Laravel Reverb | `^1.10` |
| Date filter | `malzariey/filament-daterangepicker-filter` | `^5.0` |
| Database | Microsoft SQL Server (driver `sqlsrv`) | — |
| Frontend build | Vite | `^8.0.0` |
| CSS | Tailwind CSS | `^4.0.0` |
| Realtime client | `laravel-echo` + `pusher-js` | `^2.3.4` / `^8.5.0` |
| UI dialog | `sweetalert2` | `^11.26.24` |
| Dev tools | Pint, PHPUnit 12, Pail, Collision | — |

### 6.4 Antrean (queue)

| Queue | Job | tries | timeout | Catatan |
| --- | --- | --- | --- | --- |
| `webhooks` | `ProcessWebhookJob` | 3 | 60s | Payload webhook mentah; idempotensi via `IdPesanWaha` |
| `ai-replies` | `ProcessAiAutoReplyJob` | 2 | 90s | Dilewati bila CS sudah membalas lebih dulu |
| `broadcasts` | `SendBroadcastDebouncedJob` | default | 30s | Debounce 2 detik via cache, delay 500 ms |
| default | `ImportVTokenCustomersToInstansi` | default | default | Dapat dijalankan sync via `--sync` |

### 6.5 Broadcast

- Event: `App\Events\WahaInboxUpdated` implements `ShouldBroadcastNow`.
- Channel: **public** `waha-inbox`, nama event `.inbox.updated`, payload `{ chat_id }`.
- Channel privat `waha-agents` terdaftar di `routes/channels.php` (mengembalikan `id` dan `name` agent) untuk presence/indikator agent aktif.
- Sisi klien: `resources/js/app.js` (Echo) memanggil `handleInboxUpdate()` → `loadInbox()` pada halaman Inbox.

---

## 7. Model Data

### 7.1 Konvensi

Ditetapkan pada header `src/script/DATABASE_SCHEMA_WACS.sql`:

- Prefix `M` = master data (selalu punya `NonAktif`), prefix `T` = transaksi/log.
- PK `uniqueidentifier` dengan `DEFAULT NEWSEQUENTIALID()`; aplikasi umumnya mengisi sendiri dengan `Str::orderedUuid()`.
- Semua tabel punya kolom audit: `TglBuat`, `DibuatOleh`, `TglEdit`, `DieditOleh`.
- Kolom audit user **tidak** dibuat FK agar data historis aman saat user berubah.
- Migration schema utama (`2026_04_27_000001_create_vpoint_care_schema`) mengeksekusi file SQL dan **wajib** koneksi `sqlsrv`.

### 7.2 Master data

| Tabel | Isi | Kunci unik |
| --- | --- | --- |
| `MPeran` | Role aplikasi | `KodePeran` |
| `MHakAkses` | Permission + node menu sidebar (self-reference `IdHakAkses`, `SortOrder`, `IconString`, label ID/EN) | `KodeHakAkses` (unique filtered, NULL diizinkan untuk node grup) |
| `MPeranHakAkses` | Pemetaan role↔permission | (`IdPeran`,`IdHakAkses`) |
| `MPengguna` | Pengguna aplikasi (sumber autentikasi) | `Email` |
| `MInstansi` | Instansi/klien (sinkron VToken) | `KodeInstansi` |
| `MCustomer` | Kontak person customer | `KodeCustomer` |
| `MNomorWhatsapp` | Nomor WhatsApp customer + `IdWaha` | `NomorWhatsapp` |
| `MGrupWhatsapp` | Grup WhatsApp + `IdGrupWaha` | `KodeGrup` |
| `MAnggotaGrupWhatsapp` | Anggota grup | (`IdGrupWhatsapp`,`IdNomorWhatsapp`) |
| `MProdukCustomer` | Produk yang dipakai customer | — |
| `MStatusChat` | Status percakapan | `KodeStatusChat` |
| `MStatusTicket` / `MKategoriTicket` / `MPrioritasTicket` | Master ticketing (`StatusFinal`, `BatasSlaMenit`, `Warna`, `Urutan`) | kode masing-masing |
| `MStatusTask` | Master status task | `KodeStatusTask` |
| `MSesiWhatsapp` | Sesi WAHA (`KodeSesi`, `BaseUrlWaha`, `StatusSesi`, `WebhookToken`) | `KodeSesi` |
| `MEndpointIntegrasi` | Katalog endpoint integrasi | `KodeEndpoint` |
| `MAiProvider` | Katalog provider AI | `KodeProvider` |
| `MHariLibur` | Kalender libur (`BerlakuTahunan`) | — |
| `MPengaturanAi` | Pengaturan AI Agent (baris `DEFAULT`) | `KodePengaturan` |
| `MPengetahuan` | Knowledge base AI | `KodePengetahuan` |
| `MNomorDokumen` | Counter penomoran dokumen harian | `Kode` |

### 7.3 Transaksi dan log

| Tabel | Isi |
| --- | --- |
| `TChat` | Header percakapan: jenis (`Pribadi`/`Grup`), nomor, mapping customer/instansi/grup, `IdWahaTerdeteksi`, `NomorWhatsappTerdeteksi`, foto profil, prioritas, `DiambilOleh`/`TglDiambil`, `TglChatTerakhir`, `TglDibalasTerakhir`, `JumlahPesanBelumDibaca`, `DitutupOleh`/`TglDitutup`, kontrol AI (`AutoReplyAiAktif`, `AiSudahMenyapa`, `ModeAutoReplyAi`, `TglAutoReplyAiTerakhir`), tracking notifikasi belum terbalas, serta kolom tambahan `ModeKnowledgeAi`/`BatasKnowledgeAi` |
| `TChatD` | Detail pesan: `IdPesanWaha`, `ArahPesan` (`Masuk`/`Keluar`), `JenisPesan` (`Teks`/`Gambar`/`Video`/`Audio`/`Stiker`/`Dokumen`), `IsiPesan`, media (`UrlMedia`,`NamaFileMedia`,`TipeMime`), `PayloadJson`, pengirim, `DikirimOlehCustomer`, `DihasilkanOlehAi`, `IdAiRespon`, `DibalasOleh`, `TglPesan`/`TglDikirim`/`TglDibaca`, `StatusKirim`, `PesanError` |
| `TChatDPenugasan` | Histori penugasan chat |
| `TChatDCatatanInternal` | Catatan internal per chat (tidak dikirim ke WhatsApp) |
| `TTicket` | Header ticket (`NomorTicket`, relasi chat/customer/instansi/kategori/prioritas/status, assignee, target/selesai/tutup, `RingkasanAi`) |
| `TTicketD` | Aktivitas ticket (`Pembuatan`, `PerubahanStatus`, `Catatan`) |
| `TTicketDPenugasan` | Histori penugasan ticket |
| `TTicketDLampiran` | Lampiran ticket (disk privat) |
| `TTask` | Header task (`NomorTask`, relasi ticket/chat/customer/instansi/`IdTugasInduk`, status task, assignee, `EstimasiMenit`) |
| `TTaskDPenugasan`, `TTaskDChecklist`, `TTaskDKomentar`, `TTaskDLampiran` | Detail task |
| `TAiPermintaan` | Permintaan AI (jenis, provider, model, prompt ringkas/JSON, status, waktu, error) |
| `TAiRespon` | Respons AI (ringkas, JSON, token in/out, biaya estimasi, approval) |
| `TAiDraftPengetahuan` | Draft knowledge hasil ekstraksi AI + status review + `HashKonten` |
| `TChatbotInternal` | Riwayat VPoint Assistant per pengguna (`PeranPengirim` CHECK `user`/`assistant`) |
| `TLogWebhookWaha` | Payload webhook mentah + status proses + error |
| `TLogIntegrasi` | Request/response HTTP keluar (WAHA, VToken) + status + durasi |
| `TLogError`, `TLogAktivitas` | Log error dan aktivitas pengguna |
| `notifications` | Notifikasi database Laravel (`notifiable_id` bertipe `uniqueidentifier`) |
| `job_schedules` | Jadwal command (`name`, `command`, `cron_expression`, `is_active`, `description`) |
| `jobs`, `job_batches`, `failed_jobs`, `cache`, `sessions` | Infrastruktur Laravel |

### 7.4 Index penting

Selain index dasar, migration `2026_06_28_000002_add_scalability_indexes` menambahkan index untuk beban tinggi:

- `IX_TChatD_IdPesanWaha_Partial` — filtered index untuk dedupe pesan.
- `IX_TChatD_Arah_Dikirim_Tgl` — `(ArahPesan, DikirimOlehCustomer, TglPesan DESC) INCLUDE (IdChat, IsiPesan)`.
- `IX_TChatD_IdChat_Arah_Ai_Tgl` — pencarian balasan AI terakhir per chat.
- `IX_TChatD_TglPesan_Arah_Status` — agregasi dashboard.
- `IX_TChatbotInternal_Pengguna_Tgl` — riwayat chatbot per pengguna.

### 7.5 Aturan integritas & idempotensi

| Aturan | Implementasi |
| --- | --- |
| Satu pesan WAHA hanya tercatat satu kali | `WahaWebhookProcessor::duplicateMessage()` mengecek `TChatD.IdPesanWaha` sebelum insert |
| Seluruh pemrosesan webhook atomik | `DB::transaction()` membungkus insert log + chat + detail |
| Nomor ticket/task unik per hari | `MNomorDokumen` + `sp_getapplock` (SQL Server) di dalam transaksi, format `TCK-YYYYMMDD-NNN` / `TSK-YYYYMMDD-NNN` |
| Kolom opsional aman untuk DB lama | `SchemaCache::hasColumn()` / `hasTable()` (cache selamanya) sebelum menulis kolom baru |

---

## 8. Requirement Fungsional

Format: **FR-[MODUL]-[nomor]**. Setiap requirement mencantumkan implementasi aktual dan kriteria penerimaan (AC).

### 8.1 Autentikasi dan Registrasi

**FR-AUTH-01 — Autentikasi via `MPengguna`**
Sistem HARUS mengautentikasi pengguna melalui model `App\Models\Master\Pengguna` (tabel `MPengguna`) menggunakan `App\Auth\PenggunaUserProvider`, bukan tabel `users` Laravel (tabel `users` sudah di-drop oleh migration `2026_05_06_000004`).

- Kolom password: `Password` (`getAuthPasswordName()`), remember token: `RememberToken`.
- AC-1: Login `/admin/login` dengan email+password valid membuka panel.
- AC-2: Kredensial salah menolak akses tanpa membocorkan keberadaan email.

**FR-AUTH-02 — Gating akses panel**
`Pengguna::canAccessPanel()` HARUS menolak akses bila salah satu benar: `NonAktif` = 1, `IdPeran` kosong, role tidak aktif (`roleCode()` NULL), atau `StatusRegistrasi` ≠ `approved`.

- AC-1: Pengguna nonaktif tidak dapat masuk panel meskipun password benar.
- AC-2: Pengguna hasil registrasi eksternal berstatus `pending` diarahkan ke `PendingRegistrationResponse`.

**FR-AUTH-03 — Proteksi self-deactivate**
`Pengguna::booted()` HARUS melempar `ValidationException` bila pengguna yang sedang login mencoba mengubah `NonAktif` menjadi true pada dirinya sendiri.

**FR-AUTH-04 — Login/registrasi Google dan SSO (OIDC)**
Sistem HARUS menyediakan login dan registrasi via provider eksternal `google` dan `sso` melalui route `GET /auth/{provider}/redirect` dan `GET /auth/{provider}/callback` (`ExternalAuthController` + `ExternalAuthService`).

- Provider aktif hanya jika `enabled` + `client_id` + `client_secret` + `redirect_uri` terisi (`config/external-auth.php`).
- Flow: generate `state` + `nonce` ke session → authorize URL (`scope=openid email profile`, `prompt=select_account`) → callback memvalidasi state → tukar code → ambil profil → validasi domain (`*_ALLOWED_DOMAINS`).
- Pemetaan identitas: cari `PenggunaExternalIdentity` (`Provider`,`ProviderUserId`) → jika ada, login; jika tidak, cari `MPengguna` by email lalu link; jika tidak ada dan registrasi eksternal aktif, buat pengguna baru berstatus `pending`.
- Seluruh transaksi memakai `lockForUpdate()` dan dicatat via `audit()`.
- AC-1: Callback tanpa `state` yang cocok ditolak.
- AC-2: Email di luar `ALLOWED_DOMAINS` ditolak.
- AC-3: Registrasi eksternal saat `EXTERNAL_REGISTRATION_ENABLED=false` ditolak dengan pesan `ui.auth.external_registration_disabled`.
- AC-4: Pengguna baru tidak langsung dapat akses panel (status `pending`).
- Rate limit: `EXTERNAL_AUTH_RATE_LIMIT` (default 10).

**FR-AUTH-05 — Registrasi internal**
Panel menyediakan halaman registrasi (`App\Filament\Auth\Register`) dengan render hook tombol provider eksternal di bawah form login dan register.

**FR-AUTH-06 — Profil sendiri**
Pengguna yang login HARUS dapat mengedit profilnya via `EditOwnProfileAction` di user menu. Foto profil disimpan sebagai `FotoProfilPath` dan disajikan melalui route `GET /profile-storage/{path}` (`PublicStorageController`) yang menolak path mengandung `..` atau null byte.

### 8.2 Otorisasi dan Navigasi

**FR-ACL-01 — Permission berbasis data**
Otorisasi HARUS dievaluasi dari `MPeranHakAkses` join `MPeran` + `MHakAkses` (semua `NonAktif=0`) melalui `Pengguna::permissionCodes()`, dibungkus helper `FilamentAccess::can()` / `canAny()`.

**FR-ACL-02 — Gating halaman dan resource**
Setiap Filament Page/Resource HARUS mengimplementasikan `canAccess()`/`canViewAny()` dengan kombinasi `FilamentAccess::can(<permission>)` **dan** `NavigationHelper::isActive(<permission>)` (menu tidak dinonaktifkan di `MHakAkses`).

**FR-ACL-03 — Gating aksi**
Aksi mutasi HARUS memanggil `abort_unless(FilamentAccess::can(...), 403)` di sisi server, tidak hanya menyembunyikan tombol. Contoh terimplementasi: `toggleAutoReplyAi`, `tutupPercakapan`, `resetSapaanAi`, `refreshMappingChat`, `refreshProfilWaha`, `updateModeKnowledgeAi` → `inbox.manage`; `simpanBalasanLokal`, `kirimBalasanWaha` → `inbox.reply`; `simpanPengaturan`, `testKoneksiAi`, `hapusApiKey`, `applyProviderPreset` → `ai_agent.manage`; `buatDraftKnowledge` → `knowledge.manage`.

**FR-ACL-04 — Sidebar dinamis dari `MHakAkses`**
Struktur menu (grup, label ID/EN, urutan, ikon heroicon) HARUS dibaca dari `MHakAkses` via `NavigationHelper` (`buildGroups`, `labelFor`, `iconFor`, `sortFor`, `groupFor`, `isActive`, `flush`).

- Grup bawaan: `operasional` (10), `assistant` (20), `master_data` (30), `monitoring` (40), `settings` (50).
- Menu diseed oleh `DatabaseSeeder::seedPermissions()` + `seedSidebarGroups()`.
- AC: Mengubah label/urutan/ikon di resource Hak Akses mengubah sidebar setelah cache navigasi di-flush.

**FR-ACL-05 — Konsistensi perubahan akses**
Setiap penambahan/perubahan menu atau permission HARUS menyelaraskan: konstanta di `AccessPermissions`, `defaultRolePermissions()`, `sidebarMenus()`/`permissionSidebarGroups()`, `NavigationHelper`, visibility resource/page, `DatabaseSeeder`, dan label `resources/lang/{id,en}/ui.php`.

### 8.3 Dashboard

**FR-DASH-01 — Ringkasan operasional berperiode**
Halaman `/admin` (`App\Filament\Pages\Dashboard`) HARUS menampilkan metrik pada Bagian 3.2 untuk rentang tanggal yang dipilih.

- Filter memakai `DateRangePicker` dengan format sesuai locale (`LocaleFormatter::dateInputFormat()`; ID `d-m-Y`, EN `m-d-Y`), separator `" to "`.
- Default periode saat mount: hari ini s/d hari ini.
- Quick range: `today`, `7d`, `30d`, `month` via `setQuickRange()`.
- Parser menerima format `d-m-Y`, `m-d-Y`, `Y-m-d`, `d/m/Y`, `m/d/Y`; separator ` to `, ` - `, ` s/d `.
- Bila `end < start`, sistem menukar keduanya.

**FR-DASH-02 — Performa tim**
Sistem HARUS menampilkan maksimal 10 agent (grup by `TChatD.DibalasOleh`) dengan jumlah balasan, jumlah chat, terkirim, gagal; baris **AI Agent** disisipkan di posisi pertama bila ada balasan AI pada periode.

**FR-DASH-03 — Tren harian**
Sistem HARUS menampilkan tren per tanggal (masuk / balasan CS / balasan AI) maksimal 31 titik, mengisi tanggal kosong dengan nilai 0.

**FR-DASH-04 — Top instansi**
Sistem HARUS menampilkan 8 instansi dengan pesan masuk terbanyak; chat tanpa mapping ditampilkan sebagai `ui.common.not_mapped`.

**FR-DASH-05 — Indeks kepuasan**
Sistem HARUS menghitung skor komposit dan label sesuai formula pada Bagian 3.2; bila `incoming_chats = 0`, skor NULL dengan label "no data".

### 8.4 Webhook WAHA (Intake)

**FR-WH-01 — Endpoint webhook**
Sistem HARUS menyediakan `POST /webhooks/waha/{token?}` (`WahaWebhookController`) dengan throttle `100,1` (100 request/menit).

**FR-WH-02 — Validasi token**
Bila `services.waha.webhook_token` terisi, sistem HARUS membandingkan token path dengan `hash_equals()` dan menolak `403` bila tidak cocok, **tanpa** memutasi data chat.

**FR-WH-03 — Validasi HMAC opsional**
Bila `services.waha.webhook_hmac_key` terisi, sistem HARUS memvalidasi header `X-Webhook-Hmac` dengan `hash_hmac('sha512', rawBody, key)` dan `X-Webhook-Hmac-Algorithm` harus `sha512`; selain itu tolak `403`.

**FR-WH-04 — Respons cepat & pemrosesan asinkron**
Controller HARUS langsung mengembalikan `{ok:true, queued:true}` setelah `ProcessWebhookJob::dispatch()`. Pekerjaan berat tidak boleh dijalankan inline.

**FR-WH-05 — Persistensi payload mentah**
`WahaWebhookProcessor::process()` HARUS menyimpan payload ke `TLogWebhookWaha` (`JenisEvent`, `PayloadJson`, `TglDiterima`, `SudahDiproses=0`) sebelum parsing, lalu menandai `SudahDiproses=1` + `TglDiproses` saat selesai, atau mengisi `PesanError` lalu melempar exception bila gagal.

**FR-WH-06 — Resolusi sesi**
Sistem HARUS mencari `MSesiWhatsapp` berdasarkan `session`/`sessionId` dari payload (default `default`) dan membuat baris baru bila belum ada (`BaseUrlWaha` dari `services.waha.base_url`, `StatusSesi='Aktif'`).

**FR-WH-07 — Normalisasi identitas WhatsApp**
Parser HARUS mendukung variasi payload WAHA/Baileys/whatsapp-web.js. Kandidat `remoteId` yang dicoba berurutan: `chatId`, `from`, `from.id`, `id.remote`, `id._serialized`, `_data.id._serialized`, `_data.id.remote`, `_data.Info.Chat`, `_data.chatId`, `key.remoteJid`, `chat.id`, `chat.id._serialized`, `to`, `to.id`, `groupId`, `group.id`, `payload.chatId`.

- Chat grup dideteksi dari `@g.us` atau flag `isGroup`; pengirim grup diambil dari `participant`/`author`/`sender.id`/`_data.author`.
- `WahaChatHelper::normalizePhoneNumber()` membuang suffix `@...` dan `:...`, menyisakan digit; mengembalikan `NULL` untuk JID `@lid`.
- `WahaChatHelper::normalizeChatId()` mengubah `@s.whatsapp.net` → `@c.us` dan menambahkan `@c.us` untuk nomor polos.

**FR-WH-08 — Resolusi `@lid` ke nomor telepon**
Untuk pengirim ber-JID `@lid`, sistem HARUS memanggil WAHA `GET /api/{session}/lids/{lid}` guna memperoleh nomor telepon nyata dan mengisi `pengirim_nomor` serta `pengirim_phone_jid`.

**FR-WH-09 — Abaikan status broadcast**
Pesan dengan identitas `status@broadcast` atau mengandung `_status@broadcast_` HARUS diabaikan (log ditandai selesai, tidak membuat chat), dengan hasil `{ignored:true, jenis_chat:'Status'}`.

**FR-WH-10 — Abaikan nomor pengecualian**
Pesan **masuk** dari nomor yang terdaftar pada `MPengaturanAi.ExcludeNomorWhatsapp` (dipisah spasi/koma/titik-koma, dibandingkan setelah normalisasi digit) HARUS diabaikan. Pesan `fromMe` tidak terkena filter ini.

**FR-WH-11 — Deduplikasi**
Bila `IdPesanWaha` sudah ada di `TChatD`, sistem HARUS mengembalikan `{duplicate:true, chat_id}` tanpa insert baru.

**FR-WH-12 — Mapping otomatis ke master data**
Sistem HARUS memetakan pesan ke customer/instansi dengan urutan:
1. `MNomorWhatsapp.NomorWhatsapp` = nomor pengirim (aktif).
2. Bila gagal dan kolom `IdWaha` ada: `MNomorWhatsapp.IdWaha` IN (`pengirim_jid`, `pengirim_nomor`, `nomor@c.us`, `nomor@lid`).
3. Untuk grup: `MGrupWhatsapp.IdGrupWaha` = JID grup.
`IdInstansi` diambil dari grup lebih dulu, baru dari nomor.

**FR-WH-13 — Find-or-create percakapan**
- Chat grup dicocokkan dengan `IdGrupWhatsapp`; chat pribadi dicocokkan dengan `NomorWhatsapp` OR `NomorWhatsappTerdeteksi` OR `IdWahaTerdeteksi` (termasuk fallback `nomor@c.us` untuk kasus `@lid`).
- Chat berstatus `DITUTUP` **tidak** dipakai ulang; sistem membuat chat baru berstatus `MENUNGGU_CS`.
- Chat aktif yang ditemukan diperbarui mapping, nama kontak/grup, dan identitas terdeteksi.

**FR-WH-14 — Klasifikasi jenis pesan**
`JenisPesan` ditentukan dari MIME/type/flag: `Gambar`, `Video`, `Audio` (termasuk `ptt`/`voice`), `Stiker`, `Dokumen` (ada MIME/URL media/`hasMedia`/type `document|file`), selain itu `Teks`.

**FR-WH-15 — Update counter chat**
Setelah insert `TChatD`, `TChat` HARUS diperbarui: `TglChatTerakhir` = waktu pesan; `JumlahPesanBelumDibaca` bertambah 1 untuk pesan masuk (tidak bertambah untuk `fromMe`).

**FR-WH-16 — Aksi lanjutan**
Bila hasil `ok` dan bukan duplikat dan `chat_id` ada, `ProcessWebhookJob` HARUS memicu `SendBroadcastDebouncedJob::dispatchDebounced($chatId)` dan `ProcessAiAutoReplyJob::dispatch($chatId, now())`.

**FR-WH-17 — Broadcast debounce**
`SendBroadcastDebouncedJob` HARUS menekan broadcast berulang: kunci cache `broadcast:pending:{chatId}` berlaku 2 detik; job dijadwalkan +500 ms dan hanya menyiarkan bila `Cache::pull()` berhasil.

**FR-WH-18 — Kegagalan job**
`failed()` HARUS mencatat `Log::error` tanpa membocorkan payload sensitif secara utuh.

### 8.5 Pengiriman Pesan Keluar (WAHA Sender)

**FR-SEND-01 — Kirim teks**
`WahaSender::sendText()` HARUS POST ke `{WAHA_BASE_URL}{WAHA_SEND_TEXT_PATH}` (default `/api/sendText`) dengan body `{session, chatId, text}`; `chatId` dinormalisasi.

**FR-SEND-02 — Kirim media**
`WahaSender::sendMedia()` HARUS memilih endpoint berdasarkan MIME: `image/*` → `/api/sendImage`, `video/*` → `/api/sendVideo`, lainnya → `/api/sendFile`. Body memuat `file{mimetype,filename,data(base64)}` dan `caption` opsional. Untuk video ditambahkan `convert:false` dan `asNote:false`.

**FR-SEND-03 — Autentikasi WAHA**
Bila `WAHA_API_KEY` terisi, setiap request HARUS menyertakan header `X-Api-Key`.

**FR-SEND-04 — Timeout**
POST timeout 20 detik; GET timeout 8 detik; proxy media (`WahaMediaController`) 45 detik.

**FR-SEND-05 — Log integrasi**
Setiap panggilan WAHA HARUS menulis baris `TLogIntegrasi` (`KodeIntegrasi`, `UrlEndpoint`, `MetodeHttp`, `RequestJson`, `TglRequest`) sebelum eksekusi dan memperbaruinya dengan `ResponseJson`, `StatusHttp`, `Berhasil`, `PesanError`, `TglResponse`.

Kode integrasi yang dipakai: `WAHA_SEND_TEXT`, `WAHA_MANUAL_SEND_TEXT`, `WAHA_MANUAL_SEND_MEDIA`, `WAHA_START_CHAT_TEXT`, `WAHA_NOTIF_CHAT_BELUM_DIBALAS`, `WAHA_CONTACT_PROFILE_PICTURE`, `WAHA_LID_TO_PHONE`.

**FR-SEND-06 — Circuit breaker**
Setelah **5** kegagalan beruntun, sirkuit HARUS terbuka selama **120 detik**. Selama terbuka, panggilan langsung gagal dengan pesan `ui.scalability.circuit_breaker_active` dan tetap dicatat di `TLogIntegrasi`. Respons sukses menutup kembali sirkuit dan mereset counter. Status disimpan pada properti statis per proses worker.

**FR-SEND-07 — Foto profil kontak**
`getContactProfilePictureUrl()` HARUS memanggil `GET /api/contacts/profile-picture` dengan `contactId`, `session`, `refresh`, lalu mengambil `profilePictureURL`/`url` dari respons.

### 8.6 Media dan Aset

**FR-MEDIA-01 — Proxy media terautentikasi**
`GET /admin/waha-media/{message}` (middleware `auth`, `WahaMediaController`) HARUS menyajikan media pesan berdasarkan `TChatD.Id`; `404` bila pesan tidak ada atau `UrlMedia` kosong.

**FR-MEDIA-02 — Sumber media yang didukung**
Controller HARUS menangani: data URL (`data:...;base64,`), file lokal pada disk `public` (path diawali `/storage/`), dan URL WAHA absolut/relatif. Host `localhost`/`127.0.0.1`/`0.0.0.0` pada URL WAHA HARUS ditulis ulang ke host `WAHA_MEDIA_BASE_URL` agar dapat diakses dari server aplikasi.

**FR-MEDIA-03 — Respons JSON WAHA**
Bila WAHA mengembalikan JSON, controller HARUS mencari `dataUrl`/`data_url`/`media.dataUrl` atau `base64`/`data`/`file`/`body`/`media.*` untuk didekode menjadi biner.

**FR-MEDIA-04 — Header respons**
Respons sukses memakai `Content-Type` dari `TipeMime` atau header upstream, `Content-Disposition: inline` dengan nama file (fallback `whatsapp-image|video|audio|media`), dan `Cache-Control: private, max-age=300`. Kegagalan upstream mengembalikan `424` dengan pesan teks.

**FR-MEDIA-05 — Storage foto profil**
`GET /profile-storage/{path}` menyajikan berkas disk `public` dengan `Cache-Control: public, max-age=604800`, menolak traversal path.

### 8.7 Inbox WhatsApp

Halaman `App\Filament\Pages\InboxWhatsapp` (`filament.pages.inbox-whatsapp`).

**FR-INBOX-01 — Statistik header**
Menampilkan: total chat, total pesan belum dibaca (`SUM(JumlahPesanBelumDibaca)`), jumlah chat grup, dan jumlah chat "unknown" (tanpa `IdInstansi` pada chat maupun grup).

**FR-INBOX-02 — Daftar percakapan**
Daftar HARUS menampilkan maksimal **50** chat, diurutkan `TglChatTerakhir` menurun, **mengecualikan** chat berstatus `DITUTUP`. Setiap baris memuat: jenis chat, nama instansi (dari grup jika chat grup), nama customer, nama kontak, nomor tampilan, `IdWaha` terdeteksi, URL foto profil, status, jumlah belum dibaca, waktu chat terakhir, preview pesan terakhir, status AI (aktif/sudah menyapa/terakhir auto-reply), mode knowledge, dan handler (`DiambilOleh`, nama CS dipotong 18 karakter).

**FR-INBOX-03 — Filter dan pencarian**
- Filter jenis: `keduanya` (default) / `pribadi` / `grup`.
- Pencarian teks mencocokkan (LIKE `%term%`): nama kontak, nomor, nama grup, nama instansi (chat & grup), nama customer, nama kontak master, nomor master, nama grup master, `IdGrupWaha`, `NomorGrupWhatsapp`, serta `IdWaha`, `NomorWhatsappTerdeteksi`, `IdWahaTerdeteksi` bila kolomnya ada.

**FR-INBOX-04 — Thread pesan**
Memilih chat memuat maksimal **200** pesan urut `TglPesan` menaik, lengkap dengan arah, jenis, isi, media (kategori/label/URL proxy), pengirim, status kirim, error, penanda AI, nama & avatar pembalas.

**FR-INBOX-05 — Auto-claim chat**
Saat chat dipilih dan `TChat.DiambilOleh` masih kosong, sistem HARUS mengisinya dengan `MPengguna.Id` pengguna aktif beserta `TglDiambil`. Chat yang sudah diklaim orang lain tidak diambil alih.

**FR-INBOX-06 — Balasan manual ke WhatsApp** (`inbox.reply`)
- Validasi: `replyText` nullable string maks 4000; `attachment` nullable file maks 51200 KB (50 MB).
- Minimal salah satu dari teks atau lampiran wajib ada.
- Tanpa lampiran → `sendText`; dengan lampiran → `sendMedia` (file di-encode base64, dan disalin ke disk `public/chat-outgoing` untuk preview lokal).
- Hasil disimpan ke `TChatD` dengan `StatusKirim` = `Terkirim WAHA` atau `Gagal WAHA` (+`PesanError`), `DibalasOleh` = pengguna aktif.
- `TChat` diperbarui: `TglDibalasTerakhir`, `TglChatTerakhir`, `JumlahPesanBelumDibaca = 0`.

**FR-INBOX-07 — Simpan draft lokal** (`inbox.reply`)
`replyText` wajib (maks 4000). Pesan disimpan dengan `StatusKirim = 'Draft Lokal'` dan **tidak** dikirim ke WhatsApp.

**FR-INBOX-08 — Catatan internal**
Catatan disimpan ke `TChatDCatatanInternal` dan **tidak pernah** dikirim ke WhatsApp.

**FR-INBOX-09 — Mulai chat baru**
Melalui `ChatInitiationService::start()`:
- Target berasal dari `MNomorWhatsapp` terpilih **atau** nomor manual + nama manual.
- Normalisasi nomor: prefix `0` → `62`, prefix `8` → `62`, hanya digit; minimal 10 digit.
- Nomor yang masuk daftar pengecualian ditolak (`ui.pages.inbox.start_chat_number_excluded`).
- Bila sudah ada chat pribadi aktif (bukan `DITUTUP`) untuk nomor tersebut, sistem memakai chat itu; jika tidak, membuat chat baru berstatus `MENUNGGU_CS` dengan `DiambilOleh` = pembuat.
- Mode pengiriman: `send` (kirim WAHA) atau draft (`Draft Lokal`).
- Sesi dipilih dari input; jika kosong, memilih sesi aktif dengan `KodeSesi='default'` diprioritaskan; bila tidak ada, membuat sesi `default`.
- Setelah tersimpan, event `WahaInboxUpdated` dipicu.

**FR-INBOX-10 — Penentuan `chatId` WAHA saat mengirim**
Urutan: (1) `IdGrupWaha` bila chat grup; (2) `chatId` dari `PayloadJson` pesan masuk terakhir (`WahaChatHelper::latestIncomingWahaChatId`); (3) normalisasi `TChat.NomorWhatsapp`.

**FR-INBOX-11 — Toggle auto-reply AI per chat** (`inbox.manage`)
Mengubah `TChat.AutoReplyAiAktif` dan `ModeAutoReplyAi` (`ChatAktif` / `Default`).

**FR-INBOX-12 — Reset sapaan AI** (`inbox.manage`)
Mengubah `AiSudahMenyapa` menjadi `false` sehingga AI dapat menyapa ulang.

**FR-INBOX-13 — Mode knowledge per chat** (`inbox.manage`)
Nilai valid: `Ringan` (limit 5), `AllKnowledge` (limit 20), `Nonaktif` (limit 0); disimpan ke `ModeKnowledgeAi` dan `BatasKnowledgeAi`.

**FR-INBOX-14 — Tutup percakapan** (`inbox.manage`)
- Set `IdStatusChat` = `DITUTUP`, `AutoReplyAiAktif=false`, `DitutupOleh`, `TglDitutup`.
- Memanggil `AiAutoReplyService::sendClosingMessage()` yang membuat pesan penutup (AI atau fallback statis) dan menyimpannya sebagai `TChatD` keluar; dikirim ke WAHA hanya bila `KirimKeWaha` aktif.
- Bila master status `DITUTUP` tidak ada, aksi dibatalkan dengan notifikasi error.

**FR-INBOX-15 — Refresh mapping** (`inbox.manage`)
Menghitung ulang mapping customer/instansi/nomor/grup. Bila tetap tidak ditemukan instansi, sistem menampilkan hingga 8 identifier terdeteksi sebagai petunjuk pemetaan manual.

**FR-INBOX-16 — Refresh profil WAHA** (`inbox.manage`)
Mengambil ulang foto profil kontak dari WAHA dan menyimpan `UrlFotoProfil` + `TglFotoProfilDiambil`. Bila gagal (mis. privasi kontak), menampilkan peringatan, bukan error fatal.

**FR-INBOX-17 — Riwayat percakapan terkait**
Menampilkan maksimal 20 chat lain yang cocok berdasarkan `IdCustomer` / `IdInstansi` / `IdNomorWhatsapp` / `NomorWhatsapp` yang sama.

**FR-INBOX-18 — Update realtime**
Halaman berlangganan channel `waha-inbox` dan memanggil `handleInboxUpdate()` → `loadInbox()` saat menerima `.inbox.updated`.

**FR-INBOX-19 — Buat draft knowledge dari chat** (`knowledge.manage`)
Memanggil `AiKnowledgeLearningService::createDraftFromChat()` (lihat FR-LEARN-01).

**FR-INBOX-20 — Kompatibilitas kolom opsional**
Halaman HARUS memeriksa keberadaan kolom (`Schema::hasColumn`) untuk `MNomorWhatsapp.IdWaha`, `TChatD.NamaFileMedia`, `TChatD.TipeMime`, `TChat.DiambilOleh`, `TChat.UrlFotoProfil`, `TChat.ModeKnowledgeAi`, `TChat.BatasKnowledgeAi`, `MPengguna.FotoProfilPath` sebelum menyeleksi/menulis.

### 8.8 AI Agent — Pengaturan

Halaman `App\Filament\Pages\AiAgent`, sumber data `MPengaturanAi` baris `KodePengaturan='DEFAULT'`.

**FR-AI-01 — Bootstrap pengaturan default**
Saat halaman dibuka dan baris `DEFAULT` belum ada, sistem HARUS membuatnya dengan nilai default (auto-reply nonaktif, jam kerja 08:00–17:00, hari kerja `1,2,3,4,5`, zona `Asia/Jakarta`, provider `OpenAI`, model `gpt-5`, mode kirim `DraftLokal`, batas riwayat 8, notifikasi belum terbalas aktif dengan tunggu 10 menit dan jeda 30 menit).

**FR-AI-02 — Provider yang didukung dan preset**
Preset tersedia untuk `OpenAI`, `DeepSeek`, `OpenRouter`, `9Router`; masing-masing membawa `model`, `instruct_model`, `base_url`, label env key, dan ikon SVG. Menerapkan preset mengisi `ProviderAi`, `ModelAi`, `BaseUrl`, dan `ModelInstructAi` (hanya bila masih kosong).

**FR-AI-03 — Normalisasi provider**
Saat menyimpan, `normalizeProviderSettings()` HARUS mengoreksi `BaseUrl`/`ModelAi` yang tidak konsisten dengan provider terpilih (mis. base URL OpenAI saat provider DeepSeek) ke nilai default provider dari `config/services.php`.

**FR-AI-04 — Validasi form pengaturan**
Aturan yang diberlakukan: jam kerja `date_format:H:i` (wajib), `HariKerja` array minimal 1, `ZonaWaktu` maks 100, `ProviderAi` wajib maks 50, `ModelAi`/`ModelInstructAi` maks 100, `BaseUrl` harus URL maks 255, `PromptSistem` maks 8000, tiap template maks 4000, `MenitTungguNotifikasi` & `JedaNotifikasiMenit` integer 1–1440, `KodePeranPenerimaNotifikasi` wajib maks 200, `ExcludeNomorWhatsapp` maks 4000, `BatasRiwayatPesan` integer 1–20, `ModeKirim` wajib, `apiKeyBaru` maks 2000.

**FR-AI-05 — Penyimpanan API key**
API key baru HARUS dienkripsi dengan `Crypt::encryptString()` dan disimpan pada kolom sesuai provider: `OpenAiApiKeyTerenkripsi`, `DeepSeekApiKeyTerenkripsi`, `OpenRouterApiKeyTerenkripsi`, `NineRouterApiKeyTerenkripsi` (legacy `ApiKeyTerenkripsi` masih dibaca sebagai fallback OpenAI).

**FR-AI-06 — API key tidak pernah ditampilkan**
UI hanya menampilkan **status** key: tersimpan di database, berasal dari environment, atau belum ada (`api_key_db_info` / `api_key_env_info` / `api_key_missing_info`). Nilai key tidak pernah dikirim ke browser.

**FR-AI-07 — Hapus API key** (`ai_agent.manage`)
Menghapus key provider aktif (set NULL) dan mem-flush cache pengaturan; provider tidak dapat dipakai sampai key baru diisi (kecuali key environment tersedia).

**FR-AI-08 — Test koneksi provider** (`ai_agent.manage`)
- `testPrompt` wajib, maks 1000 karakter.
- Test memakai `ModelAi` (bukan `ModelInstructAi`) dan pengaturan yang sedang ada di form, bukan yang tersimpan.
- Test **tidak boleh** mengirim pesan WAHA dan **tidak boleh** membuat baris `TChatD`.
- Hasil atau error ditampilkan pada area teks; seluruh nilai API key yang dikenal disensor menjadi `[secret]` (`sanitizeSecretText`).

**FR-AI-09 — Normalisasi nomor pengecualian**
`ExcludeNomorWhatsapp` disimpan sebagai daftar digit unik yang dipisah baris baru (suffix `@...` dibuang).

**FR-AI-10 — Konsistensi mode kirim**
`KirimKeWaha` dan `ModeKirim` HARUS selalu konsisten: `KirimKeWaha=true` ⇒ `ModeKirim='KirimWaha'`, selain itu `'DraftLokal'`.

**FR-AI-11 — Cache pengaturan**
`AiSettings::get()` men-cache baris `DEFAULT` selama 5 menit (kunci `mpengaturan_ai_default_v2`); setiap penyimpanan/penghapusan key HARUS memanggil `AiSettings::flush()`.

**FR-AI-12 — Statistik AI**
Halaman menampilkan: jumlah chat dengan auto-reply aktif, total balasan AI, permintaan AI hari ini (`TAiPermintaan`), jumlah hari libur aktif, dan jumlah penerima notifikasi (pengguna aktif dengan `NomorWhatsappInternal` terisi).

### 8.9 AI Agent — Auto Reply

`App\Services\Ai\AiAutoReplyService::handleIncomingChat()`.

**FR-AIR-01 — Prasyarat eksekusi**
Auto-reply HARUS berhenti tanpa efek bila: pengaturan tidak ada, `AutoReplyAktif=0`, chat tidak ditemukan, atau tidak ada pesan masuk dari customer dengan `IsiPesan` terisi.

**FR-AIR-02 — Anti balasan ganda**
Bila sudah ada pesan keluar `DihasilkanOlehAi=1` dengan `TglPesan >= ` pesan masuk terakhir, sistem HARUS melewati dengan alasan "Pesan terakhir sudah dijawab AI."

**FR-AIR-03 — Prioritas CS manusia**
`ProcessAiAutoReplyJob` HARUS membatalkan auto-reply bila sudah ada pesan keluar **non-AI** dengan `TglPesan >= ` pesan masuk terakhir (CS sudah membalas duluan).

**FR-AIR-04 — Pohon keputusan mode balasan**
Urutan evaluasi pada `replyDecision()`:

| Urut | Kondisi | Mode | Template |
| --- | --- | --- | --- |
| 1 | Hari ini hari libur **dan** `AutoReplyHariLibur=1` | `Hari Libur` | `TemplateHariLibur` (placeholder diisi) |
| 2 | Di luar jam kerja **dan** `AutoReplyDiluarJamKerja=1` | `Luar Jam Kerja` | `TemplateDiluarJamKerja` |
| 3 | `TChat.AutoReplyAiAktif=1` **atau** `AutoReplyJamKerjaBerlanjut=1` | `Berlanjut` | `TemplateFallback` |
| 4 | `AutoReplyJamKerjaSapaan=1` **dan** `AiSudahMenyapa=0` | `Sapaan Jam Kerja` | `TemplateJamKerjaSapaan` |
| 5 | selain itu | `Skip` | — (alasan dicatat) |

**FR-AIR-05 — Evaluasi hari libur**
Hari libur cocok bila `MHariLibur` aktif dengan `TanggalLibur` = tanggal hari ini **atau** `BerlakuTahunan=1` dengan bulan+tanggal sama. Perhitungan memakai zona waktu `MPengaturanAi.ZonaWaktu`.

**FR-AIR-06 — Placeholder template hari libur**
`{nama_hari_libur}`, `{tanggal_libur}`, `{tanggal_masuk_kerja}` HARUS diisi. Tanggal masuk kerja adalah hari kerja berikutnya (sesuai `HariKerja`) yang bukan hari libur, dicari maksimal 60 hari ke depan; bila tidak ditemukan, memakai teks "hari kerja berikutnya". Format tanggal Indonesia: `Hari, D Bulan YYYY`.

**FR-AIR-07 — Evaluasi jam kerja**
Di dalam jam kerja bila hari ini termasuk `HariKerja` (ISO 1–7) **dan** waktu sekarang berada di antara `JamKerjaMulai` dan `JamKerjaSelesai` (inklusif) pada zona waktu pengaturan.

**FR-AIR-08 — Penyusunan prompt**
Prompt terdiri dari: `PromptSistem`, konteks customer (nama instansi → nama customer → "Belum dipetakan"), jenis chat, instruksi mode, template sebagai arah (bukan kalimat wajib), knowledge internal yang relevan, riwayat chat, dan instruksi penutup yang melarang mengarang fakta/prosedur/harga/jadwal.

- Riwayat: `BatasRiwayatPesan` pesan terakhir (dibatasi 1–20), diurutkan kronologis, dengan label pembicara `AI Agent` / `Customer Service` / nama kontak / `Customer`.

**FR-AIR-09 — Retrieval knowledge**
`relevantKnowledge()` HARUS:
- Mode `Nonaktif` → tidak menyertakan knowledge.
- Mode `Ringan` → maksimal 5 item (kustom ≤10), total ≤3.500 karakter, per item ≤900 karakter, hanya item dengan skor ≥4.
- Mode `AllKnowledge` → maksimal 20 item (kustom ≤50), total ≤12.000 karakter, per item ≤1.200 karakter.
- Tokenisasi konteks: kata ≥4 karakter, membuang stopword (`yang, dari, untuk, dengan, atau, kami, saya, anda, bapak, ibu, halo, terima, kasih, pesan, customer, mohon, tolong, sudah, belum`).
- Skor: judul +5, tag +4, `SearchKeywords` +3, isi +1 per token; ditambah `PrioritasAi` bila kolom ada.
- Escape wildcard SQL Server (`%`, `_`, `[`) pada klausa LIKE.
- Bila kolom `JumlahDipakaiAi` ada, item yang dipakai diperbarui `TerakhirDipakaiAi` dan counter penggunaan.

**FR-AIR-10 — Pemilihan model**
- Balasan AI **pertama** pada suatu chat memakai `ModelInstructAi` (fallback `ModelAi`, fallback `services.openai.model`).
- Balasan berikutnya memakai `ModelAi`.

**FR-AIR-11 — Pemanggilan provider**
- `openai` → POST ke `BaseUrl` (default `https://api.openai.com/v1/responses`) dengan body `{model, instructions, input, store:true}`; teks diekstrak dari `output_text` atau `output[].content[].text|content`.
- `deepseek`, `openrouter`, `9router`/`ninerouter` → POST ke `{base}/chat/completions` dengan `messages` (system = `PromptSistem`, user = prompt) dan `stream:false`; teks dari `choices.0.message.content`.
- OpenRouter/9Router menambahkan header `HTTP-Referer` dan `X-Title`.
- Timeout 30 detik. HTTP gagal → `RuntimeException` berisi status + body.

**FR-AIR-12 — Fallback aman**
Bila provider gagal, key kosong, provider tidak didukung, atau respons kosong, sistem HARUS tetap mengirim **template** sebagai balasan, menandai `TAiPermintaan.StatusPermintaan='Gagal Fallback'`, dan mengisi `PesanError`.

**FR-AIR-13 — Audit permintaan/respons AI**
Setiap eksekusi HARUS menulis `TAiPermintaan` (jenis `Auto Reply WhatsApp` atau `Tutup Chat`, provider, model, `IdChat`, prompt ringkas ≤2000, prompt JSON, status, waktu mulai/selesai, error) dan `TAiRespon` (jenis = mode keputusan, respons ringkas, respons JSON).

**FR-AIR-14 — Penyimpanan dan pengiriman balasan**
`storeReply()` HARUS menulis `TChatD` keluar dengan `DihasilkanOlehAi=1` dan `IdAiRespon`. `StatusKirim`:
- `KirimKeWaha=0` → `Draft Auto Reply AI` (tidak dikirim ke WhatsApp).
- `KirimKeWaha=1` → `Terkirim WAHA` atau `Gagal WAHA` (+`PesanError`).

**FR-AIR-15 — Pembaruan status chat**
Setelah balasan tersimpan: `AiSudahMenyapa` di-set true bila mode `Sapaan Jam Kerja`; `TglAutoReplyAiTerakhir`, `TglDibalasTerakhir`, `TglChatTerakhir` = sekarang; `JumlahPesanBelumDibaca` = 0.

**FR-AIR-16 — Broadcast setelah auto-reply**
Bila auto-reply menghasilkan balasan (bukan skip), job HARUS memicu broadcast debounce agar inbox agent ter-refresh.

### 8.10 AI Knowledge Learning (Human-in-the-loop)

`App\Services\Ai\AiKnowledgeLearningService`, tabel `TAiDraftPengetahuan`, resource `DraftPengetahuanResource`.

**FR-LEARN-01 — Ekstraksi draft dari chat**
Sistem HARUS mengekstrak kandidat pengetahuan dari maksimal 40 pesan terakhir (minimal 2 pesan berisi teks), memanggil provider AI dengan instruksi output JSON ketat.

- Skema JSON yang diminta: `{layak, judul, isi, tag, kategori, confidence, ringkasan_sumber}`; bila tidak layak `{layak:false, alasan}`.
- Konteks dibatasi 12.000 karakter; cuplikan sumber disimpan maks 6.000 karakter.

**FR-LEARN-02 — Sanitasi data sensitif (wajib)**
Sebelum dikirim ke provider dan sebelum disimpan, teks HARUS disanitasi: email → `[email]`, nomor telepon → `[nomor]`, OTP/kode verifikasi → `[otp]`, password/token/api key/secret → `[rahasia]`, URL bertoken → `[url]`, angka 16 digit → `[nomor_identitas]`.

**FR-LEARN-03 — Ambang kualitas**
Draft ditolak bila judul <8 karakter atau isi <30 karakter.

**FR-LEARN-04 — Anti duplikat**
`HashKonten` = SHA-256 dari judul+isi (lowercase, squished). Draft dengan hash sama yang belum berstatus *rejected* menolak pembuatan draft baru.

**FR-LEARN-05 — Review manual sebelum aktif**
Draft disimpan berstatus `STATUS_DRAFT` dan **tidak** otomatis menjadi `MPengetahuan`. Promosi ke knowledge base wajib melalui review pengguna ber-permission `knowledge.manage`.

**FR-LEARN-06 — Sanitasi pesan error**
Pesan error yang ditampilkan HARUS menyensor pola `Bearer ...` dan `sk-...` menjadi `[secret]`.

### 8.11 VPoint Assistant (Chatbot Internal)

`App\Filament\Pages\VPointAssistant` + `App\Services\Ai\InternalChatbotService`, tabel `TChatbotInternal`. Permission `chatbot.access`.

**FR-BOT-01 — Tanya jawab internal**
Pengguna panel HARUS dapat bertanya (maks 4000 karakter) dan menerima jawaban AI berbasis knowledge base internal dan riwayat percakapannya sendiri (maks 20 pesan konteks).

**FR-BOT-02 — Mode respons dan knowledge**
- `response_mode`: `light` atau `fast` (default `fast`).
- `knowledge_mode`: `all` atau `none` (default `all`).
- Limit knowledge: `light` → 2 item, `fast` → 5 item.

**FR-BOT-03 — Lampiran**
Lampiran diperbolehkan maksimal 5 MB per berkas; metadata (`name`, `mime`, `size`) disimpan pada `KonteksJson`.

**FR-BOT-04 — Jawaban terstruktur**
Respons diparse menjadi `visible`, `reasoning`, dan `suggested_replies`; ketiganya disimpan pada `KonteksJson` pesan assistant.

**FR-BOT-05 — Isolasi data per pengguna**
Riwayat chatbot HARUS difilter per `IdPengguna`; pengguna tidak dapat melihat riwayat pengguna lain. Tersedia aksi hapus riwayat sendiri.

**FR-BOT-06 — Penanganan kegagalan**
Kegagalan provider dicatat `Log::warning` dengan pesan tersanitasi dan ditampilkan sebagai pesan ramah (`ui.chatbot.error_provider_failed`), bukan stack trace.

### 8.12 Histori Chat

`App\Filament\Pages\HistoriChat` (tabel Filament) + `ViewChatSession` (detail). Permission `chat_history.view`.

**FR-HIST-01 — Daftar sesi chat**
Menampilkan seluruh sesi chat (termasuk yang sudah ditutup) melalui tabel Filament dengan sumber `TChat` yang dijoin ke status, nomor, grup, instansi (chat & grup), customer, dan handler.

- Kolom tampilan memakai `COALESCE` berjenjang: nama kontak (nomor master → grup → chat), nomor tampilan (nomor grup → `IdGrupWaha` → nomor chat), instansi (instansi chat → instansi grup).
- Filter: rentang tanggal `TglChatTerakhir` (default 1 bulan terakhir s/d hari ini), jenis chat (`Pribadi`/`Grup`), dan status (`MStatusChat`).
- Urutan default `TglChatTerakhir` menurun; paginasi 10/25/50/100 dengan default 25.
- Aksi `open_session` membuka `/admin/view-chat-session?id={Id}` di tab baru.

**FR-HIST-02 — Detail sesi**
Halaman detail menampilkan seluruh pesan sesi beserta catatan internal, dan menyediakan aksi buat draft knowledge.

### 8.13 Ticketing

Halaman dashboard `App\Filament\Pages\Ticketing` + resource `TicketResource` (model `App\Models\Ticketing\Ticket`, tabel `TTicket`).

**FR-TIC-01 — Dashboard ticket**
Menampilkan dari database: jumlah ticket berstatus `BARU`, ticket aktif (status non-final), ticket overdue (non-final dengan `TglTargetSelesai < now`), ticket selesai (status final), jumlah ticket aktif milik pengguna login, dan 10 ticket terbaru (nomor, judul, target selesai, status, assignee).

**FR-TIC-02 — Penomoran ticket**
Nomor otomatis `TCK-YYYYMMDD-NNN` menggunakan counter `MNomorDokumen` di dalam transaksi dengan `sp_getapplock` (mode Exclusive, timeout 10 detik) pada SQL Server, retry 3 kali.

**FR-TIC-03 — CRUD ticket** (`ticket.manage` untuk create/edit/delete, `ticket.view` untuk lihat)
Field: `JudulTicket` (wajib, ≤255), `DeskripsiMasalah`, status (wajib), kategori, prioritas, assignee, `TglTargetSelesai`, customer, instansi. `NomorTicket` ditampilkan read-only dan tidak di-dehydrate.

**FR-TIC-04 — Audit pembuatan**
Saat ticket dibuat, sistem HARUS menulis `TTicketD` dengan `JenisAktivitas='Pembuatan'` berisi judul ticket.

**FR-TIC-05 — Histori perubahan status**
Setiap perubahan `IdStatusTicket` HARUS menulis `TTicketD` `JenisAktivitas='PerubahanStatus'` dengan `StatusSebelum` dan `StatusSesudah`.

**FR-TIC-06 — Otomatisasi status final**
Saat status berubah ke status dengan `StatusFinal=1`: `TglDitutup` dan `DitutupOleh` diisi, `TglSelesai` diisi bila masih kosong. Saat kembali ke status non-final, ketiganya dikosongkan.

**FR-TIC-07 — Histori penugasan**
Perubahan `DitugaskanKepada` (termasuk saat pembuatan) HARUS menulis `TTicketDPenugasan` (`DitugaskanDari`, `DitugaskanKepada`, `TglPenugasan`), dan `TglDitugaskan` pada header diperbarui.

**FR-TIC-08 — Notifikasi assignee**
Assignee baru HARUS menerima notifikasi database (`TicketAssignedNotification`) **hanya bila** memiliki permission `ticket.view`.

**FR-TIC-09 — Catatan progres**
Repeater `activities` memungkinkan penambahan catatan (`JenisAktivitas='Catatan'`) pada `TTicketD`.

**FR-TIC-10 — Lampiran privat**
Lampiran disimpan pada disk `attachments` (`storage/app/attachments`, visibility `private`), direktori `tickets`, maksimal **3 MB** (3072 KB), tipe yang diterima: `image/*`, `application/pdf`, `text/plain`, `.doc`, `.docx`. Metadata (`TipeFile`, `UkuranFile`) diisi otomatis.

**FR-TIC-11 — Unduh lampiran terkontrol**
Unduhan hanya melalui `GET /admin/attachments/tickets/{attachment}` dengan middleware `auth` **dan** `abort_unless(FilamentAccess::can('ticket.view'), 403)`. Respons streamed dengan `Cache-Control: private, no-store`; `404` bila baris atau berkas tidak ada.

**FR-TIC-12 — Filter dan tampilan tabel**
Filter status (`SelectFilter`) dan filter "Ticket Saya" (`DitugaskanKepada = Auth::id()`). Kolom `TglTargetSelesai` ditandai `danger` bila lewat target dan status belum final. Urutan default `TglBuat` menurun.

**FR-TIC-13 — Histori penugasan read-only di form**
Repeater `assignments` ditampilkan tidak dapat ditambah/dihapus/diurutkan dan seluruh field disabled.

### 8.14 Task

Resource `TaskResource` (model `App\Models\Ticketing\Task`, tabel `TTask`).

**FR-TSK-01 — Penomoran task**
Nomor otomatis `TSK-YYYYMMDD-NNN` dengan mekanisme counter yang sama seperti ticket.

**FR-TSK-02 — Relasi opsional**
Task dapat berdiri sendiri atau terkait `IdTicket`, `IdChat`, `IdCustomer`, `IdInstansi`, dan dapat memiliki induk `IdTugasInduk` (self-reference) untuk sub-task.

**FR-TSK-03 — Atribut task**
`JudulTask` (wajib ≤255), `DeskripsiTask`, `IdStatusTask` (wajib), kategori, prioritas, assignee, `TglTargetSelesai`, `EstimasiMenit`.

**FR-TSK-04 — Histori penugasan & notifikasi**
Sama seperti ticket: menulis `TTaskDPenugasan` dan mengirim `TaskAssignedNotification` hanya bila assignee memiliki `task.view`.

**FR-TSK-05 — Otomatisasi status final**
Perubahan ke status `StatusFinal=1` mengisi `TglDitutup`, `DitutupOleh`, `TglSelesai`; kembali ke non-final mengosongkannya.

**FR-TSK-06 — Checklist**
`TTaskDChecklist` menyimpan item (`JudulItem` ≤500, `Selesai`, `Urutan`, `TglSelesai`, `DiselesaikanOleh`), ditampilkan urut `Urutan`.

**FR-TSK-07 — Komentar**
`TTaskDKomentar` menyimpan komentar progres, ditampilkan urut `TglKomentar`.

**FR-TSK-08 — Lampiran & unduhan**
Sama seperti ticket (disk `attachments`, maks 3 MB) dengan route `GET /admin/attachments/tasks/{attachment}` yang memeriksa permission `task.view`.

**FR-TSK-09 — Filter "Task Saya"**
Filter menampilkan hanya task dengan `DitugaskanKepada = Auth::id()`.

### 8.15 Master Data

**FR-MD-01 — Resource master yang tersedia**
Sistem HARUS menyediakan resource Filament untuk: `MInstansi`, `MCustomer`, `MNomorWhatsapp`, `MGrupWhatsapp`, `MAnggotaGrupWhatsapp`, `MHariLibur`, `MPengetahuan`, `MPengguna`, `MHakAkses`, `MStatusTicket`, `MKategoriTicket`, `MPrioritasTicket`, `MStatusTask`, `TAiDraftPengetahuan` (draft knowledge), dan `job_schedules`.

**FR-MD-02 — Halaman ringkasan Master Customer**
`App\Filament\Pages\MasterCustomer` menampilkan jumlah instansi, kontak customer, nomor WhatsApp, grup, dan anggota grup yang aktif, plus daftar mapping nomor terbaru.

**FR-MD-03 — Soft-disable, bukan hapus**
Master data memakai kolom `NonAktif` sebagai penanda nonaktif; data nonaktif dikecualikan dari mapping webhook, retrieval knowledge, dan pilihan form.

**FR-MD-04 — Nomor WhatsApp**
Menyimpan nomor tampilan, `IdWaha` (identitas WAHA, mendukung `@c.us`/`@lid`), nama & jabatan kontak, penanda nomor utama, status verifikasi, serta relasi ke customer dan instansi.

**FR-MD-05 — Grup WhatsApp**
Menyimpan `IdGrupWaha` sebagai kunci pencocokan webhook grup dan `IdInstansi` sebagai sumber mapping instansi untuk seluruh chat grup tersebut.

**FR-MD-06 — Knowledge base**
`MPengetahuan` menyimpan judul, isi, tag, dan (bila kolom tersedia) `SearchKeywords`, `PrioritasAi`, `TerakhirDipakaiAi`, `JumlahDipakaiAi`. Entri aktif dipakai sebagai konteks AI.

**FR-MD-07 — Hari libur**
`MHariLibur` mendukung tanggal spesifik dan `BerlakuTahunan`; dievaluasi oleh auto-reply AI (FR-AIR-05) dan notifikasi chat belum terbalas (FR-NOTIF-02).

**FR-MD-08 — Master ticketing**
Status ticket/task, kategori, dan prioritas dikelola melalui resource khusus yang membutuhkan permission `ticket.manage`. `MPrioritasTicket.BatasSlaMenit` mendefinisikan SLA menit per prioritas; `StatusFinal` menandai status penutup.

### 8.16 Integrasi VToken

**FR-VT-01 — Command import**
`php artisan vpoint:import-instansi-vtoken` mendispatch `ImportVTokenCustomersToInstansi` ke queue; opsi `--sync` menjalankannya langsung (`Bus::dispatchSync`).

**FR-VT-02 — Sumber data**
Endpoint dibaca dari `VTOKEN_OPEN_CUSTOMERS_URL` (`config('services.vtoken.open_customers_url')`).

**FR-VT-03 — Upsert berdasarkan kode**
Data di-upsert ke `MInstansi` berdasarkan kode instansi/customer; kolom `SumberData`, `IdExternal`, dan `TglSinkronTerakhir` diisi untuk jejak sinkronisasi.

**FR-VT-04 — Penjadwalan**
Seeder menyediakan job schedule `Import Instansi VToken` (`everyFiveMinutes`) dalam keadaan **nonaktif** (`is_active=false`) agar tidak berjalan tanpa keputusan operasional.

### 8.17 Scheduler dan Job Schedule

**FR-SCH-01 — Scheduler berbasis database**
`routes/console.php` HARUS membaca `job_schedules` yang `is_active=true` dan mendaftarkan setiap `command` ke scheduler.

- Bila `cron_expression` mengandung `*`, diperlakukan sebagai ekspresi cron (`->cron(...)`).
- Selain itu diperlakukan sebagai nama method schedule Laravel (`everyMinute`, `everyFiveMinutes`, dst.).
- Setiap job memakai `withoutOverlapping()` dan `runInBackground()`.
- Kegagalan database saat bootstrap (mis. sebelum migrate) HARUS ditangkap dan diabaikan agar artisan tetap dapat dijalankan.

**FR-SCH-02 — Pengelolaan jadwal dari UI**
Resource `JobScheduleResource` (permission `job_schedule.view`) mengizinkan **edit** (termasuk toggle `is_active`) tetapi **melarang create dan delete** (`canCreate()`/`canDelete()` = false).

**FR-SCH-03 — Jadwal bawaan**
Seeder membuat: `vpoint:kirim-notifikasi-chat-belum-terbalas` (`everyMinute`, aktif) dan `vpoint:import-instansi-vtoken` (`everyFiveMinutes`, nonaktif).

**FR-SCH-04 — Command serve kustom**
`php artisan serve:vpoint` menjalankan server pada `APP_SERVE_HOST`/`APP_SERVE_PORT` (default `127.0.0.1:8008`) tanpa timeout.

### 8.18 Notifikasi Chat Belum Terbalas

`App\Console\Commands\KirimNotifikasiChatBelumTerbalas` + `App\Services\Ai\ChatBelumTerbalasNotifier`.

**FR-NOTIF-01 — Prasyarat**
Notifikasi HARUS dilewati bila pengaturan `DEFAULT` tidak ada atau `NotifikasiChatBelumTerbalasAktif=0`.

**FR-NOTIF-02 — Batas jadwal**
Notifikasi HARUS dilewati bila saat ini di luar hari kerja, di luar jam kerja, atau hari libur aktif (`dilewati_jadwal=1`), dan command melaporkan alasan tersebut.

**FR-NOTIF-03 — Penerima**
Penerima adalah `MPengguna` aktif dengan `NomorWhatsappInternal` terisi dan role termasuk `KodePeranPenerimaNotifikasi` (default `ADMIN,SUPERVISOR_CS,CS`).

**FR-NOTIF-04 — Kriteria chat**
Chat dianggap belum terbalas bila balasan **CS non-AI** terakhir lebih lama dari pesan masuk customer terakhir (atau belum pernah ada), pesan masuk terakhir sudah ≥ `MenitTungguNotifikasi`, dan chat belum dinotifikasi dalam `JedaNotifikasiMenit` terakhir. Maksimal **20** chat per eksekusi, diurutkan dari yang paling lama menunggu.

**FR-NOTIF-05 — Pesan notifikasi**
Template `TemplateNotifikasiChatBelumTerbalas` mendukung placeholder: `{nama_user}`, `{nama_instansi}`, `{jenis_chat}`, `{nama_kontak}`, `{nomor_whatsapp}`, `{pesan_terakhir}` (dipotong 180 karakter), `{menit_menunggu}`, `{url_admin}` (`APP_URL` + `/admin/inbox-whatsapp`).

**FR-NOTIF-06 — Pengiriman & pencatatan**
Notifikasi dikirim via WAHA memakai sesi `WAHA_NOTIFICATION_SESSION` dengan kode integrasi `WAHA_NOTIF_CHAT_BELUM_DIBALAS`. Setelah diproses, `TChat.TglNotifikasiBelumTerbalasTerakhir` diperbarui dan `JumlahNotifikasiBelumTerbalas` bertambah 1.

**FR-NOTIF-07 — Output command**
Command melaporkan jumlah chat diperiksa, penerima, terkirim, dan gagal.

**FR-NOTIF-08 — Notifikasi database**
Notifikasi penugasan ticket/task memakai channel database Laravel (tabel `notifications` dengan `notifiable_id` bertipe `uniqueidentifier`) dan tampil pada bell notification Filament.

### 8.19 Log Data dan Observability

Halaman `App\Filament\Pages\LogData`, permission `log_data.view`.

**FR-LOG-01 — Log integrasi**
Menampilkan 30 baris `TLogIntegrasi` terbaru: kode integrasi, URL, metode, request/response (dipotong), status HTTP, penanda berhasil, pesan error, waktu request/response.

**FR-LOG-02 — Log webhook**
Menampilkan `TLogWebhookWaha` terbaru: jenis event, status proses, pesan error, waktu diterima/diproses.

**FR-LOG-03 — Status queue**
Menampilkan ringkasan tabel `jobs` (pending per queue), `failed_jobs` (jumlah + daftar), dan `job_batches`.

**FR-LOG-04 — Ketahanan tabel opsional**
Halaman HARUS memeriksa `Schema::hasTable('failed_jobs')` sebelum query agar tidak error pada instalasi tanpa tabel tersebut.

**FR-LOG-05 — Kebijakan logging aplikasi**
Log aplikasi HARUS mencatat kegagalan webhook, kegagalan auto-reply, kegagalan proxy media, dan pembukaan/penutupan circuit breaker WAHA (`Log::critical` saat terbuka, `Log::info` saat tertutup).

### 8.20 Lokalisasi

**FR-I18N-01 — Bahasa yang didukung**
Sistem HARUS mendukung `id` (default) dan `en`, dikonfigurasi pada `config/localization.php`.

**FR-I18N-02 — Pergantian locale**
`GET /locale/{locale}` (`LocaleController`) HARUS menyimpan locale ke session (`wacs_locale`) dan cookie (`wacs_locale`, 1 tahun) lalu kembali ke halaman sebelumnya. Nilai locale divalidasi terhadap daftar `supported`.

**FR-I18N-03 — Penerapan locale**
Middleware `SetLocale` (terdaftar pada panel Filament) HARUS menerapkan locale dari session/cookie pada setiap request panel.

**FR-I18N-04 — Format tanggal per locale**
`LocaleFormatter` HARUS memformat tanggal/waktu sesuai locale: ID `d M Y` / `d M Y H:i:s` / input `d-m-Y`; EN `M j, Y` / `M j, Y H:i:s` / input `m-d-Y`.

**FR-I18N-05 — Label permission dwibahasa**
`MHakAkses` menyimpan `NamaHakAksesId`/`NamaHakAksesEn`, `ModulId`/`ModulEn`, `KeteranganId`/`KeteranganEn`; `AccessPermissions::localizedColumnNames()` memilih kolom sesuai locale aktif.

**FR-I18N-06 — Larangan hardcode**
String UI baru HARUS ditambahkan ke `resources/lang/id/ui.php` dan `resources/lang/en/ui.php` (saat ini masing-masing 920 baris) dan dipanggil via `__()`.

**FR-I18N-07 — Anti auto-translate**
Panel menyisipkan `<meta name="google" content="notranslate">`, atribut `translate="no"`, dan class `notranslate` pada `<html>`/`<body>` untuk mencegah terjemahan otomatis browser merusak istilah teknis.

### 8.21 Tampilan dan Branding

**FR-UI-01 — Identitas panel**
Panel `admin` pada path `/admin` memakai brand `APP_NAME`, logo `images/logo_primary.svg` (terang) dan `logo_secondary.svg` (gelap), tinggi logo `2.25rem`, favicon logo primary.

**FR-UI-02 — Palet warna**
`primary`=Indigo, `danger`=Rose, `gray`=Gray, `info`=Cyan, `success`=Green, `warning`=Orange.

**FR-UI-03 — Layout**
Lebar konten penuh (`Width::Full`), halaman simple `Width::Large`, sidebar collapsible/fully-collapsible di desktop, lebar sidebar `18rem` (collapsed `4.75rem`).

**FR-UI-04 — Dark mode**
Seluruh komponen kustom HARUS memiliki varian gelap (didefinisikan pada blok style panel dan `resources/css/filament/admin/theme.css`).

**FR-UI-05 — Elemen global**
Locale switcher pada `TOPBAR_END`; footer copyright pada seluruh halaman **kecuali** `admin/v-point-assistant`; font DM Sans + Playfair Display via bunny.net.

### 8.22 Ringkasan Kontrak Route

Route publik/aplikasi (`routes/web.php`) yang **tidak boleh** berubah tanpa proposal OpenSpec:

| Method | Path | Nama | Proteksi |
| --- | --- | --- | --- |
| GET | `/` | — | publik (landing) |
| GET | `/locale/{locale}` | `locale.switch` | publik, locale divalidasi |
| GET | `/auth/{provider}/redirect` | `external-auth.redirect` | provider ∈ {google, sso} |
| GET | `/auth/{provider}/callback` | `external-auth.callback` | provider ∈ {google, sso} |
| POST | `/webhooks/waha/{token?}` | `webhooks.waha` | token + HMAC opsional, throttle 100/menit |
| GET | `/admin/waha-media/{message}` | `admin.waha-media.show` | `auth` |
| GET | `/profile-storage/{path}` | `public-storage.show` | publik (path tervalidasi) |
| GET | `/admin/attachments/tickets/{attachment}` | `admin.attachments.tickets.download` | `auth` + `ticket.view` |
| GET | `/admin/attachments/tasks/{attachment}` | `admin.attachments.tasks.download` | `auth` + `task.view` |
| — | `/admin/*` | panel Filament | `Authenticate` + permission per halaman |

Broadcast channel: `waha-inbox` (publik), `waha-agents` (privat).

---

## 9. Requirement Non-Fungsional

### 9.1 Performa

| ID | Requirement |
| --- | --- |
| NFR-PERF-01 | Endpoint webhook HARUS merespons tanpa menunggu pemrosesan (dispatch ke queue), sehingga latensi respons independen dari beban parsing/AI |
| NFR-PERF-02 | Query daftar inbox dibatasi 50 baris; thread pesan dibatasi 200 baris; tren dashboard dibatasi 31 titik; log dibatasi 30 baris |
| NFR-PERF-03 | Pengaturan AI di-cache 5 menit; metadata schema di-cache selamanya (`SchemaCache`); struktur navigasi di-cache dengan invalidasi eksplisit (`NavigationHelper::flush()`) |
| NFR-PERF-04 | Broadcast di-debounce 2 detik per chat untuk mencegah badai event saat pesan beruntun |
| NFR-PERF-05 | Timeout eksternal dibatasi: WAHA POST 20s, WAHA GET 8s, media proxy 45s, AI auto-reply 30s, AI knowledge extraction 45s |
| NFR-PERF-06 | Index scalability (Bagian 7.4) HARUS ada pada database production |

### 9.2 Skalabilitas dan Ketahanan

| ID | Requirement |
| --- | --- |
| NFR-SCALE-01 | Pemrosesan berat berjalan pada queue terpisah (`webhooks`, `ai-replies`, `broadcasts`) sehingga dapat diberi worker/konkurensi berbeda |
| NFR-SCALE-02 | Circuit breaker WAHA mencegah cascading failure saat gateway down (5 kegagalan → cooldown 120 detik) |
| NFR-SCALE-03 | Job memiliki batas retry eksplisit (`ProcessWebhookJob` 3, `ProcessAiAutoReplyJob` 2) dan handler `failed()` |
| NFR-SCALE-04 | Reverb mendukung horizontal scaling via Redis (`REVERB_SCALING_ENABLED`) |
| NFR-SCALE-05 | Kegagalan AI tidak boleh menggagalkan alur chat — sistem selalu memiliki fallback template |

### 9.3 Keamanan

| ID | Requirement |
| --- | --- |
| NFR-SEC-01 | Webhook diverifikasi dengan token (`hash_equals`, timing-safe) dan HMAC-SHA512 opsional atas raw body |
| NFR-SEC-02 | API key AI disimpan terenkripsi (`Crypt::encryptString`) dan tidak pernah dikirim ke browser atau ditulis ke log |
| NFR-SEC-03 | Pesan error yang ditampilkan ke pengguna HARUS disensor dari API key/token (`sanitizeSecretText`, `safeError`) |
| NFR-SEC-04 | Lampiran ticket/task disimpan di disk privat di luar `public/` dan hanya dapat diunduh via route terautentikasi + berpermission |
| NFR-SEC-05 | Media WAHA di-proxy melalui aplikasi (route `auth`), bukan URL WAHA yang diekspos ke browser |
| NFR-SEC-06 | Path traversal dicegah pada `PublicStorageController` (`..`, null byte) |
| NFR-SEC-07 | Otorisasi ditegakkan di sisi server pada setiap aksi mutasi (`abort_unless`), bukan hanya menyembunyikan UI |
| NFR-SEC-08 | Ekstraksi knowledge dari chat wajib disanitasi dari PII/kredensial sebelum dikirim ke provider AI |
| NFR-SEC-09 | Chat customer tidak dipakai untuk fine-tuning otomatis; knowledge baru wajib melalui review manusia |
| NFR-SEC-10 | `.env` tidak boleh di-commit; `APP_DEBUG=false` di production; HTTPS wajib; `APP_FORCE_HTTPS` + `TRUSTED_PROXIES` diatur bila di belakang proxy |
| NFR-SEC-11 | Rate limit: webhook 100/menit; auth eksternal `EXTERNAL_AUTH_RATE_LIMIT` (default 10) |
| NFR-SEC-12 | Password akun seed awal WAJIB diganti setelah instalasi pertama |
| NFR-SEC-13 | OAuth/OIDC memakai `state` + `nonce` per sesi dan validasi domain email |

### 9.4 Kompatibilitas Database

| ID | Requirement |
| --- | --- |
| NFR-DB-01 | Seluruh SQL HARUS kompatibel Microsoft SQL Server; sintaks/perilaku MySQL/PostgreSQL tidak boleh diasumsikan |
| NFR-DB-02 | Migration schema WACS HARUS menolak eksekusi pada koneksi selain `sqlsrv` |
| NFR-DB-03 | `src/script/DATABASE_SCHEMA_WACS.sql` HARUS ikut dipublish ke production karena dieksekusi oleh migration |
| NFR-DB-04 | Penambahan objek pada schema SQL HARUS idempotent (`IF OBJECT_ID(...) IS NULL`, `IF NOT EXISTS (SELECT 1 FROM sys.indexes ...)`) agar aman untuk database existing |
| NFR-DB-05 | Konvensi UUID dan nama tabel/kolom legacy HARUS dipertahankan |
| NFR-DB-06 | Rangkaian write yang harus atomik memakai `DB::transaction()` |

### 9.5 Observability dan Auditability

| ID | Requirement |
| --- | --- |
| NFR-OBS-01 | Setiap request keluar (WAHA, VToken) tercatat lengkap di `TLogIntegrasi` |
| NFR-OBS-02 | Setiap payload webhook tersimpan mentah di `TLogWebhookWaha` beserta status proses |
| NFR-OBS-03 | Setiap panggilan AI tercatat di `TAiPermintaan`/`TAiRespon` termasuk model, prompt ringkas, dan error |
| NFR-OBS-04 | Perubahan status dan penugasan ticket/task tercatat pada tabel histori terpisah |
| NFR-OBS-05 | Alasan skip auto-reply HARUS dikembalikan/dicatat agar dapat diaudit |

### 9.6 Pemeliharaan dan Kualitas Kode

| ID | Requirement |
| --- | --- |
| NFR-MNT-01 | Perubahan non-trivial WAJIB direncanakan lewat `openspec/changes/<change-slug>/` (proposal + delta spec + tasks) sebelum implementasi |
| NFR-MNT-02 | Format kode PHP mengikuti Laravel Pint (`vendor/bin/pint`) |
| NFR-MNT-03 | Validasi berjenjang: `php -l` → `php artisan test --filter=...` → `php artisan test` → `pint --test` → `npm run build` |
| NFR-MNT-04 | Kolom/tabel baru yang opsional HARUS diakses melalui pemeriksaan schema agar aman untuk database lama |
| NFR-MNT-05 | Dilarang mengubah `src/vendor/`, generated asset, atau lock file tanpa alasan tercatat di proposal |

---

## 10. Integrasi Eksternal

### 10.1 WAHA (WhatsApp HTTP API)

| Aspek | Detail |
| --- | --- |
| Arah masuk | `POST {APP_URL}/webhooks/waha/{token}` |
| Header verifikasi | `X-Webhook-Hmac`, `X-Webhook-Hmac-Algorithm: sha512` (opsional) |
| Arah keluar | `POST {WAHA_BASE_URL}/api/sendText` (path dari `WAHA_SEND_TEXT_PATH`) |
| | `POST /api/sendImage`, `/api/sendVideo`, `/api/sendFile` |
| | `GET /api/contacts/profile-picture?contactId&session&refresh` |
| | `GET /api/{session}/lids/{lid}` |
| Autentikasi keluar | Header `X-Api-Key` bila `WAHA_API_KEY` diisi |
| Format JID | `@c.us` (kontak), `@g.us` (grup), `@s.whatsapp.net` (dinormalisasi ke `@c.us`), `@lid` (di-resolve ke nomor) |

### 10.2 Provider AI

| Provider | Endpoint default | Format |
| --- | --- | --- |
| OpenAI | `https://api.openai.com/v1/responses` | Responses API (`instructions` + `input`, `store`) |
| DeepSeek | `https://api.deepseek.com/chat/completions` | Chat Completions |
| OpenRouter | `https://openrouter.ai/api/v1/chat/completions` | Chat Completions + header `HTTP-Referer`, `X-Title` |
| 9Router | `https://openrouter.ai/api/v1/chat/completions` (dapat di-override) | Chat Completions + header `HTTP-Referer`, `X-Title` |

Resolusi API key: kolom terenkripsi di `MPengaturanAi` → gagal dekripsi/kosong → `config('services.<provider>.api_key')` dari environment.

### 10.3 VToken

| Aspek | Detail |
| --- | --- |
| Endpoint | `VTOKEN_OPEN_CUSTOMERS_URL` (contoh `https://vtoken.vpoint.my.id/api/open/customers`) |
| Arah | Keluar (aplikasi menarik data) |
| Target | Upsert `MInstansi` berdasarkan kode instansi |
| Pemicu | Command manual, `--sync`, atau job schedule |

### 10.4 Identity Provider (Google / SSO OIDC)

| Aspek | Detail |
| --- | --- |
| Protokol | OAuth 2.0 / OpenID Connect (`response_type=code`, `scope=openid email profile`) |
| Konfigurasi | `config/external-auth.php` (enabled, client id/secret, authorize/token/userinfo/issuer URL, redirect URI, allowed domains) |
| Keamanan | `state` + `nonce` per sesi, validasi domain email, `lockForUpdate` saat pembuatan/penautan identitas |

---

## 11. Konfigurasi Environment

### 11.1 Aplikasi

| Variabel | Keterangan |
| --- | --- |
| `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` | Dasar Laravel. `APP_KEY` production **tidak boleh** di-generate ulang (merusak data terenkripsi) |
| `APP_FORCE_HTTPS`, `TRUSTED_PROXIES` | Untuk deployment di belakang reverse proxy SSL |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_TIMEZONE` | Default `id`, `id`, `Asia/Jakarta` |
| `APP_SERVE_HOST`, `APP_SERVE_PORT` | Dipakai `php artisan serve:vpoint` (default `127.0.0.1:8008`) |

### 11.2 Database, queue, cache, session

| Variabel | Nilai yang didukung |
| --- | --- |
| `DB_CONNECTION` | **wajib** `sqlsrv` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Koneksi SQL Server (port default 1433) |
| `QUEUE_CONNECTION` | `database` (README/production) atau `redis` (`.env.example`) |
| `CACHE_STORE` | `database` atau `redis` |
| `SESSION_DRIVER` | `database` atau `redis` |
| `REDIS_*` | Diperlukan bila memakai driver redis |

> **Catatan konsistensi:** `README.md` mendokumentasikan `database` untuk queue/cache/session, sedangkan `src/.env.example` berisi `redis`. Keduanya didukung kode; pilihan harus ditetapkan per environment dan dicatat di runbook deployment.

### 11.3 WAHA

`WAHA_BASE_URL`, `WAHA_MEDIA_BASE_URL`, `WAHA_API_KEY`, `WAHA_WEBHOOK_TOKEN`, `WAHA_WEBHOOK_HMAC_KEY`, `WAHA_SEND_TEXT_PATH`, `WAHA_NOTIFICATION_SESSION`.

### 11.4 AI

`OPENAI_API_KEY` / `OPENAI_MODEL` / `OPENAI_BASE_URL`; `DEEPSEEK_*`; `OPENROUTER_*` (termasuk `SITE_URL`, `SITE_NAME`); `NINEROUTER_*`.

### 11.5 Realtime

`BROADCAST_CONNECTION` (`reverb` untuk realtime, `log` untuk development tanpa realtime), `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`, `REVERB_SCALING_ENABLED`, serta pasangan `VITE_REVERB_*` yang harus sesuai domain/port yang diakses browser.

> Perubahan `VITE_*` memerlukan `npm run build` ulang karena nilainya di-inline saat build.

### 11.6 Autentikasi eksternal

`GOOGLE_AUTH_ENABLED`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `GOOGLE_ALLOWED_DOMAINS`; `SSO_AUTH_ENABLED`, `SSO_DISPLAY_NAME`, `SSO_PROVIDER`, `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`, `SSO_ISSUER_URL`, `SSO_AUTHORIZE_URL`, `SSO_TOKEN_URL`, `SSO_USERINFO_URL`, `SSO_REDIRECT_URI`, `SSO_ALLOWED_DOMAINS`; `EXTERNAL_REGISTRATION_ENABLED`, `EXTERNAL_REGISTRATION_DEFAULT_STATUS`, `EXTERNAL_AUTH_RATE_LIMIT`.

### 11.7 Lain-lain

`VTOKEN_OPEN_CUSTOMERS_URL`, `FILESYSTEM_DISK`, `LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_KB` (51200), `LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_TIME`.

---

## 12. Deployment dan Operasional

### 12.1 Prasyarat server

PHP 8.3+ dengan ekstensi `sqlsrv`, `pdo_sqlsrv`, `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`, `zip`, `intl`; Composer 2; Node.js/npm (untuk build); SQL Server; ODBC Driver for SQL Server; web server dengan document root `src/public`; process manager (NSSM/Supervisor/systemd/Windows Service); WAHA yang stabil.

### 12.2 Prosedur publish

```powershell
cd <path-to-repo>
git pull
cd src
php artisan down
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force        # WAJIB setelah backup database
php artisan db:seed --force        # idempotent: role, permission, menu, master ticketing, job schedule
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
php artisan up
```

Restart layanan queue worker, scheduler, dan Reverb bila process manager tidak melakukannya otomatis.

### 12.3 Direktori writable

`src/storage` dan `src/bootstrap/cache` harus writable oleh user web server. Disk `attachments` menulis ke `src/storage/app/attachments`.

### 12.4 Checklist verifikasi pasca-deploy

1. `/admin` dapat dibuka dan login berhasil.
2. Asset termuat dari `public/build`.
3. Migration selesai tanpa error.
4. Queue worker aktif (cek Log Data → status queue).
5. Scheduler aktif.
6. Reverb aktif bila realtime dipakai.
7. Webhook WAHA menerima pesan masuk (cek `TLogWebhookWaha`).
8. Agent dapat mengirim balasan WhatsApp (cek `TLogIntegrasi`).
9. AI Agent hanya aktif bila API key dan pengaturan sudah benar.
10. Lampiran ticket/task dapat diunduh oleh role yang berhak dan ditolak untuk yang tidak berhak.

### 12.5 Runbook singkat

| Gejala | Langkah pertama |
| --- | --- |
| Pesan WhatsApp tidak masuk | Cek URL/token webhook di WAHA, konektivitas WAHA→aplikasi, `TLogWebhookWaha`, dan apakah queue worker berjalan |
| Balasan gagal terkirim | Cek `TLogIntegrasi` (status HTTP + body), sesi WAHA login, `WAHA_SEND_TEXT_PATH`, `WAHA_API_KEY`, dan apakah circuit breaker terbuka |
| Realtime tidak jalan | Cek `BROADCAST_CONNECTION=reverb`, proses `reverb:start`, kesesuaian `VITE_REVERB_*` dengan domain browser, firewall port WS, dan apakah asset sudah di-build ulang |
| AI tidak membalas | Cek `AutoReplyAktif`, validitas API key, jam kerja/hari libur, nomor pengecualian, `KirimKeWaha`, dan `TAiPermintaan.PesanError` |
| Tidak bisa konek SQL Server | Cek ekstensi `sqlsrv`/`pdo_sqlsrv` di PHP CLI **dan** PHP web server, ODBC driver, kredensial, dan TCP/IP SQL Server |

---

## 13. Alur Pengguna Utama (User Journey)

### 13.1 Pesan masuk → balasan agent

1. Customer mengirim pesan WhatsApp ke nomor bisnis.
2. WAHA POST ke `/webhooks/waha/{token}`; aplikasi memvalidasi token/HMAC dan langsung membalas `queued`.
3. `ProcessWebhookJob` menyimpan log, mem-parse pesan, mengecek status broadcast/nomor pengecualian/duplikat, memetakan customer, dan menyimpan `TChat`/`TChatD`.
4. Broadcast debounce mengirim `.inbox.updated`; inbox agent yang terbuka memuat ulang.
5. Agent membuka chat → chat otomatis diklaim (`DiambilOleh`) → melihat konteks instansi/customer dan riwayat percakapan terkait.
6. Agent membalas (teks atau lampiran) → `WahaSender` mengirim ke WAHA → status disimpan ke `TChatD` → counter belum dibaca direset.

### 13.2 Pesan masuk → auto-reply AI

1. Langkah 1–3 identik dengan 13.1.
2. `ProcessAiAutoReplyJob` mengecek apakah CS sudah membalas; bila sudah, berhenti.
3. `AiAutoReplyService` mengevaluasi pohon keputusan (hari libur / luar jam kerja / berlanjut / sapaan / skip).
4. Bila boleh, prompt disusun dari pengaturan + konteks customer + knowledge + riwayat; provider dipanggil dengan model instruct (balasan pertama) atau model utama.
5. Hasil disimpan sebagai `TChatD` keluar; dikirim ke WhatsApp bila `KirimKeWaha` aktif, atau disimpan sebagai draft.
6. Inbox agent ter-refresh via broadcast.

### 13.3 Chat → ticket → task

1. Agent mengidentifikasi masalah yang butuh eskalasi.
2. Agent membuat ticket (nomor `TCK-YYYYMMDD-NNN`), mengisi judul/deskripsi/kategori/prioritas/target selesai, dan menugaskan ke developer.
3. Sistem mencatat aktivitas `Pembuatan`, mencatat penugasan, dan mengirim notifikasi database ke assignee (jika berhak).
4. Developer membuat task turunan (nomor `TSK-YYYYMMDD-NNN`) dengan checklist dan komentar progres.
5. Perubahan status dicatat; saat status final, tanggal selesai/tutup terisi otomatis.
6. Dashboard Ticketing menampilkan ticket baru/aktif/overdue/selesai secara realtime dari database.

### 13.4 Chat tidak terbalas → notifikasi internal

1. Scheduler menjalankan `vpoint:kirim-notifikasi-chat-belum-terbalas` sesuai `job_schedules`.
2. Sistem memeriksa jam kerja, hari kerja, dan hari libur; bila di luar jadwal, dilewati.
3. Chat yang balasan CS-nya lebih lama dari pesan masuk terakhir dan sudah menunggu ≥ `MenitTungguNotifikasi` dikumpulkan (maks 20).
4. Notifikasi WhatsApp dikirim ke pengguna internal berdasarkan role penerima.
5. Counter dan waktu notifikasi terakhir per chat diperbarui untuk menegakkan jeda.

### 13.5 Chat → knowledge base

1. Agent/supervisor menekan "Buat Draft Knowledge" pada chat yang mengandung prosedur reusable.
2. Sistem menyanitasi PII, memanggil AI, dan meminta output JSON terstruktur.
3. Draft disimpan ke `TAiDraftPengetahuan` berstatus draft (bukan knowledge aktif) dengan pengecekan duplikat berbasis hash.
4. Supervisor mereview, mengedit, lalu mempromosikan draft menjadi `MPengetahuan`.
5. Knowledge aktif otomatis dipakai sebagai konteks auto-reply dan VPoint Assistant.

---

## 14. Aturan Bisnis Terkonsolidasi

| ID | Aturan |
| --- | --- |
| BR-01 | Chat berstatus `DITUTUP` tidak pernah dipakai ulang; pesan masuk berikutnya membuat percakapan baru |
| BR-02 | Chat baru dari webhook selalu berstatus `MENUNGGU_CS` |
| BR-03 | Satu `IdPesanWaha` hanya boleh menghasilkan satu baris `TChatD` |
| BR-04 | Status broadcast WhatsApp tidak pernah masuk inbox |
| BR-05 | Nomor pada daftar pengecualian tidak masuk inbox dan tidak dapat dijadikan target "mulai chat" |
| BR-06 | Balasan CS manusia selalu mengalahkan auto-reply AI untuk pesan masuk yang sama |
| BR-07 | AI tidak membalas dua kali untuk pesan masuk terakhir yang sama |
| BR-08 | Saat `KirimKeWaha` nonaktif, seluruh output AI berhenti sebagai draft lokal |
| BR-09 | Kegagalan AI tidak menghentikan balasan — template menjadi fallback |
| BR-10 | Catatan internal tidak pernah dikirim ke WhatsApp |
| BR-11 | Nomor ticket/task unik dan berurutan per hari |
| BR-12 | Status final ticket/task mengisi tanggal selesai/tutup; kembali ke non-final mengosongkannya |
| BR-13 | Notifikasi penugasan hanya dikirim ke pengguna yang memiliki permission view atas modulnya |
| BR-14 | Lampiran maksimal 3 MB dan hanya dapat diunduh oleh pengguna berpermission |
| BR-15 | Chat tanpa handler otomatis diklaim oleh agent pertama yang membukanya |
| BR-16 | Notifikasi chat belum terbalas hanya dikirim pada jam kerja, hari kerja, dan bukan hari libur |
| BR-17 | Knowledge dari chat tidak pernah aktif tanpa persetujuan manusia |
| BR-18 | Data master dinonaktifkan (`NonAktif`), bukan dihapus |
| BR-19 | Pengguna tidak dapat menonaktifkan akunnya sendiri |
| BR-20 | Balasan AI pertama pada sebuah chat memakai model instruct; berikutnya memakai model utama |

---

## 15. Batasan, Technical Debt, dan Temuan

Temuan berikut berasal dari pembacaan source code aktual dan **perlu ditindaklanjuti melalui OpenSpec** sebelum diperbaiki. Tidak ada perubahan kode yang dilakukan saat menyusun PRD ini.

### 15.1 Defect fungsional

| ID | Lokasi | Temuan | Dampak |
| --- | --- | --- | --- |
| TD-01 | `app/Services/Ai/AiAutoReplyService.php:110` vs `:123` | `$isFirstReply` dipakai pada insert `TAiPermintaan.ModelAi` sebelum variabelnya didefinisikan pada baris 123 | Kolom `ModelAi` pada log permintaan mencatat model yang salah (selalu jalur non-first-reply) dan memicu warning "undefined variable". Model yang benar-benar dipakai tetap benar karena `generateReply()` dipanggil setelah variabel terisi |
| TD-02 | `AiAutoReplyService::generateReply()` | `generateChatCompletionReply()` dipanggil tanpa meneruskan `$isFirstReply` | `ModelInstructAi` tidak pernah berlaku untuk DeepSeek/OpenRouter/9Router — fitur model instruct efektif hanya untuk OpenAI |
| TD-03 | `app/Filament/Pages/InboxWhatsapp.php:324-325` dan `app/Filament/Pages/ViewChatSession.php:153,161` | Query memakai tabel `Pengguna`, bukan `MPengguna` (4 kemunculan di 2 file) | Nama pembuat catatan internal **selalu** jatuh ke label "system" di kedua halaman. Tidak crash karena dijaga `Schema::hasTable` yang selalu `false`, tetapi datanya hilang. Ditambah N+1 query karena lookup dilakukan per baris catatan |

### 15.2 Duplikasi dan kode mati

| ID | Lokasi | Temuan |
| --- | --- | --- |
| TD-04 | `AiAutoReplyService::latestIncomingWahaChatId()`, `normalizeWahaChatId()` | Duplikat dari `WahaChatHelper`; tidak dipanggil lagi (jalur aktif memakai helper) |
| TD-05 | `WahaWebhookProcessor::resolveLidPhoneNumber()`, `normalisasiNomorWhatsapp()` | Duplikat dari `WahaChatHelper`; tidak terpakai |
| TD-06 | `database/migrations/` | Dua migration dengan tujuan sama: `2026_05_06_000006_add_multilanguage_columns_to_hak_akses.php` dan `2026_05_06_000006_add_multilingual_columns_to_hak_akses.php` |
| TD-07 | `src/` root | Skrip ad-hoc ter-commit: `test_login.php`, `test_query.php`, `test_simpan_pengaturan_full.php`, `test_storage_writable.php` — bukan bagian test suite dan berpotensi tereksekusi bila web root salah konfigurasi |

### 15.3 Cakupan test

| ID | Temuan |
| --- | --- |
| TD-08 | Test suite hanya berisi `tests/Unit/TicketTaskSupportTest.php` dan dua `ExampleTest`. Tidak ada test untuk webhook processor, auto-reply decision tree, permission gating, penomoran dokumen konkuren, maupun circuit breaker — padahal semuanya adalah logika bisnis kritis |

### 15.4 Inkonsistensi dokumentasi/konfigurasi

| ID | Temuan |
| --- | --- |
| TD-09 | `README.md` menyebut queue/cache/session `database`; `.env.example` memakai `redis`. Perlu satu keputusan resmi per environment |
| TD-10 | `README.md` menyebut file schema di `src/DATABASE_SCHEMA_WACS.sql`; lokasi aktual adalah `src/script/DATABASE_SCHEMA_WACS.sql` (sesuai `AGENTS.md`) |
| TD-11 | Seeder `MStatusTicket` (`BARU/ANALISA/DIKERJAKAN/SELESAI/DITUTUP`) berbeda dari seed di `DATABASE_SCHEMA_WACS.sql` (`DRAFT/BARU/DIANALISA_CS/.../DIBATALKAN`); demikian juga `MKategoriTicket`. Database hasil install akan memiliki gabungan keduanya |
| TD-12 | Kredensial akun seed (`mrthx.89@gmail.com` / password) tertulis di `README.md` dan `DatabaseSeeder`. Harus diganti setelah instalasi dan sebaiknya dipindahkan ke variabel environment |

### 15.5 Batasan arsitektur yang disadari

| ID | Batasan |
| --- | --- |
| TD-13 | Circuit breaker WAHA berbasis properti **statis per proses**; tidak dibagikan antar worker/server. Efektivitasnya terbatas pada deployment multi-worker |
| TD-14 | Channel broadcast `waha-inbox` bersifat **publik** — siapa pun yang tahu kredensial WebSocket dapat mengetahui adanya update chat (payload hanya `chat_id`, tanpa isi pesan) |
| TD-15 | Daftar inbox dibatasi 50 chat tanpa paginasi; percakapan lama hanya dapat dijangkau melalui Histori Chat |
| TD-16 | Auto-claim chat tidak memiliki mekanisme lepas/alih-tangan eksplisit di UI inbox |
| TD-17 | Retrieval knowledge memakai LIKE + skor kata kunci, bukan vektor/embedding; relevansi menurun pada knowledge base besar |
| TD-18 | SLA (`MPrioritasTicket.BatasSlaMenit`) belum dipakai untuk menghitung `TglTargetSelesai` otomatis maupun eskalasi |

---

## 16. Backlog dan Roadmap

### 16.1 Change OpenSpec yang sudah ada

| Change slug | Ruang lingkup | Status terhadap kode |
| --- | --- | --- |
| `add-task-and-ticketing-module` | Modul Ticketing fungsional + modul Task | Terimplementasi (commit `3c10872`) |
| `add-9router-ai-agent` | Provider 9Router + test koneksi + ikon provider | Terimplementasi |
| `add-google-sso-auth` | Login/registrasi Google & SSO | Terimplementasi |
| `add-reviewed-ai-learning` | Draft knowledge human-in-the-loop | Terimplementasi |
| `scalability-optimization-and-chatbot` | Async webhook, index, cache, circuit breaker, VPoint Assistant | Terimplementasi |
| `add-model-instruct` / `add-ai-instruct-model` / `ai-model-instruct-and-ui-improvements` | Pemisahan model utama vs model instruct | Terimplementasi untuk OpenAI; lihat TD-02 |
| `audit-ai-agent-light-outline-ui` | Perapihan UI/UX light outline | Terimplementasi |

### 16.2 Prioritas yang diusulkan

**P0 — Perbaikan korektif**
1. Perbaiki TD-01 (urutan `$isFirstReply`) dan TD-02 (model instruct lintas provider).
2. Perbaiki TD-03 (tabel `Pengguna` → `MPengguna`).
3. Selaraskan seed master ticketing (TD-11) agar tidak menghasilkan status ganda.
4. Hapus skrip test ad-hoc dari `src/` (TD-07) dan rapikan migration duplikat (TD-06).

**P1 — Kepercayaan dan keandalan**
5. Tambah test otomatis untuk: dedupe webhook, pohon keputusan auto-reply, permission gating, penomoran dokumen konkuren, circuit breaker (TD-08).
6. Pindahkan state circuit breaker ke cache bersama agar berlaku lintas worker (TD-13).
7. Pertimbangkan channel privat/presence untuk update inbox (TD-14).

**P2 — Peningkatan produk**
8. Paginasi/infinite scroll pada daftar inbox (TD-15).
9. Aksi lepas/alih-tangan chat eksplisit + indikator agent aktif (TD-16).
10. Perhitungan `TglTargetSelesai` otomatis dari `BatasSlaMenit` + penanda/eskalasi SLA breach (TD-18).
11. Retrieval knowledge berbasis embedding (TD-17).
12. Laporan periodik yang dapat diekspor (di luar scope saat ini).

---

## 17. Kriteria Penerimaan Rilis (QA Checklist)

### 17.1 Autentikasi & akses
- [ ] Login internal berhasil; pengguna nonaktif/pending ditolak.
- [ ] Login Google/SSO berhasil bila dikonfigurasi; domain di luar daftar ditolak.
- [ ] Setiap role bawaan hanya melihat menu sesuai permission-nya.
- [ ] Akses langsung ke URL halaman tanpa permission menghasilkan penolakan.
- [ ] Aksi mutasi tanpa permission menghasilkan HTTP 403 (bukan hanya tombol tersembunyi).

### 17.2 Webhook & inbox
- [ ] Webhook dengan token salah → 403 dan tidak ada perubahan data.
- [ ] Webhook dengan HMAC salah (bila diaktifkan) → 403.
- [ ] Pesan teks, gambar, video, audio, dokumen, dan stiker terklasifikasi benar.
- [ ] Pesan grup terpetakan ke instansi via `IdGrupWaha`.
- [ ] Pengirim `@lid` ter-resolve ke nomor telepon.
- [ ] Kiriman ganda dengan `IdPesanWaha` sama tidak menghasilkan duplikat.
- [ ] Status broadcast dan nomor pengecualian tidak masuk inbox.
- [ ] Inbox ter-refresh otomatis saat pesan baru masuk (Reverb aktif).
- [ ] Balasan teks dan lampiran terkirim; status `Terkirim WAHA` tercatat.
- [ ] Draft lokal tidak terkirim ke WhatsApp.
- [ ] Catatan internal tidak terkirim ke WhatsApp.
- [ ] Tutup percakapan mengubah status dan menghilangkan chat dari daftar aktif.
- [ ] Media pesan terbuka melalui route proxy dan menolak akses tanpa login.

### 17.3 AI
- [ ] Test koneksi berhasil untuk setiap provider terkonfigurasi dan tidak membuat baris `TChatD`.
- [ ] API key tidak pernah tampil di UI, log, atau pesan error.
- [ ] Auto-reply hari libur memakai template hari libur dengan placeholder terisi.
- [ ] Auto-reply di luar jam kerja memakai template luar jam kerja.
- [ ] Sapaan jam kerja hanya sekali per chat sampai direset.
- [ ] Mode draft lokal tidak mengirim apa pun ke WhatsApp.
- [ ] CS yang membalas duluan membatalkan auto-reply.
- [ ] Kegagalan provider menghasilkan fallback template dan `Gagal Fallback` pada `TAiPermintaan`.
- [ ] Draft knowledge tersanitasi dari email/nomor/OTP/token.

### 17.4 Ticket & task
- [ ] Nomor ticket/task berurutan dan unik, termasuk saat pembuatan bersamaan.
- [ ] Perubahan status tercatat di `TTicketD` dengan status sebelum/sesudah.
- [ ] Perubahan assignee tercatat dan menghasilkan notifikasi hanya untuk pengguna berpermission.
- [ ] Lampiran >3 MB ditolak; lampiran tersimpan di disk privat.
- [ ] Unduhan lampiran ditolak untuk pengguna tanpa permission view.
- [ ] Filter "Ticket Saya"/"Task Saya" hanya menampilkan milik pengguna login.
- [ ] Ticket melewati target selesai ditandai sebagai overdue di dashboard.

### 17.5 Scheduler & notifikasi
- [ ] Menonaktifkan job schedule menghentikan eksekusinya.
- [ ] Notifikasi chat belum terbalas dilewati di luar jam kerja/hari libur.
- [ ] Jeda notifikasi dipatuhi (tidak spam ke chat yang sama).
- [ ] Import VToken `--sync` menghasilkan upsert `MInstansi` yang benar.

### 17.6 Lokalisasi & non-fungsional
- [ ] Pergantian `/locale/id` dan `/locale/en` mengubah seluruh label yang didukung.
- [ ] Format tanggal berubah sesuai locale pada dashboard dan tabel.
- [ ] Halaman utama tampil benar pada mode terang dan gelap.
- [ ] Migration berjalan pada database kosong (fresh) dan database existing.
- [ ] `php artisan test`, `vendor/bin/pint --test`, dan `npm run build` lulus.

---

## 18. Glosarium

| Istilah | Arti |
| --- | --- |
| **WACS** | WhatsApp Customer Service — kode internal produk |
| **WAHA** | WhatsApp HTTP API, gateway self-hosted penghubung WhatsApp ↔ aplikasi |
| **JID** | WhatsApp identifier: `@c.us` (kontak), `@g.us` (grup), `@s.whatsapp.net` (format alternatif), `@lid` (linked identity anonim) |
| **`@lid`** | Identitas WhatsApp yang menyembunyikan nomor asli; harus di-resolve ke nomor melalui API WAHA |
| **Sesi (`MSesiWhatsapp`)** | Instance koneksi WhatsApp pada WAHA, diidentifikasi `KodeSesi` (umumnya `default`) |
| **`KirimKeWaha`** | Sakelar global: balasan AI dikirim ke WhatsApp (true) atau hanya disimpan sebagai draft (false) |
| **Mode auto-reply** | `Hari Libur`, `Luar Jam Kerja`, `Berlanjut`, `Sapaan Jam Kerja`, `Skip` |
| **Model instruct** | Model AI terpisah (`ModelInstructAi`) untuk balasan pertama/asisten internal, umumnya lebih cepat/murah |
| **Draft lokal** | Balasan tersimpan di sistem tanpa dikirim ke customer |
| **Instansi (`MInstansi`)** | Organisasi/klien VPoint, disinkronkan dari VToken |
| **Human-in-the-loop RAG** | Pola knowledge: AI mengusulkan, manusia menyetujui, baru dipakai sebagai konteks |
| **Circuit breaker** | Mekanisme menghentikan sementara panggilan ke layanan yang berulang kali gagal |
| **Debounce broadcast** | Penekanan event realtime beruntun dalam jendela waktu singkat |
| **OpenSpec** | Standar perencanaan perubahan repo ini (`openspec/changes/<slug>/`) |
| **`NonAktif`** | Penanda soft-disable pada seluruh master data |
| **`StatusFinal`** | Penanda bahwa status ticket/task bersifat penutup |

---

## Lampiran A — Peta Modul ke Source Code

| Modul | File utama |
| --- | --- |
| Panel & navigasi | `app/Providers/Filament/AdminPanelProvider.php`, `app/Support/NavigationHelper.php`, `app/Support/AccessPermissions.php`, `app/Support/FilamentAccess.php`, `app/Support/FilamentBreadcrumbs.php` |
| Autentikasi | `app/Auth/PenggunaUserProvider.php`, `app/Models/Master/Pengguna.php`, `app/Filament/Auth/{Login,Register}.php`, `app/Services/Auth/ExternalAuthService.php`, `app/Http/Controllers/Auth/ExternalAuthController.php`, `app/Http/Responses/Auth/*` |
| Webhook | `app/Http/Controllers/Webhook/WahaWebhookController.php`, `app/Jobs/ProcessWebhookJob.php`, `app/Services/Waha/WahaWebhookProcessor.php`, `app/Support/WahaChatHelper.php` |
| Pengiriman WAHA | `app/Services/Waha/WahaSender.php` |
| Inbox | `app/Filament/Pages/InboxWhatsapp.php`, `resources/views/filament/pages/inbox-whatsapp.blade.php`, `app/Services/Chat/ChatInitiationService.php`, `app/Events/WahaInboxUpdated.php`, `app/Jobs/SendBroadcastDebouncedJob.php` |
| Media | `app/Http/Controllers/WahaMediaController.php`, `app/Http/Controllers/PublicStorageController.php` |
| AI Agent | `app/Filament/Pages/AiAgent.php`, `app/Services/Ai/AiAutoReplyService.php`, `app/Jobs/ProcessAiAutoReplyJob.php`, `app/Support/AiSettings.php` |
| AI Learning | `app/Services/Ai/AiKnowledgeLearningService.php`, `app/Models/Ai/DraftPengetahuan.php`, `app/Filament/Resources/Ai/DraftPengetahuans/*` |
| Chatbot internal | `app/Filament/Pages/VPointAssistant.php`, `app/Services/Ai/InternalChatbotService.php`, `app/Models/ChatbotMessage.php` |
| Dashboard | `app/Filament/Pages/Dashboard.php`, `resources/views/filament/pages/dashboard.blade.php` |
| Ticketing | `app/Filament/Pages/Ticketing.php`, `app/Filament/Resources/Operational/Tickets/*`, `app/Models/Ticketing/{Ticket,TicketActivity,TicketAssignment,TicketAttachment}.php` |
| Task | `app/Filament/Resources/Operational/Tasks/*`, `app/Models/Ticketing/{Task,TaskAssignment,TaskAttachment,TaskChecklist,TaskComment}.php` |
| Master ticketing | `app/Filament/Resources/Ticketing/*`, `app/Models/Ticketing/{StatusTicket,StatusTask,KategoriTicket,PrioritasTicket}.php` |
| Lampiran | `app/Http/Controllers/Ticketing/AttachmentController.php`, `config/filesystems.php` (disk `attachments`) |
| Penomoran | `app/Services/Ticketing/TicketTaskSupport.php`, tabel `MNomorDokumen` |
| Master data | `app/Filament/Resources/Master/*`, `app/Models/Master/*`, `app/Filament/Pages/MasterCustomer.php` |
| Histori chat | `app/Filament/Pages/HistoriChat.php`, `app/Filament/Pages/ViewChatSession.php`, `app/Models/ChatSession.php` |
| Log & monitoring | `app/Filament/Pages/LogData.php` |
| VToken | `app/Console/Commands/ImportInstansiVToken.php`, `app/Jobs/ImportVTokenCustomersToInstansi.php` |
| Scheduler | `routes/console.php`, `app/Models/JobSchedule.php`, `app/Filament/Resources/Settings/JobScheduleResource.php` |
| Notifikasi | `app/Console/Commands/KirimNotifikasiChatBelumTerbalas.php`, `app/Services/Ai/ChatBelumTerbalasNotifier.php`, `app/Notifications/{TicketAssignedNotification,TaskAssignedNotification}.php` |
| Lokalisasi | `config/localization.php`, `app/Support/{LocaleManager,LocaleFormatter}.php`, `app/Http/Middleware/SetLocale.php`, `app/Http/Controllers/LocaleController.php`, `resources/lang/{id,en}/ui.php` |
| Schema | `src/script/DATABASE_SCHEMA_WACS.sql`, `database/migrations/*`, `database/seeders/DatabaseSeeder.php` |

## Lampiran B — Referensi Dokumen Terkait

- `README.md` — instalasi, konfigurasi, deployment, troubleshooting.
- `AGENTS.md` — aturan kerja, standar OpenSpec, aturan teknis WACS, definition of done.
- `openspec/project.md` — konteks produk, stack, aturan development & deployment.
- `openspec/specs/vpoint-care/spec.md` — spec kemampuan dan acceptance criteria (format GIVEN/WHEN/THEN).
- `openspec/changes/*/` — proposal, delta spec, dan tasks per perubahan.
- `docs/PLAN_*.md` — dokumen perencanaan historis per fitur (chatbot scalability, ticketing/task, SSO, 9Router, deployment Docker/Nginx/Laragon, dsb).
- `docs/BUG-ANALISIS-DUPLICATE-TCHAT.md` — analisis bug duplikasi chat.
