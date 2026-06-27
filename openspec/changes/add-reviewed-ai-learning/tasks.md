# Tasks: Reviewed AI Learning dari Chat Customer

> Status Update: Checklist ini disinkronkan setelah implementasi awal. Item [x] sudah dikerjakan atau diputuskan di kode/dokumen. Item [ ] masih pending, terutama migration database runtime, browser test, permission role audit nyata, dan fase lanjutan.

## 0. Pre-Implementation Decisions

- [x] Putuskan permission fase 1: pakai `knowledge.manage` saja atau tambah `knowledge.learn`/`knowledge.review`.
- [x] Putuskan lokasi menu: group AI Agent atau Master Data Knowledge.
- [x] Putuskan lokasi tombol awal: `ViewChatSession`, `InboxWhatsapp`, atau keduanya.
- [x] Putuskan approval awal: selalu create `MPengetahuan` baru atau boleh update existing.
- [x] Putuskan apakah ekstraksi memakai provider AI aktif atau setting provider khusus learning.
- [x] Putuskan apakah response JSON provider disimpan penuh atau ringkasan audit saja.

## 1. Audit Existing Code

- [x] Audit migration `MPengetahuan`, `TChat`, `TChatD`, `MCustomer`, `MInstansi`, `MPengguna`.
- [x] Audit model `Pengetahuan` dan resource `PengetahuanResource`.
- [x] Audit `AiAutoReplyService::buildPrompt()` dan `relevantKnowledge()`.
- [x] Audit API key/provider helper di `AiAutoReplyService` untuk reuse atau ekstraksi helper.
- [x] Audit `InboxWhatsapp` action pattern, notification pattern, dan selected chat state.
- [x] Audit `ViewChatSession` action pattern dan available chat ID.
- [ ] Audit `Ticketing` untuk kemungkinan integrasi fase lanjut.
- [x] Audit `AccessPermissions`, `NavigationHelper`, dan localization keys.

## 2. Database Migration

- [x] Buat migration SQL Server `create_ai_draft_pengetahuan_table`.
- [x] Gunakan pola idempotent `IF OBJECT_ID(...) IS NULL` sesuai migration existing.
- [x] Tambahkan kolom `Id` uniqueidentifier primary key.
- [x] Tambahkan kolom referensi `IdChat`, `IdCustomer`, `IdInstansi`, `IdPengetahuan` nullable.
- [x] Tambahkan kolom konten draft `JudulDraft`, `IsiDraft`, `TagDraft`, `KategoriDraft`.
- [x] Tambahkan kolom sumber `RingkasanSumber`, `CuplikanSumberDisanitasi`.
- [x] Tambahkan kolom quality `ConfidenceScore`, `HashKonten`, `AlasanTidakLayak`.
- [x] Tambahkan kolom review `StatusReview`, `CatatanReviewer`, `DireviewOleh`, `TglReview`.
- [x] Tambahkan kolom audit AI `ProviderAi`, `ModelAi`, `PromptRingkas`, `ResponseJson`, `DibuatOlehAi`.
- [x] Tambahkan kolom audit user `DibuatOleh`, `TglBuat`, `TglEdit`.
- [x] Tambahkan default constraint untuk `StatusReview`, `DibuatOlehAi`, dan `TglBuat` bila sesuai pola repo.
- [x] Tambahkan index `StatusReview/TglBuat`, `IdChat`, `IdPengetahuan`, `HashKonten`.
- [ ] Tambahkan foreign key nullable jika aman dan tidak mengganggu deployment existing.
- [x] Buat rollback/drop logic yang aman.

## 3. Model dan Constants

- [x] Buat model `DraftPengetahuan` atau `AiDraftPengetahuan`.
- [x] Set table ke `TAiDraftPengetahuan`.
- [x] Set primary key UUID/string sesuai pola model existing.
- [x] Tambahkan fillable/guarded sesuai style repo.
- [x] Tambahkan casts untuk boolean, datetime, decimal.
- [x] Tambahkan constants status: `Draft`, `PerluRevisi`, `Disetujui`, `Ditolak`, `Diarsipkan`.
- [x] Tambahkan helper `isApproved()`, `canApprove()`, `canReview()` bila berguna.
- [ ] Tambahkan relasi opsional ke chat, customer, instansi, knowledge, creator, reviewer jika model tersedia.

## 4. AI Provider Reuse

