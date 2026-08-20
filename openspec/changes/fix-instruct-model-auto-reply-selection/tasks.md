# Tasks: Perbaiki Pemilihan Model Instruct pada Auto Reply

## 1. Urutan Penentuan Balasan Pertama

- [ ] Buka `src/app/Services/Ai/AiAutoReplyService.php`, method `handleIncomingChat()`.
- [ ] Pindahkan `$isFirstReply = $this->isFirstInboxAiReply($chatId);` ke posisi sebelum blok `DB::table('TAiPermintaan')->insert([...])` (saat ini insert berada di baris 106-120).
- [ ] Hapus perhitungan duplikat `$isFirstReply` yang berada di dalam blok `try` (baris 123) agar hanya ada satu sumber nilai.
- [ ] Pastikan indentasi blok `try` dirapikan; saat ini baris 123-124 tidak sejajar dengan isi `try` lainnya.
- [ ] Verifikasi: tidak ada lagi pembacaan `$isFirstReply` sebelum baris pendefinisiannya.

## 2. Teruskan Flag ke Seluruh Provider

- [ ] Pada `generateReply()`, teruskan `$isFirstReply` ke cabang `deepseek`: `generateChatCompletionReply($settings, $prompt, $apiKey, 'deepseek', $isFirstReply)`.
- [ ] Teruskan `$isFirstReply` ke cabang `openrouter`.
- [ ] Teruskan `$isFirstReply` ke cabang `9router`/`ninerouter`.
- [ ] Pastikan cabang `openai` tetap meneruskan `$isFirstReply` seperti sekarang.
- [ ] Pastikan signature `generateChatCompletionReply(..., bool $isFirstReply = false)` tidak diubah agar pemanggil lain tidak terpengaruh.
- [ ] Verifikasi: seluruh pemanggilan `inboxReplyModel()` di dalam service menerima flag yang berasal dari satu sumber yang sama.

## 3. Fallback Model yang Aman

- [ ] Ubah `inboxReplyModel()` agar tidak lagi memakai operator `??` untuk memilih model.
- [ ] Gunakan pola yang sama dengan `InternalChatbotService` baris 333-343: periksa `property_exists($settings, 'ModelInstructAi')` dan `filled(...)` sebelum memakainya.
- [ ] Urutan untuk balasan pertama: `ModelInstructAi` bila terisi → `ModelAi` bila terisi → `config('services.openai.model')`.
- [ ] Urutan untuk balasan lanjutan: `ModelAi` bila terisi → `config('services.openai.model')`.
- [ ] Pastikan method tetap mengembalikan `string`, bukan `null`.
- [ ] Verifikasi: object pengaturan tanpa properti `ModelInstructAi` tidak memicu error.

## 4. Cegah Nilai Kosong Tersimpan

- [ ] Buka `src/app/Filament/Pages/AiAgent.php`, method `simpanPengaturan()`.
- [ ] Pada blok `if (Schema::hasColumn('MPengaturanAi', 'ModelInstructAi'))`, normalisasi nilai: `trim()` lalu simpan `null` bila hasilnya string kosong.
- [ ] Pastikan `AiSettings::flush()` tetap dipanggil setelah penyimpanan (sudah ada, jangan dihapus).
- [ ] Verifikasi: mengosongkan field Model Instruct lalu menyimpan menghasilkan `NULL` pada database, bukan `''`.

## 5. Guard Anti-Regresi

- [ ] Pastikan `sendClosingMessage()` tetap memakai `$settings->ModelAi ?: config('services.openai.model')` dan tetap memanggil `generateReply($settings, $prompt, false)`.
- [ ] Pastikan `testProviderConnection()` tetap memanggil `generateReply($settings, $prompt, false)` sehingga test koneksi memakai Model Utama.
- [ ] Pastikan `replyDecision()`, `buildPrompt()`, `relevantKnowledge()`, dan `storeReply()` tidak berubah.
- [ ] Pastikan alur `KirimKeWaha`, `ModeKirim`, jam kerja, hari libur, dan nomor pengecualian tidak tersentuh.
- [ ] Pastikan `InternalChatbotService` tidak diubah.

## 6. Test

