# Chatbot WhatsAppAsli Improve SDD Ledger

Baseline: 02be061 (master), no commits permitted by user.

## Task Status Summary

**Task 1: Kontrak Data dan Migration SQL Server** ✅ COMPLETE
- Migration guarded dengan COL_LENGTH checks, extended_properties untuk rollback
- Index IX_TChat_StatusIdentitasWaha_TglIdentitasWahaDiambil dan IX_TChat_SesiJenisNomorWhatsapp ditambahkan
- Fresh schema DATABASE_SCHEMA_WACS.sql disinkronkan
- Runtime test terblokir (PHPUnit tidak tersedia)

**Task 2: Adapter Metadata WAHA dan Snapshot** ✅ COMPLETE
- WahaSender.getContactInfo(), getGroupInfo(), getContactProfilePictureUrl() implementasi lengkap
- Parser metadata dengan validasi panjang UTF-16 code units
- Sanitasi TLogIntegrasi.ResponseJson dan PesanError (redact sensitive values)
- Runtime test terblokir

**Task 3: Queue, Idempotensi, dan Observability** ✅ COMPLETE
- SyncWahaChatIdentityJob dengan tries=3, timeout=30, backoff [30,120], deduplikasi 60 detik via Cache
- Update atomik snapshot TChat tanpa mengubah mapping master
- Dispatch dari WahaWebhookProcessor setelah transaksi chat sukses
- Broadcast hanya saat snapshot berubah (SendBroadcastDebouncedJob)
- Runtime test terblokir

**Task 4: Tampilan WhatsAppAsli pada Inbox** ✅ COMPLETE
- Identity.whatsapp prioritas: snapshot DB > payload raw > fallback
- Badge Grup WhatsApp (@g.us) dan Chat Pribadi (@lid + nomor resolusi)
- Header/detail menampilkan status sinkronisasi dan waktu tanpa secret teknis
- Aksi refresh metadata antre job deduplicated
- UI responsive dengan wrapping/break-all untuk identifier panjang
- Runtime test terblokir

**Task 5: Permission, Localization, dan Regression Test** ✅ COMPLETE
- Key Bahasa Indonesia/Inggris di src/resources/lang/{id,en}/ui.php
- Refresh memakai permission Inbox existing
- Participant avatar dengan fallback inisial, dark-mode-safe
- Runtime test terblokir

**Task 6: Koreksi Foto Profil GroupWhatsApp** ✅ COMPLETE *(sudah termasuk dalam Task 4 & 5)*
- Resolver avatar grup memprioritaskan raw group JID @g.us
- Fallback MGrupWhatsapp.IdGrupWaha hanya jika valid, tidak override snapshot terakhir
- Preservasi foto terakhir saat WAHA gagal
- Runtime test terblokir

**Task 7: Koreksi Aturan AI Agent dan Session** ✅ COMPLETE
- isFirstReply dihitung sebelum pemilihan model dan pencatatan TAiPermintaan
- Setting BatasSesiAutoReplyMenit default 60, validasi CHECK 1..1440
- Policy: All Session aktif proses semua incoming; nonaktif hanya first/idle >= batas
- Reason code audit untuk skip, failure, fallback, draft, delivery error
- ProcessAiAutoReplyJob failure tidak menandai chat sebagai sudah dibalas
- Runtime test terblokir

**Task 8: Koreksi Tampilan Base64 Media** ✅ COMPLETE
- WahaMediaPayload membedakan media valid vs base64 mentah
- State/renderer tidak tampilkan base64 ketika preview/download berhasil
- Fallback localized untuk base64 rusak tanpa PayloadJson/raw HTML/API key
- Controller sebagai binary boundary (tidak expose text body ke Livewire state)
- Runtime test terblokir

**Task 9: Koreksi Breadcrumb Halaman Ticketing** ✅ COMPLETE
- HasMenuBreadcrumbs trait dipasang pada ManageTickets, ManageStatusTickets, ManagePrioritasTickets, ManageKategoriTickets
- AccessPermissions::TICKET_VIEW guard pada semua halaman
- Label localization id/en untuk Status Ticket, Prioritas, Kategori
- Syntax check lulus, runtime test terblokir

