# Requirements Document

## Introduction

Inbox WhatsApp pada tab "WhatsApp Asli" mengalami waktu loading sangat lama ketika pengguna mengklik kontak untuk membuka rincian chat. Performa loading ini menjadi bottleneck pengalaman user dan menghambat produktivitas agent customer service dalam menangani percakapan WhatsApp. Requirement ini berfokus pada optimasi performa loading chat history, query database, dan rendering frontend agar chat terbuka dengan cepat.

## Glossary

- **Inbox_WhatsApp**: Panel admin Filament untuk menampilkan daftar chat dan rincian percakapan WhatsApp customer.
- **Chat_Loading**: Proses pengambilan data chat, message history, internal notes, dan history chats ketika user mengklik kontak dari daftar inbox.
- **Tab_WhatsApp_Asli**: Tab filter yang menampilkan identitas WhatsApp asli (raw contact name, group JID, nomor Whatsapp) tanpa mapping internal.
- **TChatD**: Tabel transaksi detail chat yang menyimpan setiap pesan masuk dan keluar pada satu percakapan.
- **TChat**: Tabel transaksi header chat yang menyimpan metadata percakapan seperti customer, status, timestamp terakhir.
- **Query_Performance**: Waktu eksekusi query database dari saat method dipanggil hingga data dikembalikan ke Livewire.
- **Rendering_Performance**: Waktu browser untuk me-render komponen Blade setelah menerima data dari Livewire.
- **Database_Index**: Index SQL Server pada kolom tabel untuk mempercepat pencarian dan sorting.

## Requirements

### Requirement 1: Identifikasi Bottleneck Loading Chat

**User Story:** Sebagai developer, saya ingin mengidentifikasi penyebab lambatnya loading chat, sehingga saya dapat menentukan optimasi yang tepat untuk mempercepat performa.

#### Acceptance Criteria

1. WHEN agent mengklik kontak pada Inbox WhatsApp, THE System SHALL mencatat timing breakdown dari query database, post-processing PHP, dan rendering Livewire
2. THE System SHALL menyediakan benchmark test untuk method `selectChat()` dengan chat yang memiliki 50, 100, dan 200 pesan
3. WHEN query `selectChat()` dieksekusi, THE System SHALL mengukur waktu eksekusi query TChatD, loadHistoryChats, dan loadInternalNotes secara terpisah
4. THE System SHALL menghasilkan report yang menunjukkan komponen mana yang menyumbang waktu loading terbesar (database query, PHP map, atau Livewire hydration)

### Requirement 2: Optimalkan Query Database TChatD

**User Story:** Sebagai agent, saya ingin chat history terbuka dengan cepat, sehingga saya dapat langsung membaca dan membalas pesan customer tanpa menunggu lama.

#### Acceptance Criteria

1. WHEN query TChatD dijalankan pada `selectChat()`, THE System SHALL memiliki index pada kolom `IdChat` dan `TglPesan`
2. THE System SHALL memastikan query TChatD.leftJoin(MPengguna) menggunakan index join yang efisien
3. WHEN chat memiliki lebih dari 200 pesan, THE System SHALL membatasi hasil query dengan `LIMIT 200` untuk mencegah over-fetch
4. THE Query_Builder SHALL menggunakan `DB::table()` tanpa Eloquent N+1 problem
5. WHEN Schema::hasColumn() dipanggil lebih dari satu kali per request, THE System SHALL menggunakan cache static untuk mengurangi metadata query
6. THE System SHALL menghindari multiple schema check dengan menyimpan hasil `hasColumn()` dalam property static class

### Requirement 3: Reduksi Post-Processing PHP pada Message Map

**User Story:** Sebagai developer, saya ingin mengurangi overhead post-processing setelah query, sehingga data dapat dikirim ke frontend lebih cepat.

#### Acceptance Criteria

1. WHEN method `selectChat()` melakukan `map()` pada collection message, THE System SHALL memproses hanya data yang diperlukan untuk rendering pertama
2. THE System SHALL menghindari inspeksi media payload untuk pesan yang tidak memiliki media
3. WHEN `WahaMediaPayload::inspectPayload()` dipanggil, THE System SHALL melakukan lazy evaluation hanya jika `UrlMedia`, `TipeMime`, atau `PayloadJson` terisi
4. THE System SHALL menghindari duplikasi call `inspectPayload()` pada payload yang sama dalam satu request
5. WHEN message tidak memiliki media, THE System SHALL skip pemrosesan `base64TextPayload`, `mediaPresentationCategory`, dan `mediaLabel`

### Requirement 4: Implementasi Pagination atau Lazy Loading Message

**User Story:** Sebagai agent, saya ingin melihat pesan terbaru dengan cepat, sehingga saya tidak perlu menunggu seluruh history chat ter-load sebelum dapat mulai membaca.

#### Acceptance Criteria

1. WHEN agent membuka chat pertama kali, THE System SHALL memuat maksimal 50 pesan terbaru
2. WHEN agent scroll ke atas untuk melihat pesan lebih lama, THE System SHALL memuat 50 pesan tambahan secara lazy
3. THE System SHALL menyediakan Livewire method `loadMoreMessages()` yang dapat dipanggil dari frontend
4. WHEN `loadMoreMessages()` dipanggil, THE System SHALL menambahkan pesan lama ke awal array `$this->messages` tanpa menghapus pesan yang sudah ada
5. THE Frontend SHALL menampilkan indikator loading saat pesan tambahan sedang dimuat
6. THE System SHALL menghindari infinite scroll jika seluruh message sudah dimuat dengan menandai property `$allMessagesLoaded`

