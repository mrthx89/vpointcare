# Change: Perbaiki Pemilihan Model Instruct pada Auto Reply

## Summary

Aturan "balasan AI pertama pada sebuah chat memakai Model Instruct, balasan berikutnya memakai Model Utama" sudah diimplementasikan di `AiAutoReplyService`, tetapi mengandung tiga cacat: variabel penentu dipakai sebelum didefinisikan sehingga log mencatat model yang salah, flag tidak diteruskan ke provider chat-completions sehingga aturan ini hanya berlaku untuk OpenAI, dan fallback memakai `??` sehingga nilai string kosong lolos menjadi nama model. Change ini memperbaiki ketiganya dan sekaligus menyelaraskan dua delta spec terdahulu yang saling bertentangan mengenai boleh-tidaknya Model Instruct dipakai pada jalur auto-reply.

## Problem Statement

Tim menetapkan Model Instruct agar balasan pembuka (sapaan, routing ringan) tidak memakai model mahal. Saat ini penghematan itu **hanya terjadi bila provider yang dipakai adalah OpenAI**. Instalasi yang memakai DeepSeek, OpenRouter, atau 9Router — yang justru dipilih karena alasan biaya — sama sekali tidak mendapat manfaatnya, tanpa indikasi apa pun di UI maupun log.

Lebih buruk, kolom `TAiPermintaan.ModelAi` yang menjadi satu-satunya jejak audit "model apa yang dipakai untuk balasan ini" mencatat nilai yang salah untuk setiap balasan pertama. Ketika biaya API ditinjau atau kualitas balasan dipertanyakan, data auditnya tidak dapat dipercaya.

Selain itu, dua change OpenSpec terdahulu meninggalkan spec yang saling bertentangan, sehingga tidak ada acuan tunggal untuk menilai apakah perilaku saat ini benar atau salah.

## Current State

Verifikasi pada source code aktual, file `src/app/Services/Ai/AiAutoReplyService.php`.

**Cacat 1 — variabel dipakai sebelum didefinisikan.**

Pada `handleIncomingChat()`, insert `TAiPermintaan` di baris 106-120 memakai `$this->inboxReplyModel($settings, $isFirstReply)` pada baris **110**, sedangkan `$isFirstReply` baru didefinisikan pada baris **123**:

```php
DB::table('TAiPermintaan')->insert([
    'ModelAi' => $this->inboxReplyModel($settings, $isFirstReply),   // baris 110
    ...
]);

try {
    $isFirstReply = $this->isFirstInboxAiReply($chatId);              // baris 123
    $generated = $this->generateReply($settings, $prompt, $isFirstReply);
```

Pada PHP 8.3 variabel yang belum terdefinisi bernilai `null` disertai warning, sehingga `$isFirstReply` selalu dievaluasi sebagai `false`. Akibatnya `TAiPermintaan.ModelAi` **selalu** mencatat `ModelAi`, bahkan ketika request sebenarnya memakai `ModelInstructAi`. Model yang benar-benar dikirim ke provider tetap tepat karena `generateReply()` dipanggil setelah baris 123; yang rusak murni jejak auditnya.

**Cacat 2 — flag tidak diteruskan ke provider non-OpenAI.**

`generateReply()` menerima `$isFirstReply` tetapi tidak meneruskannya:

```php
if ($provider === 'deepseek') {
    return $this->generateChatCompletionReply($settings, $prompt, $apiKey, 'deepseek');
}
if ($provider === 'openrouter') {
    return $this->generateChatCompletionReply($settings, $prompt, $apiKey, 'openrouter');
}
if (in_array($provider, ['9router', 'ninerouter'], true)) {
    return $this->generateChatCompletionReply($settings, $prompt, $apiKey, 'ninerouter');
}
if ($provider === 'openai') {
    return $this->generateOpenAiReply($settings, $prompt, $apiKey, $isFirstReply);   // hanya di sini
}
```

`generateChatCompletionReply()` memiliki parameter `bool $isFirstReply = false` yang tidak pernah diisi, sehingga selalu memanggil `inboxReplyModel($settings, false)` dan mengembalikan `ModelAi`.

**Cacat 3 — fallback `??` meloloskan string kosong.**

```php
private function inboxReplyModel(object $settings, bool $isFirstReply): string
{
    if ($isFirstReply) {
        return $settings->ModelInstructAi ?? $settings->ModelAi ?? config('services.openai.model');
    }
    return $settings->ModelAi ?? config('services.openai.model');
}
```

`AiAgent::simpanPengaturan()` menyimpan `ModelInstructAi` dari input teks Livewire; saat admin mengosongkan field, nilai yang tersimpan adalah string kosong, bukan `NULL`. Operator `??` hanya menangkap `null`, sehingga `''` diteruskan sebagai nama model dan request ke provider gagal. Sebagai pembanding, `InternalChatbotService` baris 333-343 sudah menangani hal ini dengan `property_exists()` + `! empty()`.

**Konflik spec.**

