# Requirements Document

## Introduction

Feature ini mencakup tiga kelompok perubahan pada aplikasi VPoint Care (Laravel + Filament + Livewire):

**Kelompok A — Model Instruct AI:** Menambahkan jalur model kedua (`ModelInstructAi`) pada pengaturan AI, yang digunakan khusus oleh VPoint Assistant (mode Ringan), opsi suggested replies, dan jawaban pertama sesi Inbox WhatsApp. Model Utama (`ModelAi`) tetap dipakai oleh `AiAutoReplyService`, test koneksi, dan mode Cepat, tanpa perubahan apapun pada alur yang sudah berjalan.

**Kelompok B — Tiga Bug Fix UI VPoint Assistant:** Menghapus shadow berlebih di area input bawah, membatasi textarea pesan ke max-height ~200px mengikuti pola ChatGPT, dan memperbaiki perilaku suggested replies agar hanya muncul sesaat setelah AI membalas — bukan saat load history atau setelah user memilih salah satu opsi.

**Kelompok C — Perbaikan UI PromptSistem dan Template di AI Agent:** Mengubah textarea PromptSistem menjadi auto-grow dengan Alpine.js (min-height 120px, tanpa resize handle), dan mempercompact layout template pesan dari `min-h-60` menjadi `min-h-[80px]` agar layout dua kolom di desktop terasa lebih proporsional.

---

## Glossary

- **MPengaturanAi**: Tabel SQL Server yang menyimpan pengaturan AI Agent, satu baris dengan `KodePengaturan = 'DEFAULT'`.
- **ModelAi**: Kolom yang sudah ada di `MPengaturanAi`, menyimpan nama model AI utama. Tidak diganti nama. Label UI berubah menjadi "Model Utama".
- **ModelInstructAi**: Kolom baru `nvarchar(100) NULL` yang ditambahkan ke `MPengaturanAi` melalui migrasi. Menyimpan nama model AI khusus untuk operasi instruct/ringan.
- **Model Utama**: Label UI untuk kolom `ModelAi`. Dipakai untuk auto-reply customer, test koneksi, dan mode Cepat VPoint Assistant.
- **Model Instruct**: Label UI untuk kolom `ModelInstructAi`. Dipakai untuk mode Ringan VPoint Assistant, suggested replies, dan jawaban pertama sesi Inbox WhatsApp. Fallback ke Model Utama jika kosong.
- **VPoint Assistant**: Halaman chatbot internal (`VPointAssistant.php`) dengan dua mode respons: Ringan (`light`) dan Cepat (`fast`).
- **Mode Ringan (light)**: Mode respons VPoint Assistant yang menggunakan `ModelInstructAi` (fallback ke `ModelAi` jika kosong). Jawaban lebih singkat dan langsung.
- **Mode Cepat (fast)**: Mode respons VPoint Assistant yang menggunakan `ModelAi`. Jawaban lebih lengkap.
- **Suggested Replies**: Daftar opsi tindak lanjut yang dihasilkan AI dan ditampilkan di bawah area input setelah AI membalas.
- **InternalChatbotService**: Service PHP (`InternalChatbotService.php`) yang menangani logika AI untuk VPoint Assistant.
- **AiAutoReplyService**: Service PHP yang menangani auto-reply WhatsApp customer. Tidak diubah dalam feature ini.
- **AiAgent**: Halaman pengaturan AI (`AiAgent.php` + `ai-agent.blade.php`) tempat admin mengkonfigurasi provider, model, dan template.
- **loadHistory()**: Method di `VPointAssistant.php` yang memuat riwayat percakapan saat halaman dimuat (`mount()`).
- **useSuggestedReply()**: Method di `VPointAssistant.php` yang menangani klik pada salah satu suggested reply.
- **sendMessage()**: Method di `VPointAssistant.php` yang mengirim pesan dan memproses respons AI.
- **PromptSistem**: Field textarea di halaman AI Agent untuk mengatur system prompt AI auto-reply.
- **Migrasi Kondisional (Conditional DDL)**: Migrasi Laravel yang menggunakan pengecekan `Schema::hasColumn` sebelum menjalankan DDL, aman untuk dijalankan berulang kali di SQL Server.
- **Translation Key**: Kunci terjemahan dalam format `__('ui.pages.ai_agent.*')` yang harus ada di kedua file bahasa (`id` dan `en`).