- [ ] Ekstrak helper provider dari `AiAutoReplyService` bila perlu agar tidak duplikasi.
- [x] Pastikan ekstraksi bisa memakai OpenAI Responses API dan Chat Completions provider.
- [x] Pastikan API key resolver tidak membocorkan secret ke log/UI.
- [x] Tambahkan method internal untuk request AI dengan prompt extraction.
- [x] Pastikan timeout wajar dan error provider disanitasi.
- [x] Pastikan model/provider tersimpan di draft untuk audit.

## 5. Sanitization Utility

- [x] Tambahkan method sanitasi email menjadi `[email]`.
- [x] Tambahkan method sanitasi nomor telepon panjang menjadi `[nomor]`.
- [x] Tambahkan method sanitasi OTP menjadi `[otp]`.
- [x] Tambahkan method sanitasi password/token/API key menjadi `[rahasia]`.
- [x] Tambahkan method sanitasi URL query sensitif menjadi `[url]`.
- [x] Tambahkan method sanitasi NIK/KTP-like numeric ID menjadi `[nomor_identitas]`.
- [x] Pastikan sanitasi diterapkan pada cuplikan sumber dan prompt extraction.
- [ ] Tambahkan unit-level check sederhana bila test suite tersedia.

## 6. Extraction Service

- [x] Buat `AiKnowledgeLearningService`.
- [x] Implement `createDraftFromChat(string $chatId, ?string $userId)`.
- [x] Load chat header dengan customer/instansi/session context.
- [x] Load `TChatD` pesan terbaru dengan limit aman.
- [x] Filter pesan teks kosong dan pesan non-teks.
- [x] Format percakapan dengan speaker `Customer`, `CS`, atau `AI Agent`.
- [x] Potong konteks agar tidak melebihi batas karakter/token.
- [x] Sanitasi konteks sebelum dikirim ke provider.
- [x] Build prompt extraction terstruktur.
- [x] Panggil provider AI aktif.
- [x] Parse JSON response.
- [x] Validasi `layak`, `judul`, `isi`, `tag`, `confidence`.
- [x] Return no-draft result jika `layak = false`.
- [x] Generate normalized content hash.
- [x] Cek duplicate draft dengan hash sama.
- [ ] Cek duplicate knowledge berdasarkan judul/tag.
- [x] Simpan draft status `Draft`.
- [x] Return draft ID dan pesan sukses.

## 7. JSON Parsing and Validation

- [x] Strip markdown code fence jika provider membungkus JSON.
- [x] Decode JSON dengan error handling.
- [x] Validasi tipe field dan panjang maksimal.
- [x] Clamp `confidence` ke 0-100.
- [ ] Reject judul terlalu pendek atau terlalu umum.
- [ ] Reject isi terlalu pendek atau terlalu umum.
- [ ] Reject output yang mengandung placeholder PII tidak aman.
- [x] Simpan alasan provider jika tidak layak.
- [x] Tampilkan error ramah jika JSON invalid.

## 8. Filament Resource: Draft Knowledge AI

- [x] Buat resource Filament untuk `TAiDraftPengetahuan`.
- [x] Tentukan navigation group dan icon.
- [x] Terapkan `canViewAny`, `canCreate`, `canEdit`, `canDelete` berbasis permission.
- [x] Buat table columns status, judul, tag, kategori, confidence, customer/instansi, tanggal.
- [x] Tambahkan badge color per status.
- [x] Tambahkan filters status, tanggal, confidence, provider.
- [x] Tambahkan search judul, isi, tag, ringkasan.
- [x] Buat form edit draft.
- [x] Tampilkan source snippet read-only.
- [x] Tampilkan AI audit read-only.
- [ ] Tambahkan action open source chat jika route tersedia.
- [ ] Tambahkan action open knowledge setelah approve.

## 9. Review Actions

- [x] Implement action `Approve`.
- [x] Approve wajib validasi draft belum disetujui.
- [x] Approve wajib validasi judul/isi/tag final.
- [x] Approve generate `KodePengetahuan` unik.
- [x] Approve create `MPengetahuan` aktif.
- [x] Approve update draft `StatusReview = Disetujui`.
- [x] Approve simpan `IdPengetahuan`, reviewer, `TglReview`, catatan.
- [x] Implement action `Reject` dengan catatan reviewer.
- [x] Implement action `Perlu Revisi` dengan catatan reviewer.
- [x] Implement action `Arsip` dengan catatan reviewer.
- [x] Semua action review harus mencatat reviewer dan waktu.
- [ ] Semua action harus menampilkan notification sukses/gagal.

## 10. Chat Page Integration