| Sumber | Requirement |
| --- | --- |
| `openspec/changes/add-model-instruct/specs/care-desk/spec.md` | "Auto-Reply uses Primary Model Only — The system SHALL NOT use Model Instruct for customer auto-replies **under any circumstances**" |
| `openspec/changes/add-ai-instruct-model/specs/care-desk/spec.md` | "Jawaban pertama Inbox WhatsApp memakai Model Instruct" **dan** "Auto-reply tetap memakai Model Utama" — dua requirement yang saling bertentangan dalam satu dokumen |
| `openspec/changes/add-ai-instruct-model/tasks.md` bagian 8 | "Pastikan `AiAutoReplyService.php` tidak berubah memakai `ModelInstructAi`" — tercentang selesai, padahal kode memakainya |

Keputusan pemilik produk pada 2026-07-23: **aturan balasan pertama memakai Model Instruct dipertahankan**. Karena itu spec yang melarangnya harus dicabut, dan klaim tugas yang keliru harus dikoreksi.

## Goals

- Aturan model auto-reply berlaku seragam untuk seluruh provider: OpenAI, DeepSeek, OpenRouter, dan 9Router.
- `TAiPermintaan.ModelAi` mencatat model yang benar-benar dikirim ke provider.
- `ModelInstructAi` bernilai string kosong diperlakukan sama dengan kosong/`NULL`, yaitu fallback ke `ModelAi`.
- Satu acuan spec yang tidak bertentangan mengenai pemilihan model pada jalur auto-reply.
- Test koneksi AI dan pesan penutup chat tetap memakai `ModelAi` seperti sebelumnya.

## Non-Goals

- Tidak mengubah pohon keputusan auto-reply (hari libur, luar jam kerja, berlanjut, sapaan, skip).
- Tidak mengubah `KirimKeWaha`, `ModeKirim`, jam kerja, hari libur, nomor pengecualian, maupun retrieval knowledge.
- Tidak mengubah pemilihan model pada `InternalChatbotService`; implementasinya sudah benar.
- Tidak menambah kolom, migration, atau perubahan data.
- Tidak menambahkan UI baru pada halaman AI Agent; kedua field model sudah tersedia.
- Tidak mengimplementasikan fitur "opsi jawaban ringan" di Inbox WhatsApp yang masih berstatus rencana pada `add-ai-instruct-model`.

## Proposed Changes

### 1. Pindahkan penentuan `$isFirstReply` sebelum dipakai

Hitung `$isFirstReply = $this->isFirstInboxAiReply($chatId);` sebelum blok insert `TAiPermintaan`, lalu hapus perhitungan duplikatnya di dalam `try`. Dengan begitu satu nilai dipakai konsisten oleh log audit dan oleh pemanggilan provider.

### 2. Teruskan `$isFirstReply` ke seluruh provider

Ubah ketiga cabang `generateChatCompletionReply()` di `generateReply()` agar meneruskan `$isFirstReply`. Parameter tersebut sudah ada pada signature method, sehingga perubahannya minimal dan tidak mengubah kontrak.

### 3. Perbaiki fallback model

Ubah `inboxReplyModel()` agar memperlakukan `null`, string kosong, dan properti yang tidak ada sebagai "tidak diisi", memakai pola yang sama dengan `InternalChatbotService`:

- `$isFirstReply` benar → `ModelInstructAi` bila terisi, jika tidak `ModelAi` bila terisi, jika tidak `config('services.openai.model')`.
- `$isFirstReply` salah → `ModelAi` bila terisi, jika tidak `config('services.openai.model')`.

Pemeriksaan properti wajib aman terhadap object pengaturan yang tidak memiliki kolom `ModelInstructAi`, karena `AiSettings::get()` membentuk object dari baris database dan instalasi lama bisa saja belum memiliki kolom tersebut.

### 4. Cegah nilai kosong tersimpan sejak awal

Pada `AiAgent::simpanPengaturan()`, normalisasi `ModelInstructAi` menjadi `NULL` bila hasil trim-nya kosong, sehingga database tidak lagi menyimpan string kosong. Ini melengkapi perbaikan nomor 3, bukan menggantikannya — data yang terlanjur tersimpan kosong tetap ditangani runtime.

### 5. Selaraskan spec yang bertentangan

- Delta spec change ini menandai requirement "Auto-Reply uses Primary Model Only" dari `add-model-instruct` sebagai **MODIFIED**, digantikan aturan pemilihan model per-balasan yang berlaku lintas provider.
- Requirement "Auto-reply tetap memakai Model Utama" dari `add-ai-instruct-model` dipersempit menjadi hanya berlaku untuk balasan lanjutan, pesan penutup chat, dan test koneksi.
- Koreksi klaim keliru pada `openspec/changes/add-ai-instruct-model/tasks.md` bagian 8 agar tidak lagi menyatakan `AiAutoReplyService` tidak memakai `ModelInstructAi`, dan tambahkan rujukan ke change ini.