---

## Requirements

### Requirement 1: Migrasi Kolom ModelInstructAi

**User Story:** Sebagai administrator database, saya ingin kolom `ModelInstructAi` ditambahkan ke tabel `MPengaturanAi` melalui migrasi yang aman, sehingga pengaturan model instruct dapat disimpan tanpa risiko error saat deployment ulang.

#### Acceptance Criteria

1. THE `Migrasi` SHALL menambahkan kolom `ModelInstructAi` bertipe `nvarchar(100)` yang `nullable` ke tabel `MPengaturanAi`.
2. WHEN `Migrasi` dijalankan dan kolom `ModelInstructAi` sudah ada di tabel `MPengaturanAi`, THEN THE `Migrasi` SHALL melewati langkah DDL tersebut tanpa error menggunakan pemeriksaan `Schema::hasColumn`.
3. THE `Migrasi` SHALL dapat dijalankan berulang kali pada lingkungan production SQL Server tanpa menghasilkan error duplikat kolom.
4. WHEN `Migrasi` dijalankan untuk pertama kali pada database yang belum memiliki kolom `ModelInstructAi`, THE `Migrasi` SHALL menambahkan kolom tersebut dengan nilai default `NULL`.

---

### Requirement 2: Pemilihan Model di InternalChatbotService

**User Story:** Sebagai developer, saya ingin `InternalChatbotService` memilih model AI yang tepat berdasarkan mode respons dan ketersediaan `ModelInstructAi`, sehingga VPoint Assistant menggunakan model yang sesuai untuk setiap konteks penggunaan.

#### Acceptance Criteria

1. WHEN `InternalChatbotService` menerima permintaan dengan `mode = 'light'`, THE `InternalChatbotService` SHALL menggunakan `ModelInstructAi` dari pengaturan sebagai model AI.
2. WHEN `InternalChatbotService` menerima permintaan dengan `mode = 'light'` dan kolom `ModelInstructAi` kosong atau null, THE `InternalChatbotService` SHALL menggunakan `ModelAi` sebagai fallback.
3. WHEN `InternalChatbotService` menerima permintaan dengan `mode = 'fast'`, THE `InternalChatbotService` SHALL menggunakan `ModelAi` dari pengaturan sebagai model AI.
4. WHEN `InternalChatbotService` menerima permintaan dengan `mode = 'fast'` dan `ModelAi` kosong, THE `InternalChatbotService` SHALL menggunakan model default dari konfigurasi provider.
5. THE `InternalChatbotService` SHALL selalu menggunakan `ModelInstructAi` (dengan fallback ke `ModelAi`) untuk menghasilkan suggested replies, terlepas dari mode respons yang dipilih user.
6. THE `AiAutoReplyService` SHALL TIDAK membaca atau menggunakan kolom `ModelInstructAi` dalam pemrosesan auto-reply WhatsApp customer.

---

### Requirement 3: Isolasi Model — AiAutoReplyService Tidak Berubah

**User Story:** Sebagai administrator sistem, saya ingin memastikan bahwa penambahan `ModelInstructAi` tidak mengubah perilaku `AiAutoReplyService` yang sudah berjalan, sehingga alur auto-reply customer WhatsApp tetap stabil.

#### Acceptance Criteria

1. THE `AiAutoReplyService` SHALL SELALU menggunakan `ModelAi` untuk semua operasi auto-reply customer WhatsApp.
2. THE `AiAutoReplyService` SHALL TIDAK membaca properti `ModelInstructAi` dari objek pengaturan AI.
3. WHEN fitur test koneksi AI dijalankan dari halaman AI Agent, THE `AiAgent` SHALL menggunakan `ModelAi` melalui `AiAutoReplyService` untuk mengirim permintaan test ke provider.
4. IF kolom `ModelInstructAi` diubah atau dihapus dari database, THEN THE `AiAutoReplyService` SHALL TETAP berjalan normal tanpa error menggunakan `ModelAi`.