### Requirement 5: Optimalkan Query loadHistoryChats

**User Story:** Sebagai agent, saya ingin sidebar history chat tidak memperlambat loading chat utama, sehingga saya dapat membuka chat dengan cepat.

#### Acceptance Criteria

1. WHEN `loadHistoryChats()` dijalankan, THE System SHALL memiliki index pada kolom `TChat.TglChatTerakhir` untuk sorting
2. THE System SHALL memiliki index pada kolom `TChat.IdCustomer`, `TChat.IdInstansi`, dan `TChat.IdNomorWhatsapp` untuk filter
3. WHEN history chat diquery, THE System SHALL membatasi hasil dengan `LIMIT 20` untuk mengurangi data transfer
4. THE System SHALL menggunakan select minimal (4-6 kolom) tanpa memuat seluruh payload chat
5. WHEN filter orWhere memiliki banyak kondisi, THE System SHALL memastikan SQL Server dapat menggunakan index seek bukan table scan

### Requirement 6: Optimalkan Rendering Frontend Blade

**User Story:** Sebagai agent, saya ingin UI chat langsung ter-render tanpa lag setelah data diterima, sehingga saya dapat langsung berinteraksi dengan chat.

#### Acceptance Criteria

1. WHEN Livewire mengirim data message ke Blade, THE System SHALL mengirim data terstruktur yang siap render tanpa post-processing di JavaScript
2. THE Frontend SHALL menggunakan `x-for` Alpine.js dengan `:key` yang stabil untuk menghindari re-render seluruh list
3. WHEN message baru ditambahkan, THE System SHALL hanya render elemen message baru tanpa re-render seluruh chat
4. THE System SHALL menghindari inline style computation yang berat pada setiap message bubble
5. WHEN chat memiliki banyak media preview, THE System SHALL menggunakan lazy loading image dengan `loading="lazy"` attribute
6. THE Frontend SHALL menghindari nested loop atau computation di dalam `x-for` yang dapat memperlambat Alpine.js reactivity

### Requirement 7: Monitoring dan Logging Performance

**User Story:** Sebagai developer, saya ingin dapat memantau performa loading chat secara realtime, sehingga saya dapat mendeteksi regression atau bottleneck baru.

#### Acceptance Criteria

1. WHEN agent membuka chat, THE System SHALL mencatat timing `selectChat()` ke log dengan level `debug`
2. THE System SHALL mencatat query time TChatD, loadHistoryChats, dan loadInternalNotes secara terpisah
3. WHEN query time melebihi threshold 500ms, THE System SHALL mencatat warning log dengan query SQL dan parameter
4. THE System SHALL menyediakan Artisan command `php artisan vpoint:benchmark-inbox` untuk mengukur performa loading chat dengan data sampel
5. THE Benchmark_Command SHALL menghasilkan report rata-rata waktu loading untuk 10, 50, 100, dan 200 pesan per chat
6. THE System SHALL menampilkan timing breakdown di Laravel Debugbar saat `APP_DEBUG=true`

### Requirement 8: Database Index Creation Migration

**User Story:** Sebagai developer, saya ingin membuat migration index yang aman untuk production, sehingga performa loading chat dapat ditingkatkan tanpa downtime.

#### Acceptance Criteria

1. THE Migration SHALL membuat index `IX_TChatD_IdChat_TglPesan` pada kolom `TChatD(IdChat, TglPesan)` dengan `CREATE INDEX IF NOT EXISTS`
2. THE Migration SHALL membuat index `IX_TChat_IdCustomer` pada kolom `TChat(IdCustomer)` jika belum ada
3. THE Migration SHALL membuat index `IX_TChat_IdInstansi` pada kolom `TChat(IdInstansi)` jika belum ada
4. THE Migration SHALL membuat index `IX_TChat_IdNomorWhatsapp` pada kolom `TChat(IdNomorWhatsapp)` jika belum ada
5. THE Migration SHALL membuat index `IX_TChat_TglChatTerakhir` pada kolom `TChat(TglChatTerakhir DESC)` untuk sorting history
6. THE Migration SHALL menggunakan syntax SQL Server yang kompatibel dengan `DB::statement()` tanpa mengasumsikan MySQL/PostgreSQL
7. WHEN migration dijalankan pada database production, THE System SHALL dapat rollback index dengan `DROP INDEX IF EXISTS` tanpa kehilangan data

### Requirement 9: Test Performance Regression Prevention

**User Story:** Sebagai developer, saya ingin memastikan perubahan kode di masa depan tidak memperlambat loading chat, sehingga performa tetap terjaga.

#### Acceptance Criteria

1. THE System SHALL menyediakan performance test `InboxWhatsappPerformanceTest` yang memverifikasi waktu loading chat
2. WHEN test performance dijalankan, THE System SHALL membuat chat fixture dengan 100 pesan dan mengukur waktu `selectChat()`
3. THE Test SHALL gagal jika waktu eksekusi `selectChat()` melebihi threshold 1000ms pada environment test SQLite
4. THE Test SHALL gagal jika query TChatD menghasilkan N+1 query atau lebih dari 5 query total
5. THE System SHALL menyediakan assertion helper `assertChatLoadsWithin(int $milliseconds, string $chatId)` untuk reusable performance check
6. WHEN perubahan kode meningkatkan jumlah query database, THE CI_Pipeline SHALL gagal dengan pesan yang jelas tentang regression