## Impacted Areas

| Area | Detail |
| --- | --- |
| File diubah | `src/app/Services/Ai/AiAutoReplyService.php` (`handleIncomingChat()`, `generateReply()`, `inboxReplyModel()`) |
| File diubah | `src/app/Filament/Pages/AiAgent.php` (`simpanPengaturan()` — normalisasi `ModelInstructAi`) |
| OpenSpec | `openspec/changes/add-ai-instruct-model/tasks.md` (koreksi klaim bagian 8) |
| Database | Tidak ada perubahan schema, migration, maupun data |
| API/Route | Tidak ada |
| Permission | Tidak ada; pengaturan tetap membutuhkan `ai_agent.manage` |
| Localization | Tidak ada key baru |
| Queue | `ProcessAiAutoReplyJob` tidak berubah; hanya perilaku internal service yang dipanggilnya |
| Broadcast/Scheduler | Tidak ada |
| Frontend asset | Tidak ada; `npm run build` tidak diperlukan |
| Biaya operasional | Instalasi non-OpenAI akan mulai memakai Model Instruct untuk balasan pertama; biaya per balasan pertama berpotensi turun, kualitasnya perlu dipantau |
| Dokumentasi | `docs/PRD_CARE_DESK_WACS.md` — tutup TD-01 dan TD-02, sesuaikan FR-AIR-10 dan FR-AIR-11 |

## Risks and Mitigations

| Risiko | Mitigasi |
| --- | --- |
| Instalasi DeepSeek/OpenRouter/9Router tiba-tiba memakai model berbeda untuk balasan pertama sehingga kualitas sapaan berubah | Perilaku ini adalah requirement yang disetujui. Bila `ModelInstructAi` dikosongkan, sistem otomatis kembali memakai `ModelAi` sehingga tersedia jalan keluar tanpa deploy ulang. Sampaikan pada catatan rilis |
| Nama model instruct tidak valid pada provider tertentu sehingga balasan pertama gagal | Auto-reply sudah memiliki fallback template; kegagalan provider tercatat sebagai `Gagal Fallback` pada `TAiPermintaan.PesanError` dan customer tetap menerima balasan |
| `ModelInstructAi` kosong tersimpan sebagai string kosong pada database lama | Ditangani di runtime oleh perbaikan nomor 3, dan dicegah untuk data baru oleh perbaikan nomor 4 |
| Object pengaturan tanpa kolom `ModelInstructAi` (instalasi lama) memicu error properti | Pemakaian `property_exists()` sebelum akses, diuji eksplisit pada test |
| Perubahan spec dianggap melonggarkan aturan "auto-reply tidak boleh pakai model murah" | Delta spec menyatakan batasannya secara eksplisit: hanya balasan **pertama**; balasan lanjutan, pesan penutup, dan test koneksi tetap wajib `ModelAi` |
| Cache pengaturan AI 5 menit membuat perubahan model tidak langsung terasa saat pengujian | `AiSettings::flush()` sudah dipanggil pada penyimpanan pengaturan; disebutkan pada langkah verifikasi manual |

## Validation

```powershell
cd src
php -l app/Services/Ai/AiAutoReplyService.php
php -l app/Filament/Pages/AiAgent.php
php artisan test --filter=AutoReplyModelSelection
php artisan test
vendor\bin\pint --test
```

Verifikasi manual pada staging, untuk **setiap** provider yang dipakai (minimal OpenAI dan satu provider chat-completions):

1. Isi `Model Utama` dan `Model Instruct` dengan dua nilai berbeda yang keduanya valid, lalu simpan.
2. Kirim pesan dari nomor customer yang belum pernah dibalas AI → periksa `TAiPermintaan` baris terbaru: `ModelAi` harus berisi nilai **Model Instruct**.
3. Kirim pesan kedua pada chat yang sama → `TAiPermintaan` baris terbaru harus berisi nilai **Model Utama**.
4. Cocokkan dengan `TLogIntegrasi`/respons provider untuk memastikan model yang tercatat sama dengan yang benar-benar dikirim.
5. Kosongkan `Model Instruct`, simpan, lalu ulangi langkah 2 → harus memakai **Model Utama** dan tidak menghasilkan error model kosong.
6. Tekan **Test Koneksi AI** → tetap memakai `ModelAi`, tidak membuat baris `TChatD`, dan tidak mengirim pesan WAHA.
7. Tutup satu percakapan → pesan penutup tetap memakai `ModelAi`.

## Rollback

Tidak ada perubahan schema maupun data, sehingga rollback cukup mengembalikan kode:

```powershell
git revert <commit>
cd src
php artisan optimize:clear
php artisan queue:restart
```

`php artisan queue:restart` diperlukan agar worker memuat ulang kode service. Backup database tidak diperlukan.

Jalan keluar cepat tanpa rollback kode: kosongkan field **Model Instruct** pada halaman AI Agent, simpan, dan seluruh balasan kembali memakai Model Utama.