---

### Requirement 4: Pengaturan UI Model Utama dan Model Instruct di AI Agent

**User Story:** Sebagai administrator AI, saya ingin melihat dan mengkonfigurasi Model Utama dan Model Instruct dari halaman AI Agent, sehingga saya dapat mengatur dua jalur model secara terpisah tanpa kebingungan.

#### Acceptance Criteria

1. THE `AiAgent` SHALL menampilkan field input bertanda "Model Utama" untuk kolom `ModelAi` pada formulir pengaturan AI.
2. THE `AiAgent` SHALL menampilkan field input bertanda "Model Instruct" untuk kolom `ModelInstructAi` pada formulir pengaturan AI, di samping field Model Utama.
3. WHEN pengguna mengosongkan field Model Instruct dan menyimpan pengaturan, THE `AiAgent` SHALL menyimpan nilai `NULL` untuk kolom `ModelInstructAi` di database.
4. WHEN pengguna memilih preset provider, THE `AiAgent` SHALL mengisi field Model Instruct hanya jika field tersebut masih kosong.
5. IF kolom `ModelInstructAi` belum ada di database saat halaman dimuat, THEN THE `AiAgent` SHALL menampilkan field Model Instruct dengan nilai kosong tanpa memunculkan error.
6. THE `AiAgent` SHALL menampilkan teks bantuan (help text) pada field Model Instruct menggunakan translation key `__('ui.pages.ai_agent.instruct_model_help')`.
7. THE `AiAgent` SHALL menampilkan label field Model Utama menggunakan translation key `__('ui.pages.ai_agent.primary_model')`.
8. THE `AiAgent` SHALL menampilkan label field Model Instruct menggunakan translation key `__('ui.pages.ai_agent.instruct_model')`.

---

### Requirement 5: Translation Keys Dua Bahasa

**User Story:** Sebagai developer yang mengelola internasionalisasi, saya ingin semua translation key baru untuk feature ini tersedia di kedua file bahasa (`id` dan `en`), sehingga UI dapat ditampilkan dengan benar dalam dua bahasa tanpa key yang hilang.

#### Acceptance Criteria

1. THE `TranslationSystem` SHALL memiliki key `ui.pages.ai_agent.primary_model` di file `id/ui.php` dan `en/ui.php` dengan konten yang sesuai bahasanya.
2. THE `TranslationSystem` SHALL memiliki key `ui.pages.ai_agent.primary_model_help` di file `id/ui.php` dan `en/ui.php` dengan konten yang sesuai bahasanya.
3. THE `TranslationSystem` SHALL memiliki key `ui.pages.ai_agent.instruct_model` di file `id/ui.php` dan `en/ui.php` dengan konten yang sesuai bahasanya.
4. THE `TranslationSystem` SHALL memiliki key `ui.pages.ai_agent.instruct_model_help` di file `id/ui.php` dan `en/ui.php` dengan konten yang sesuai bahasanya.
5. FOR ALL translation keys baru yang ditambahkan dalam feature ini, THE `TranslationSystem` SHALL memiliki key yang identik (nama yang sama) di KEDUA file bahasa `id` dan `en`.
6. WHEN aplikasi berjalan dengan locale `id`, THE `TranslationSystem` SHALL mengembalikan teks Bahasa Indonesia untuk semua key baru.
7. WHEN aplikasi berjalan dengan locale `en`, THE `TranslationSystem` SHALL mengembalikan teks Bahasa Inggris untuk semua key baru.

---

### Requirement 6: Bug Fix — Suggested Replies Hanya Muncul Setelah Respons AI Baru

**User Story:** Sebagai pengguna VPoint Assistant, saya ingin suggested replies hilang setelah saya memilih salah satu opsi dan mengirimkan pesan, dan tidak muncul kembali saat halaman di-refresh atau di-reload, sehingga antarmuka terasa bersih dan tidak membingungkan.

#### Acceptance Criteria

