### Task 7: AI Agent All Session dan Idle Session

**Tujuan:** AI Agent menjawab semua incoming eligible saat All Session aktif, dan saat nonaktif menjawab chat pertama atau setelah idle minimal `BatasSesiAutoReplyMenit`.

**Files:**
- Modify: `src/app/Services/Ai/AiAutoReplyService.php`
- Modify: `src/app/Jobs/ProcessAiAutoReplyJob.php`
- Modify: `src/app/Filament/Pages/AiAgent.php`
- Modify: `src/resources/views/filament/pages/ai-agent.blade.php`
- Modify: `src/resources/lang/id/ui.php`
- Modify: `src/resources/lang/en/ui.php`
- Create: `src/tests/Feature/Ai/AiAutoReplySessionPolicyTest.php`

**Interfaces:**
- `AutoReplyJamKerjaBerlanjut` dilabeli All Session.
- `BatasSesiAutoReplyMenit`: integer `1..1440`, default `60`.
- Session policy emits reason code: `all_session`, `first_message`, `idle_session`, `active_session_skip` selain guard reason existing.

- [ ] **Step 1: Tulis test merah `$isFirstReply`**

Aktifkan AI dengan provider fake dan call `handleIncomingChat()`. Assert tidak terjadi undefined variable serta `TAiPermintaan.ModelAi` mengikuti model pilihan first/non-first yang sudah ada.

- [ ] **Step 2: Tulis test merah All Session aktif**

Insert dua incoming eligible selang satu menit dan AI reply setelah pesan pertama. Dengan `AutoReplyJamKerjaBerlanjut=true`, proses pesan kedua dan assert satu outgoing AI baru tersimpan.

- [ ] **Step 3: Tulis test merah idle dan active session**

Dengan All Session off dan idle 60: pesan setelah 61 menit harus dibalas; pesan setelah 30 menit harus menghasilkan `skipped=true`, reason code `active_session_skip`, dan tidak menambah outgoing AI message.

- [ ] **Step 4: Pindahkan perhitungan `$isFirstReply`**

Di `handleIncomingChat()`, set `$isFirstReply = $this->isFirstInboxAiReply($chatId);` sebelum insert `TAiPermintaan`; hapus assignment duplikat di dalam `try`.

- [ ] **Step 5: Implementasikan helper policy idle**

Tambahkan helper yang mencari incoming customer sebelumnya sebelum message terbaru. Bila All Session aktif return allowed `all_session`; bila tidak ada message sebelumnya return `first_message`; bila selisih menit >= setting return `idle_session`; selain itu deny `active_session_skip`. Clamp setting ke `1..1440` agar data legacy invalid tidak mematikan flow.

- [ ] **Step 6: Pertahankan urutan guard**

Jangan hapus check `AutoReplyAktif`, duplicate/already answered, manual reply, jam kerja, hari libur, nomor excluded, provider, knowledge, dan `KirimKeWaha`. Session policy berjalan setelah incoming terbaru tersedia dan sebelum call provider.

- [ ] **Step 7: UI setting dan localization**

Tambahkan rules Livewire `required|integer|min:1|max:1440`, hydrate default 60, input type number `min=1 max=1440`, label All Session, serta helper text bahwa batas idle hanya berlaku ketika All Session off. Tambahkan string `id` dan `en`; jangan hardcode copy baru di Blade.

- [ ] **Step 8: Delivery failure tidak dianggap sukses**

Jika `storeReply()` gagal mengirim WAHA, set `TChatD.StatusKirim='Gagal'`, simpan error tersanitasi maksimal 500 karakter, dan jangan update chat seolah dibalas/delivered. Draft lokal tetap sukses lokal bila `KirimKeWaha=false`. Pastikan `ProcessAiAutoReplyJob::failed()` mencatat context minimal tanpa prompt/provider body/secret.

- [ ] **Step 9: Jalankan AI test**

```powershell
cd src
php -l app/Services/Ai/AiAutoReplyService.php
php -l app/Jobs/ProcessAiAutoReplyJob.php
php -l app/Filament/Pages/AiAgent.php
php artisan test --filter=AiAutoReplySessionPolicyTest
```

Expected: All Session on membalas semua eligible incoming; All Session off membalas first/idle dan skip active session; tidak ada undefined `$isFirstReply`; WAHA delivery failure bukan status sukses.

---