- [x] Tambahkan method/action di `ViewChatSession` untuk membuat draft knowledge.
- [x] Tambahkan tombol di view `ViewChatSession` jika ada blade/custom action.
- [x] Tambahkan method/action di `InboxWhatsapp` untuk chat aktif.
- [x] Tambahkan tombol di UI inbox pada area action chat.
- [x] Pastikan tombol hanya tampil untuk permission sesuai keputusan.
- [x] Disable tombol saat sedang proses.
- [x] Tampilkan notification sukses dengan judul draft.
- [x] Tampilkan notification warning jika chat tidak layak.
- [x] Tampilkan notification error jika provider/API key gagal.
- [x] Cegah double-click duplicate dengan lock/check hash/IdChat.

## 11. Optional Ticket Integration

- [ ] Audit relasi ticket ke chat.
- [ ] Jika relasi jelas, tambahkan tombol di halaman ticket detail.
- [ ] Jika tidak jelas, tunda ke fase 2.
- [ ] Pastikan ticket integration tidak memblokir fase 1.

## 12. Knowledge Approval Mapping

- [x] Map `JudulDraft` ke `MPengetahuan.JudulPengetahuan`.
- [x] Map `IsiDraft` ke `MPengetahuan.IsiPengetahuan`.
- [x] Map `TagDraft` ke `MPengetahuan.Tag`.
- [x] Generate `KodePengetahuan` dari slug uppercase dengan collision handling.
- [x] Set `NonAktif = false`.
- [x] Isi timestamp sesuai pola table.
- [ ] Jika table punya kolom user audit, isi pembuat/editor sesuai pola repo.
- [x] Pastikan approved knowledge langsung eligible untuk auto-reply.

## 13. Duplicate Warning

- [x] Implement normalized slug/title comparison.
- [x] Implement hash comparison draft existing.
- [ ] Implement tag overlap check.
- [ ] Tampilkan daftar knowledge mirip di form review jika ditemukan.
- [x] Block approve untuk exact duplicate.
- [ ] Allow approve setelah user mengubah judul/isi jika hanya similar warning.

## 14. Improve Retrieval Scoring

- [x] Refactor `relevantKnowledge()` agar score title/tag/content terpisah.
- [x] Title match mendapat bobot tertinggi.
- [x] Tag match mendapat bobot tinggi.
- [x] Content match mendapat bobot rendah.
- [ ] Tambahkan exact phrase bonus.
- [x] Tambahkan minimum score.
- [x] Tambahkan limit total panjang knowledge dalam prompt.
- [x] Pastikan inactive knowledge tidak ikut.
- [x] Pastikan draft table tidak pernah dibaca auto-reply.

## 15. Permission and Navigation

- [x] Jika pakai existing permission, map resource ke `knowledge.view/manage`.
- [ ] Jika tambah permission baru, update `AccessPermissions`.
- [ ] Jika tambah permission baru, update seeder/menu permission bila ada.
- [x] Pastikan navigation item hanya muncul untuk authorized user.
- [x] Pastikan action button chat hanya muncul untuk authorized user.
- [x] Pastikan direct URL/action tetap 403 untuk unauthorized user.

## 16. Localization

- [x] Tambahkan label menu `Draft Knowledge AI` di `src/resources/lang/id/ui.php`.
- [x] Tambahkan label status review.
- [x] Tambahkan label field draft.
- [x] Tambahkan label action approve/reject/revisi/arsip.
- [x] Tambahkan pesan sukses membuat draft.
- [x] Tambahkan pesan warning chat tidak layak.
- [x] Tambahkan pesan error provider/API key/JSON invalid.
- [x] Tambahkan versi English jika `src/resources/lang/en/ui.php` tersedia.

## 17. Logging and Error Safety

- [ ] Log extraction failure tanpa API key.
- [x] Sanitize provider response before storing/showing.
- [x] Limit stored prompt/response length.
- [x] Avoid storing raw unsanitized chat source in draft.
- [x] Avoid exposing stack trace in UI notification.
- [x] Ensure errors do not create partial approved knowledge.

## 18. Testing: Syntax and Migration

- [x] Jalankan `php -l` untuk service baru.
- [x] Jalankan `php -l` untuk model baru.
- [x] Jalankan `php -l` untuk resource baru.
- [x] Jalankan `php -l` untuk page yang diubah.
- [ ] Jalankan migration di database development setelah backup.
- [ ] Verifikasi rollback jika memungkinkan di database non-production.

## 19. Testing: Functional