1. WHEN `VPointAssistant` memuat halaman (`mount()`), THE `VPointAssistant` SHALL menginisialisasi `$suggestedReplies` sebagai array kosong `[]` tanpa membaca data suggested replies dari riwayat percakapan.
2. WHEN `loadHistory()` dipanggil, THE `VPointAssistant` SHALL TIDAK mengisi `$suggestedReplies` dari data `suggested_replies` yang tersimpan di kolom `KonteksJson` riwayat pesan.
3. WHEN `sendMessage()` menerima respons sukses dari AI yang mengandung suggested replies, THE `VPointAssistant` SHALL mengisi `$suggestedReplies` dengan array opsi dari respons tersebut.
4. WHEN `useSuggestedReply()` dipanggil dengan salah satu opsi, THE `VPointAssistant` SHALL mereset `$suggestedReplies` menjadi array kosong `[]`.
5. AFTER `useSuggestedReply()` dipanggil, THE `VPointAssistant` SHALL mempertahankan `$suggestedReplies` sebagai `[]` hingga `sendMessage()` berikutnya menerima respons AI baru yang mengandung suggested replies.
6. WHEN respons AI dari `sendMessage()` tidak mengandung suggested replies (array kosong atau error), THE `VPointAssistant` SHALL menyetel `$suggestedReplies` menjadi `[]`.

---

### Requirement 7: Bug Fix — Penghapusan Shadow Berlebih di Area Input VPoint Assistant

**User Story:** Sebagai pengguna VPoint Assistant, saya ingin area input di bagian bawah tampak ringan tanpa shadow yang berlebihan, sehingga tampilan terasa lebih bersih dan konsisten dengan desain Filament.

#### Acceptance Criteria

1. THE `VPointAssistant` SHALL menampilkan container area input bawah tanpa shadow CSS (`box-shadow` / `shadow-*` Tailwind) pada elemen container utama area input.
2. THE `VPointAssistant` SHALL menampilkan tombol submit tanpa kelas `shadow-sm` atau shadow Tailwind lainnya yang menambahkan efek bayangan.
3. WHEN pengguna membuka halaman VPoint Assistant, THE `VPointAssistant` SHALL menampilkan area input yang terasa ringan secara visual tanpa efek bayangan yang menonjol.

---

### Requirement 8: Bug Fix — Textarea Input Pesan dengan Max-Height yang Tepat

**User Story:** Sebagai pengguna VPoint Assistant, saya ingin textarea input pesan tumbuh mengikuti konten saya hingga batas maksimum ~200px (sekitar 12rem), sehingga pola interaksi terasa seperti ChatGPT — tidak memenuhi hampir seluruh layar saat mengetik pesan panjang.

#### Acceptance Criteria

1. THE `VPointAssistant` SHALL menampilkan textarea input pesan yang tumbuh secara otomatis mengikuti tinggi konten saat pengguna mengetik (auto-grow behavior menggunakan Alpine.js).
2. THE `VPointAssistant` SHALL membatasi tinggi maksimum textarea input pesan tidak melebihi `200px` (atau setara `~12rem` / `max-h-[200px]`).
3. WHEN konten textarea melebihi batas tinggi maksimum, THE `VPointAssistant` SHALL mengaktifkan scrollbar vertikal pada textarea sehingga konten tetap dapat digulir.
4. THE `VPointAssistant` SHALL mengganti nilai `max-h-[60vh]` pada textarea input dengan `max-h-[200px]` atau nilai yang setara tidak lebih dari 200px.
5. THE `VPointAssistant` SHALL mempertahankan perilaku `x-on:input` dan `x-effect` Alpine.js untuk auto-resize textarea, dengan perhitungan tinggi dibatasi pada 200px sebagai batas atas.

---

### Requirement 9: Perbaikan UI — PromptSistem Auto-Height di AI Agent

**User Story:** Sebagai administrator AI, saya ingin textarea PromptSistem di halaman AI Agent tumbuh secara otomatis mengikuti panjang konten yang saya ketik, sehingga saya bisa melihat seluruh prompt tanpa harus menggulir secara manual pada kotak yang terlalu kecil.

#### Acceptance Criteria