- [ ] Buat `src/tests/Feature/AutoReplyModelSelectionTest.php` dengan HTTP fake terhadap provider.
- [ ] Test: chat tanpa balasan AI sebelumnya + provider OpenAI → body request `model` berisi nilai `ModelInstructAi`.
- [ ] Test: chat tanpa balasan AI sebelumnya + provider DeepSeek → body request `model` berisi nilai `ModelInstructAi`.
- [ ] Test: chat tanpa balasan AI sebelumnya + provider OpenRouter → body request `model` berisi nilai `ModelInstructAi`.
- [ ] Test: chat tanpa balasan AI sebelumnya + provider 9Router → body request `model` berisi nilai `ModelInstructAi`.
- [ ] Test: chat yang sudah punya balasan AI → body request `model` berisi nilai `ModelAi` untuk seluruh provider.
- [ ] Test: `TAiPermintaan.ModelAi` pada balasan pertama sama dengan model yang dikirim ke provider.
- [ ] Test: `ModelInstructAi` bernilai `''` → model yang dipakai adalah `ModelAi`.
- [ ] Test: object pengaturan tanpa properti `ModelInstructAi` → model yang dipakai adalah `ModelAi`, tanpa error.
- [ ] Test: `ModelInstructAi` dan `ModelAi` keduanya kosong → model yang dipakai adalah `config('services.openai.model')`.
- [ ] Test: `sendClosingMessage()` memakai `ModelAi` meskipun chat belum pernah dibalas AI.
- [ ] Test: `testProviderConnection()` memakai `ModelAi` dan tidak membuat baris `TChatD`.
- [ ] Verifikasi: `php artisan test --filter=AutoReplyModelSelection` lulus.

## 7. Validasi Menyeluruh

- [ ] `php -l app/Services/Ai/AiAutoReplyService.php`.
- [ ] `php -l app/Filament/Pages/AiAgent.php`.
- [ ] `php artisan test` dan catat hasilnya.
- [ ] `vendor\bin\pint --test` untuk file yang diubah.
- [ ] `npm run build` **tidak** diperlukan karena tidak ada perubahan asset frontend.

## 8. Sinkronisasi OpenSpec

- [ ] Buka `openspec/changes/add-ai-instruct-model/tasks.md` bagian 8 "No-Regression Guard".
- [ ] Koreksi item "Pastikan `src/app/Services/Ai/AiAutoReplyService.php` tidak berubah memakai `ModelInstructAi`" karena klaim tersebut tidak sesuai kondisi kode; ganti menjadi rujukan bahwa aturan balasan pertama memakai Model Instruct dipindahkan ke change `fix-instruct-model-auto-reply-selection`.
- [ ] Tambahkan catatan pada `openspec/changes/add-model-instruct/proposal.md` bahwa requirement "Auto-Reply uses Primary Model Only" telah di-MODIFIED oleh change ini, dengan tanggal keputusan 2026-07-23.
- [ ] Pastikan tidak ada dua delta spec aktif yang memberikan aturan berbeda untuk pemilihan model auto-reply.

## 9. Verifikasi Manual pada Staging

- [ ] Isi Model Utama dan Model Instruct dengan dua nilai valid yang berbeda, lalu simpan.
- [ ] Kirim pesan dari nomor customer yang belum pernah dibalas AI; periksa baris terbaru `TAiPermintaan` → `ModelAi` berisi nilai Model Instruct.
- [ ] Kirim pesan kedua pada chat yang sama; periksa baris terbaru `TAiPermintaan` → `ModelAi` berisi nilai Model Utama.
- [ ] Ulangi kedua langkah di atas dengan provider chat-completions (DeepSeek/OpenRouter/9Router).
- [ ] Cocokkan model pada `TAiPermintaan` dengan payload request pada `TLogIntegrasi`/respons provider.
- [ ] Kosongkan Model Instruct, simpan, ulangi pengujian balasan pertama → memakai Model Utama tanpa error model kosong.
- [ ] Tekan Test Koneksi AI → memakai Model Utama, tidak membuat `TChatD`, tidak mengirim WAHA.
- [ ] Tutup satu percakapan → pesan penutup memakai Model Utama.

## 10. Dokumentasi

- [ ] Perbarui `docs/PRD_CARE_DESK_WACS.md`: tandai TD-01 dan TD-02 sebagai teratasi.
- [ ] Perbarui FR-AIR-10 agar menyatakan aturan berlaku untuk seluruh provider dan menyebut fallback string kosong.
- [ ] Perbarui FR-AIR-11 agar menyebut bahwa jalur chat-completions juga menerima flag balasan pertama.
- [ ] Perbarui FR-AIR-13 agar menyatakan `TAiPermintaan.ModelAi` mencatat model efektif.
- [ ] Tambahkan catatan rilis: instalasi non-OpenAI mulai memakai Model Instruct untuk balasan pertama; kosongkan field tersebut untuk kembali ke perilaku lama.

## 11. Deployment

- [ ] Deploy kode; tidak ada migration dan tidak ada perubahan data.
- [ ] `php artisan optimize:clear` lalu `php artisan optimize`.
- [ ] `php artisan queue:restart` **wajib** agar worker `ai-replies` memuat ulang kode service.
- [ ] Restart Reverb dan scheduler tidak diperlukan.
- [ ] Backup database tidak diperlukan.
- [ ] Pantau `TAiPermintaan.PesanError` selama 24 jam pertama untuk memastikan tidak ada lonjakan `Gagal Fallback` akibat nama model instruct yang tidak valid pada provider.