- [ ] Test chat valid membuat draft.
- [ ] Test chat sapaan saja tidak membuat draft.
- [ ] Test chat dengan email/nomor menghasilkan snippet tersanitasi.
- [ ] Test API key kosong menghasilkan error ramah.
- [ ] Test provider timeout menghasilkan error ramah.
- [ ] Test JSON invalid menghasilkan error ramah.
- [ ] Test duplicate hash mencegah draft duplikat.
- [ ] Test approve membuat `MPengetahuan` aktif.
- [ ] Test reject tidak membuat `MPengetahuan`.
- [ ] Test perlu revisi tidak membuat `MPengetahuan`.
- [ ] Test arsip menyembunyikan dari queue aktif.
- [ ] Test auto-reply memakai knowledge approved.
- [ ] Test auto-reply tidak memakai draft unapproved.

## 20. Testing: Permissions

- [ ] User tanpa `knowledge.view` tidak melihat menu draft.
- [ ] User tanpa `knowledge.manage` tidak melihat tombol create draft.
- [ ] User tanpa `knowledge.manage` tidak bisa approve/reject via direct action.
- [ ] Authorized user bisa membuat draft dari chat.
- [ ] Authorized reviewer bisa approve.

## 21. Documentation

- [x] Update `docs/PLAN_AI_LEARNING_DARI_CHAT_CUSTOMER.md` sesuai implementasi final.
- [ ] Update README bagian AI Agent bila fitur sudah selesai.
- [ ] Update OpenSpec base spec setelah change diterima.
- [ ] Dokumentasikan SOP review knowledge.
- [ ] Dokumentasikan rollback: disable menu/action dan jangan approve draft baru.

## 22. Deployment Notes

- [ ] Backup database sebelum migration production.
- [ ] Jalankan `php artisan migrate --force`.
- [ ] Jalankan `php artisan optimize:clear`.
- [ ] Jalankan queue worker restart bila service/job baru ditambahkan.
- [ ] Jalankan `npm run build` bila view/frontend asset berubah.
- [ ] Beri akses menu hanya ke role supervisor/admin saat awal rollout.

## 23. Performance and Lightweight Retrieval

- [ ] Audit jumlah maksimum knowledge yang realistis untuk production.
- [x] Tambahkan batas global knowledge context dalam prompt.
- [x] Tambahkan batas panjang per knowledge dalam prompt.
- [x] Tambahkan batas jumlah knowledge top-N, default 3-5.
- [x] Ubah retrieval agar tidak mengambil semua `MPengetahuan` aktif.
- [x] Implement token extraction dari pesan customer terbaru.
- [x] Implement prefilter database berdasarkan token penting.
- [x] Limit kandidat awal dari database, default 30-50 row.
- [x] Implement weighted scoring title/tag/keyword/content.
- [x] Tambahkan minimum score agar knowledge lemah tidak masuk prompt.
- [ ] Tambahkan cache retrieval TTL pendek jika Laravel cache tersedia.
- [ ] Cache hanya menyimpan ID knowledge terpilih, bukan data sensitif penuh.
- [ ] Tambahkan logging durasi retrieval untuk evaluasi performa.

## 24. MPengetahuan Performance Fields

- [x] Evaluasi penambahan kolom `SearchKeywords` pada `MPengetahuan`.
- [x] Evaluasi penambahan kolom `PrioritasAi` pada `MPengetahuan`.
- [x] Evaluasi penambahan kolom `TerakhirDipakaiAi` pada `MPengetahuan`.
- [x] Evaluasi penambahan kolom `JumlahDipakaiAi` pada `MPengetahuan`.
- [x] Tambahkan field `SearchKeywords` dan `PrioritasAi` di form Knowledge Base.
- [x] Saat approve draft, isi `SearchKeywords` dari tag/kata kunci AI.
- [x] Tambahkan index ringan untuk `NonAktif` dan `PrioritasAi`.
- [x] Hindari index pada `nvarchar(max)`.

## 25. Future Embedding Readiness

- [x] Jangan wajibkan embedding pada fase 1.
- [x] Desain kolom/struktur agar embedding bisa ditambahkan kemudian.
- [x] Dokumentasikan kapan embedding diperlukan.
- [ ] Jika embedding ditambahkan, gunakan prefilter keyword dulu sebelum similarity agar tetap ringan.

## 26. Per-Chat CS Controls

- [x] Tambahkan kolom `ModeKnowledgeAi` pada `TChat`.
- [x] Tambahkan kolom `BatasKnowledgeAi` pada `TChat`.
- [x] Tambahkan opsi `Ringan`, `AllKnowledge`, dan `Nonaktif`.
- [x] Tambahkan kontrol mode knowledge di panel chat aktif.
- [x] Pastikan mode per chat tidak mengubah setting global AI Agent.
- [x] Pastikan `AllKnowledge` tetap dibatasi jumlah item dan total karakter prompt.