1. THE `AiAgent` SHALL menampilkan textarea `PromptSistem` yang tumbuh otomatis mengikuti tinggi konten menggunakan mekanisme Alpine.js `x-on:input` dan `x-effect`.
2. THE `AiAgent` SHALL menetapkan tinggi minimum textarea `PromptSistem` sebesar `120px` (menggantikan `min-h-[220px]` yang ada saat ini).
3. THE `AiAgent` SHALL menghapus atribut `resize-y` dari textarea `PromptSistem` sehingga pengguna tidak dapat mengubah tinggi secara manual.
4. WHEN konten textarea `PromptSistem` sangat panjang, THE `AiAgent` SHALL mengaktifkan scroll vertikal otomatis (`overflow-y-auto`) agar konten tetap dapat diakses.
5. THE `AiAgent` SHALL mengimplementasikan auto-grow dengan pola Alpine.js yang konsisten dengan pola yang sudah dipakai di `vpoint-assistant.blade.php`.

---

### Requirement 10: Perbaikan UI — Layout Template Dua Kolom yang Compact di AI Agent

**User Story:** Sebagai administrator AI, saya ingin keempat textarea template pesan (luar jam kerja, hari libur, sapaan jam kerja, fallback) ditampilkan dalam layout dua kolom di layar desktop dengan tinggi minimum yang lebih kecil, sehingga tampilan tidak memakan terlalu banyak ruang layar.

#### Acceptance Criteria

1. THE `AiAgent` SHALL menampilkan keempat textarea template (luar jam kerja, hari libur, sapaan jam kerja, fallback) dalam layout dua kolom (`lg:grid-cols-2`) pada layar dengan lebar `lg` ke atas.
2. THE `AiAgent` SHALL mengubah nilai `min-h-60` (240px) pada keempat textarea template menjadi `min-h-[80px]` (80px) agar tampilan lebih compact.
3. THE `AiAgent` SHALL mempertahankan atribut `resize-y` pada keempat textarea template sehingga pengguna masih dapat memperbesar textarea secara manual jika diperlukan.
4. WHEN pengguna membuka halaman AI Agent pada layar dengan lebar di bawah breakpoint `lg`, THE `AiAgent` SHALL menampilkan keempat textarea template dalam layout satu kolom (stacked).
5. THE `AiAgent` SHALL memastikan masing-masing textarea template tetap memiliki label yang jelas dan input wrapper Filament yang konsisten.

---

### Requirement 11: Konsistensi Penyimpanan dan Pemuatan ModelInstructAi di AiAgent

**User Story:** Sebagai administrator AI, saya ingin pengaturan Model Instruct yang saya simpan dapat dimuat kembali dengan benar saat halaman AI Agent dibuka ulang, sehingga tidak ada kehilangan data konfigurasi.

#### Acceptance Criteria

1. WHEN `AiAgent.simpanPengaturan()` dieksekusi dan kolom `ModelInstructAi` ada di database, THE `AiAgent` SHALL menyimpan nilai field `ModelInstructAi` ke database.
2. WHEN `AiAgent.simpanPengaturan()` dieksekusi dan kolom `ModelInstructAi` belum ada di database (migrasi belum dijalankan), THE `AiAgent` SHALL TIDAK mencoba menulis kolom `ModelInstructAi` ke database dan menghindari SQL error.
3. WHEN `AiAgent.loadPengaturan()` dipanggil dan kolom `ModelInstructAi` ada di database, THE `AiAgent` SHALL memuat nilai `ModelInstructAi` dari database ke properti `$pengaturan['ModelInstructAi']`.
4. WHEN `AiAgent.loadPengaturan()` dipanggil dan kolom `ModelInstructAi` belum ada di database, THE `AiAgent` SHALL menyetel `$pengaturan['ModelInstructAi']` ke `null` tanpa error SQL.
5. THE `AiAgent` SHALL memvalidasi field `ModelInstructAi` sebagai `nullable|string|max:100` sebelum menyimpan ke database.

---

### Requirement 12: Perilaku Fallback Model pada InternalChatbotService

**User Story:** Sebagai operator sistem, saya ingin VPoint Assistant tetap berfungsi meski `ModelInstructAi` tidak dikonfigurasi, sehingga perubahan konfigurasi tidak memutus layanan yang sudah berjalan.