**Task 10: Validasi Security dan Sanitasi** ✅ COMPLETE *(terintegrasi di Task 2, 7, 8)*
- shouldSendWahaApiKey() validasi scheme+host+port sebelum kirim API key
- safeMessageError() fail-closed ke pesan localized generik
- sanitizeReason() redact token/api_key/password/secret
- redactSensitiveValues() pada URL endpoint log
- No secret exposure di UI, log, atau Livewire state

**Task 11: Validasi Terpadu dan Deployment** 🔄 PARTIAL COMPLETE
- ✅ Step 1: PHP lint 12/12 file clean
- ❌ Step 2: Targeted test — BLOCKED (PHPUnit/Pest tidak tersedia)
- ⚠️ Step 3: Broader validation — Vite build ✅ sukses, full test & Pint ❌ blocked
- ✅ Step 4: OpenSpec valid, git diff --check exit 0
- ⏸️ Step 5: Migration SQL Server — REQUIRES STAGING (backup + migrate --force)
- ⏸️ Step 6: Restart runtime — DEPLOYMENT STEP (config/route/view cache, queue restart)
- ⏸️ Step 7: Manual browser verification — REQUIRES LIVE WAHA
- ✅ Step 8: Final task sync — tasks.md updated

## Environment Blockers

1. **PHPUnit/Pest tidak terinstall** — vendor/bin/phpunit.bat dan vendor/bin/pint.bat tidak ada. Test harus dijalankan di environment dev/staging yang memiliki composer install --dev completed.
2. **WAHA live tidak terhubung** — Manual verification memerlukan WAHA instance aktif untuk test grup mapped/unmapped, avatar participant, idle session policy.
3. **SQL Server staging belum tersedia** — Migration perlu backup database staging sebelum php artisan migrate --force.

## Files Changed (131 files, +18873/-420)

Core implementation:
- src/app/Services/Waha/WahaSender.php (+231 lines)
- src/app/Services/Waha/WahaWebhookProcessor.php (+95 lines)
- src/app/Jobs/SyncWahaChatIdentityJob.php (new, 283 lines)
- src/app/Filament/Pages/InboxWhatsapp.php (+458 lines)
- src/app/Services/Ai/AiAutoReplyService.php (+175 lines)
- src/app/Http/Controllers/WahaMediaController.php (+355 lines)
- src/app/Support/WahaMediaPayload.php (new, 372 lines)
- src/database/migrations/2026_07_30_000001_add_waha_identity_snapshot_to_chat.php (new, 216 lines)

Tests (syntax verified, runtime blocked):
- tests/Feature/Waha/WahaChatIdentitySyncTest.php (679 lines)
- tests/Feature/Waha/WahaWebhookGroupIngestionTest.php (253 lines)
- tests/Feature/Filament/Pages/InboxWhatsappTest.php (919 lines)
- tests/Feature/Ai/AiAutoReplySessionPolicyTest.php (191 lines)
- tests/Feature/Http/Controllers/WahaMediaControllerTest.php (460 lines)
- tests/Unit/Support/WahaMediaPayloadTest.php (355 lines)

Documentation:
- openspec/changes/chatbot-whatsappasli-improve/tasks.md (updated with Task 11)
- docs/superpowers/plans/2026-07-30-chatbot-whatsapp-improve.md (execution plan)

## Next Steps for Production

1. **Environment Setup**: composer install --dev di staging/prod untuk PHPUnit/Pint
2. **Run Tests**: php artisan test && vendor/bin/pint --test
3. **Backup Database**: Backup staging database sebelum migration
4. **Deploy Migration**: php artisan migrate --force
5. **Clear & Cache**: config:cache, route:cache, view:cache
6. **Restart Queues**: queue:restart (webhooks, broadcasts, ai-replies)
7. **Manual Verification**: 6-point browser checklist dengan WAHA aktif