#### Acceptance Criteria

1. WHEN `InternalChatbotService.getInstructModel()` dipanggil dan `ModelInstructAi` adalah string non-kosong, THE `InternalChatbotService` SHALL mengembalikan nilai `ModelInstructAi`.
2. WHEN `InternalChatbotService.getInstructModel()` dipanggil dan `ModelInstructAi` adalah null atau string kosong, THE `InternalChatbotService` SHALL mengembalikan nilai dari `getPrimaryModel()` sebagai fallback.
3. WHEN `InternalChatbotService.getPrimaryModel()` dipanggil dan `ModelAi` adalah string non-kosong, THE `InternalChatbotService` SHALL mengembalikan nilai `ModelAi`.
4. WHEN `InternalChatbotService.getPrimaryModel()` dipanggil dan `ModelAi` adalah null atau string kosong, THE `InternalChatbotService` SHALL mengembalikan model default dari konfigurasi provider yang aktif.
5. FOR ALL kombinasi nilai `ModelInstructAi` dan `ModelAi` yang valid, THE `InternalChatbotService` SHALL SELALU mengembalikan string model yang non-kosong dari `getAssistantModel()` untuk digunakan memanggil provider AI.

---

## Properti Kebenaran untuk Property-Based Testing

Bagian ini mendefinisikan properti yang dapat diuji secara otomatis menggunakan property-based testing.

### PBT-1: Isolasi Pemilihan Model VPoint Assistant

**Properti:** Untuk semua nilai `ModelInstructAi` yang non-null dan non-kosong, `getAssistantModel(settings, 'light')` TIDAK PERNAH mengembalikan nilai yang sama dengan `ModelAi` kecuali keduanya identik secara eksplisit.

**Kategori:** Metamorphic — hubungan antara input `mode` dan output model harus konsisten.

**Nilai input yang harus dites:** Berbagai kombinasi string model yang valid, null, dan string kosong untuk `ModelInstructAi` dan `ModelAi`.

### PBT-2: Fallback Model Tidak Pernah Menghasilkan String Kosong

**Properti:** Untuk semua kombinasi nilai `ModelInstructAi` (null, kosong, atau string valid) dan `ModelAi` (null, kosong, atau string valid), `getAssistantModel()` SELALU mengembalikan string non-kosong.

**Kategori:** Invariant — output tidak boleh kosong.

### PBT-3: Suggested Replies Bersih Setelah useSuggestedReply

**Properti:** Untuk semua string reply yang valid, setelah `useSuggestedReply(reply)` dipanggil, `$suggestedReplies` SELALU bernilai `[]`.

**Kategori:** Idempotence / post-condition invariant.

### PBT-4: loadHistory Tidak Mengisi suggestedReplies

**Properti:** Untuk semua riwayat percakapan yang mungkin disimpan di database (termasuk riwayat yang memiliki `suggested_replies` non-kosong di `KonteksJson`), setelah `loadHistory()` selesai dieksekusi, `$suggestedReplies` SELALU bernilai `[]`.

**Kategori:** Invariant — load history tidak boleh mempengaruhi state suggested replies.

### PBT-5: Idempotency Migrasi

**Properti:** Menjalankan migrasi berulang kali (termasuk saat kolom sudah ada) TIDAK PERNAH menghasilkan SQL error atau pengecualian.

**Kategori:** Idempotence.

### PBT-6: Konsistensi Translation Keys

**Properti:** Untuk semua key baru yang ditambahkan di `id/ui.php`, key yang identik (nama yang sama) HARUS ada di `en/ui.php`, dan sebaliknya — tidak ada key yang hanya ada di satu file bahasa.

**Kategori:** Completeness / symmetric property.

### PBT-7: AiAutoReplyService Tidak Membaca ModelInstructAi

**Properti:** Untuk semua objek pengaturan yang mungkin (dengan atau tanpa properti `ModelInstructAi`), eksekusi `AiAutoReplyService` TIDAK PERNAH mengakses properti `ModelInstructAi`.

**Kategori:** Isolation invariant — AiAutoReplyService harus terisolasi dari perubahan ini.
