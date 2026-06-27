# Change: Reviewed AI Learning dari Chat Customer

## Summary

Tambahkan kemampuan **AI Learning dari Chat Customer** dengan mekanisme aman: percakapan customer tidak langsung menjadi pengetahuan aktif dan tidak dipakai untuk fine-tuning otomatis. Sistem mengekstrak kandidat pengetahuan dari chat, menyimpannya sebagai **Draft Knowledge AI**, lalu admin/supervisor melakukan review sebelum pengetahuan tersebut masuk ke Knowledge Base AI (`MPengetahuan`).

Pendekatan ini disebut **Human-in-the-loop RAG**:

1. Chat customer menjadi sumber insight.
2. AI membantu merangkum insight menjadi kandidat knowledge.
3. Manusia memvalidasi akurasi, kelayakan, dan keamanan data.
4. Knowledge yang disetujui masuk ke `MPengetahuan`.
5. Auto-reply hanya memakai knowledge aktif yang sudah disetujui.

Dengan desain ini, AI Agent dapat “bertambah pengetahuan” dari pengalaman customer service nyata tanpa risiko besar dari auto-training mentah.

## Problem Statement

Saat ini AI Agent sudah dapat menjawab memakai prompt, riwayat chat, dan `MPengetahuan`. Namun pengetahuan baru masih perlu dimasukkan manual oleh admin. Banyak knowledge operasional sebenarnya muncul dari chat harian, misalnya:

- Cara menangani error tertentu.
- Pertanyaan berulang customer.
- Prosedur layanan yang dijelaskan CS.
- Klarifikasi harga, jadwal, SLA, dokumen, atau alur support.
- Jawaban yang terbukti berhasil menyelesaikan masalah customer.

Jika semua chat langsung dipakai sebagai “training data”, risikonya tinggi:

- Chat bisa mengandung data pribadi customer.
- Customer bisa menyampaikan asumsi yang salah.
- CS bisa salah jawab, typo, atau memberi pengecualian khusus.
- Percakapan bisa emosional dan tidak cocok menjadi knowledge resmi.
- Provider AI bisa mengingat atau menyimpan data jika dikirim sebagai training permanen.

Karena itu, sistem perlu pipeline yang memanfaatkan chat sebagai sumber pembelajaran, tetapi tetap ada kontrol manusia.

## Current State

### Existing AI Auto Reply

- Service utama: `src/app/Services/Ai/AiAutoReplyService.php`.
- Prompt dibuat dari:
  - prompt sistem AI Agent,
  - customer/instansi,
  - jenis chat,
  - template mode,
  - knowledge internal dari `MPengetahuan`,
  - riwayat chat terakhir dari `TChatD`.
- Provider AI didukung melalui setting `MPengaturanAi`.
- Mode pengiriman mendukung `DraftLokal` dan `KirimWaha`.

### Existing Knowledge Base

- Knowledge aktif tersimpan di `MPengetahuan`.
- UI pengelolaan ada di `src/app/Filament/Resources/Master/Pengetahuans/PengetahuanResource.php`.
- Permission knowledge sudah ada:
  - `knowledge.view`
  - `knowledge.manage`
- Retrieval knowledge saat ini masih berbasis keyword sederhana.

### Gap

- Belum ada draft workflow dari chat customer.
- Belum ada review flow untuk knowledge hasil AI.
- Belum ada sanitasi PII untuk knowledge extraction.
- Belum ada audit siapa membuat/approve/reject knowledge dari chat.
- Belum ada deduplication warning untuk knowledge mirip.
- Belum ada quality gate agar chat tidak layak tidak menjadi knowledge.

## Goals

- Membuat AI Agent bisa bertambah pengetahuan dari chat customer secara aman.
- Membuat draft knowledge dari chat menggunakan AI.
- Mewajibkan review manusia sebelum knowledge aktif.
- Menyimpan audit trail sumber chat, pembuat draft, reviewer, status review, dan waktu review.
- Menyamarkan data sensitif dasar sebelum menyimpan cuplikan sumber.
- Menyediakan UI review yang jelas untuk approve, reject, revisi, dan arsip.
- Mengintegrasikan hasil approve ke `MPengetahuan` existing.
- Memastikan auto-reply hanya memakai knowledge approved.
- Memperbaiki retrieval keyword agar knowledge approved lebih relevan dipakai.
- Menyiapkan fondasi untuk otomatisasi dan vector search di fase lanjutan.

## Non-Goals

- Tidak melakukan fine-tuning model otomatis.
- Tidak mengirim semua histori chat ke provider sebagai training permanen.
- Tidak membuat knowledge aktif tanpa review manusia.
- Tidak memakai draft knowledge langsung di auto-reply.
- Tidak membangun vector database di fase pertama.
- Tidak mengubah kontrak webhook WAHA.
- Tidak mengganti provider AI existing.
- Tidak mengubah schema chat utama kecuali relasi/read-only yang diperlukan.
- Tidak membuat jawaban AI lintas customer dari chat mentah yang belum disanitasi.

## Recommended Approach

### Why Not Fine-Tuning Now

Fine-tuning tidak disarankan untuk kebutuhan ini karena:

- Knowledge operasional sering berubah.
- Fine-tuning sulit memperbaiki informasi salah dengan cepat.
- Data chat customer mengandung PII.
- Fine-tuning cocok untuk style/format jawaban, bukan knowledge yang sering berubah.
- RAG dengan knowledge base lebih mudah diaudit dan diperbarui.

### Why Human-in-the-loop RAG

Human-in-the-loop RAG cocok karena:

- Source code sudah punya `MPengetahuan`.
- Auto-reply sudah membaca `MPengetahuan`.
- Admin bisa mengontrol kualitas knowledge.
- Knowledge bisa langsung diperbaiki tanpa train ulang model.
- Risiko PII dan informasi salah lebih rendah.

## Proposed User Flow

### Flow A: Membuat Draft dari Chat

1. User membuka chat di Inbox/Histori.
2. User klik **Buat Draft Knowledge AI**.
3. Sistem cek permission `knowledge.manage`.
4. Sistem cek chat punya pesan teks cukup.
5. Sistem mengambil riwayat chat terbatas.
6. Sistem sanitasi data sensitif dari konteks.
7. Sistem memanggil provider AI aktif untuk ekstraksi.
8. AI mengembalikan JSON kandidat knowledge.
9. Sistem validasi JSON dan kualitas minimal.
10. Sistem menyimpan `TAiDraftPengetahuan` status `Draft`.
11. User mendapat notifikasi dan bisa membuka halaman draft.

### Flow B: Review Draft

1. Admin/supervisor membuka menu **Draft Knowledge AI**.
2. Admin melihat draft, ringkasan sumber, confidence, dan cuplikan tersanitasi.
3. Admin mengedit judul, isi, tag, dan kategori jika perlu.
4. Admin memilih:
   - Approve,
   - Reject,
   - Perlu Revisi,
   - Arsip.
5. Jika approve, sistem membuat/update `MPengetahuan`.
6. Draft berubah status `Disetujui` dan menyimpan `IdPengetahuan`.

### Flow C: Auto Reply Memakai Knowledge Baru

1. Draft sudah approve ke `MPengetahuan` aktif.
2. Customer baru bertanya topik relevan.
3. `AiAutoReplyService` mencari knowledge relevan.
4. Knowledge approved masuk prompt.
5. AI menjawab berdasarkan knowledge tersebut.

## Data Model

### New Table: `TAiDraftPengetahuan`

Tabel ini menyimpan kandidat knowledge sebelum menjadi `MPengetahuan`.

Recommended columns:

- `Id` uniqueidentifier primary key.
- `IdChat` uniqueidentifier nullable, referensi chat sumber.
- `IdCustomer` uniqueidentifier nullable, customer sumber jika tersedia.
- `IdInstansi` uniqueidentifier nullable, instansi sumber jika tersedia.
- `IdPengetahuan` uniqueidentifier nullable, terisi setelah approve.
- `JudulDraft` nvarchar(255) not null.
- `IsiDraft` nvarchar(max) not null.
- `TagDraft` nvarchar(500) nullable.
- `KategoriDraft` nvarchar(100) nullable.
- `RingkasanSumber` nvarchar(max) nullable.
- `CuplikanSumberDisanitasi` nvarchar(max) nullable.
- `ConfidenceScore` decimal(5,2) nullable.
- `StatusReview` nvarchar(30) not null default `Draft`.
- `CatatanReviewer` nvarchar(max) nullable.
- `AlasanTidakLayak` nvarchar(max) nullable.
- `HashKonten` nvarchar(64) nullable untuk deduplication awal.
- `ProviderAi` nvarchar(50) nullable.
- `ModelAi` nvarchar(100) nullable.
- `PromptRingkas` nvarchar(max) nullable untuk audit terbatas.
- `ResponseJson` nvarchar(max) nullable untuk audit provider, tanpa secret.
- `DibuatOlehAi` bit not null default 1.
- `DibuatOleh` uniqueidentifier nullable.
- `DireviewOleh` uniqueidentifier nullable.
- `TglReview` datetime2 nullable.
- `TglBuat` datetime2 not null.
- `TglEdit` datetime2 nullable.

### StatusReview Values

- `Draft`: hasil ekstraksi awal, belum direview.
- `PerluRevisi`: reviewer menilai perlu perbaikan sebelum approve.
- `Disetujui`: sudah menjadi `MPengetahuan`.
- `Ditolak`: tidak layak menjadi knowledge.
- `Diarsipkan`: disembunyikan dari antrian aktif tanpa dihapus.

### Recommended Indexes

- `IX_TAiDraftPengetahuan_StatusReview_TglBuat`
- `IX_TAiDraftPengetahuan_IdChat`
- `IX_TAiDraftPengetahuan_IdPengetahuan`
- `IX_TAiDraftPengetahuan_HashKonten`
- `IX_TAiDraftPengetahuan_IdCustomer`
- `IX_TAiDraftPengetahuan_IdInstansi`

### Optional Future Table: `TAiKnowledgeExtractionLog`

Tidak wajib fase awal. Bisa ditambahkan jika perlu log detail per attempt.

## Service Design

### New Service: `AiKnowledgeLearningService`

File target:

- `src/app/Services/Ai/AiKnowledgeLearningService.php`

Responsibilities:

- Load AI settings from `MPengaturanAi`.
- Load chat header and detail from `TChat`/`TChatD`.
- Build safe extraction context.
- Sanitize sensitive text.
- Call selected AI provider.
- Parse structured JSON.
- Validate draft candidate.
- Detect duplicate candidate.
- Store draft knowledge.
- Return success/error result for UI.

### Public Methods

Recommended methods:

- `createDraftFromChat(string $chatId, ?string $userId = null): array`
- `extractCandidate(object $settings, object $chat, string $context): array`
- `sanitizeText(string $text): string`
- `approveDraft(string $draftId, string $reviewerId, array $data): string`
- `rejectDraft(string $draftId, string $reviewerId, ?string $note): void`
- `markNeedsRevision(string $draftId, string $reviewerId, ?string $note): void`
- `archiveDraft(string $draftId, string $reviewerId, ?string $note): void`

### Result Contract

`createDraftFromChat()` should return:

```php
[
    'ok' => true,
    'draft_id' => '...',
    'message' => 'Draft knowledge berhasil dibuat.',
]
```

Or:

```php
[
    'ok' => false,
    'reason' => 'Chat tidak memiliki informasi reusable.',
]
```

## Extraction Prompt Requirements

Prompt extraction must instruct AI to:

- Output valid JSON only.
- Use Indonesian language for knowledge content.
- Extract only reusable customer-service knowledge.
- Avoid personal data.
- Avoid customer-specific promises.
- Avoid facts not proven by chat.
- Mark `layak = false` if conversation has no reusable knowledge.
- Keep content concise and operational.
- Produce tags as comma-separated keywords.
- Include confidence score 0-100.

### Expected JSON When Suitable

```json
{
  "layak": true,
  "judul": "Cara reset password pelanggan",
  "isi": "Jika pelanggan lupa password, arahkan pelanggan membuka menu Lupa Password, memasukkan email terdaftar, lalu mengikuti tautan reset yang dikirim melalui email.",
  "tag": "password, reset, login",
  "kategori": "Akun",
  "confidence": 86,
  "ringkasan_sumber": "CS menjelaskan prosedur reset password dan customer berhasil mengikuti instruksi."
}
```

### Expected JSON When Not Suitable

```json
{
  "layak": false,
  "alasan": "Percakapan hanya berisi sapaan dan ucapan terima kasih."
}
```

## PII and Sensitive Data Handling

### Must Sanitize

- Email addresses: `[email]`.
- Long phone numbers: `[nomor]`.
- OTP codes: `[otp]`.
- Password-like phrases: `[rahasia]`.
- API keys/tokens: `[rahasia]`.
- URLs with query token/session: `[url]`.
- NIK/KTP-like long numeric IDs: `[nomor_identitas]`.

### Must Not Include in Knowledge

- Nama pribadi customer jika tidak relevan.
- Nomor WhatsApp customer.
- Alamat detail customer.
- Screenshot/media content yang belum dianalisis aman.
- Credential, token, OTP, password.
- Komplain emosional sebagai fakta umum.
- Janji pengecualian khusus untuk satu customer.

## UI Design

### Menu: Draft Knowledge AI

Recommended location:

- Group/menu AI Agent atau Master Data Knowledge, mengikuti struktur navigasi existing.

Table columns:

- Status.
- Judul Draft.
- Tag.
- Kategori.
- Customer/Instansi.
- Confidence.
- Dibuat oleh.
- Tanggal dibuat.
- Reviewer.
- Tanggal review.

Filters:

- Status review.
- Tanggal dibuat.
- Customer/Instansi.
- Confidence range.
- Provider AI.

Actions:

- View source.
- Edit draft.
- Approve.
- Reject.
- Mark Perlu Revisi.
- Archive.
- Open source chat.
- Open generated knowledge after approve.

### Form Fields

Editable:

- Judul draft.
- Isi draft.
- Tag draft.
- Kategori draft.
- Catatan reviewer.

Read-only:

- Chat source.
- Customer/instansi.
- Ringkasan sumber.
- Cuplikan sumber tersanitasi.
- Confidence score.
- Provider/model.
- Status audit.

### Chat Page Button

Button label:

- `Buat Draft Knowledge AI`

Initial placement priority:

1. `ViewChatSession` / Histori detail.
2. `InboxWhatsapp` active chat header/actions.
3. `Ticketing` after chat linkage confirmed.

Button behavior:

- Visible only for user with `knowledge.manage` or new `knowledge.learn`.
- Disabled while processing.
- Shows success notification with draft title/ID.
- Shows friendly error for no valid messages, provider missing, API error, duplicate, or low-quality result.

## Permission Design

### Option A: Reuse Existing Permission

Use:

- `knowledge.view`: view draft list/detail.
- `knowledge.manage`: create draft, edit draft, approve/reject/archive.

Pros:

- Minimal schema/seed changes.
- Fits existing access model.

Cons:

- Cannot separate “make draft from chat” from “approve official knowledge”.

### Option B: Add New Permission

Add:

- `knowledge.learn`: create draft from chat.
- `knowledge.review`: approve/reject draft.

Pros:

- More granular.
- CS can suggest draft without approving official knowledge.

Cons:

- More changes to permission seed/UI.

### Recommendation

Use Option A for phase 1 unless user needs different roles. Add Option B in phase 2 if role separation is needed.

## Duplicate Handling

### Phase 1 Deduplication

Before creating or approving:

- Normalize title and content.
- Generate `HashKonten`.
- Check existing draft with same hash.
- Check `MPengetahuan` with same/similar title slug.
- Check tag overlap.
- Warn user if similar knowledge exists.

### Approval Behavior

Phase 1:

- Default approve creates new `MPengetahuan`.
- If exact duplicate exists, block approve or require edit.

Phase 2:

- Add option “Update existing knowledge”.
- Add merge workflow.

## Retrieval Improvement

Existing `relevantKnowledge()` can be improved without vector DB.

Proposed scoring:

- Match title token: +5.
- Match tag token: +4.
- Match content token: +1.
- Exact phrase in title/tag: bonus +5.
- Minimum score: configurable constant, e.g. 4.
- Limit selected knowledge: max 5 items.
- Limit total inserted content: e.g. 3500 chars.

Important rule:

- Only `MPengetahuan.NonAktif = false` can be used.
- `TAiDraftPengetahuan` must never be used directly in auto-reply.

## Error Handling

User-facing errors should be clear:

- `Chat tidak ditemukan.`
- `Chat belum memiliki pesan teks yang cukup untuk dibuat knowledge.`
- `Provider AI belum dikonfigurasi.`
- `API key provider AI belum diisi.`
- `Provider AI tidak mengembalikan JSON valid.`
- `Percakapan tidak mengandung pengetahuan reusable.`
- `Draft serupa sudah pernah dibuat.`
- `Anda tidak memiliki izin kelola knowledge.`

Internal logs may store provider status/body summary, but must not expose API key or secrets.

## Audit Requirements

The system must store:

- Chat source ID.
- Customer/instansi source when available.
- Draft creator.
- Provider/model used.
- Status review.
- Reviewer.
- Review timestamp.
- Notes for rejection/revision/archive.
- Knowledge ID generated after approval.

## Rollout Plan

### Phase 1: Manual Reviewed Learning

- Create database table/model.
- Create extraction service.
- Create review resource.
- Add manual button on chat detail.
- Approve into `MPengetahuan`.
- Add basic duplicate checks.
- Add sanitization.

### Phase 2: Quality Controls

- Improve duplicate detection.
- Add update existing knowledge flow.
- Add confidence filters and reporting.
- Improve `relevantKnowledge()` scoring.
- Add more robust JSON repair/fallback.

### Phase 3: Controlled Automation

- Add scheduled job for closed/resolved chats.
- Add daily limits.
- Add queue processing.
- Add config switches for auto-draft.
- Add minimum confidence threshold.

### Phase 4: Semantic Search

- Add embeddings for `MPengetahuan`.
- Add vector/similarity search.
- Add embedding refresh on knowledge changes.
- Use similarity for deduplication and retrieval.

## Acceptance Criteria

- User with permission can create draft knowledge from chat.
- User without permission cannot see or execute extraction actions.
- Chat with reusable knowledge creates `TAiDraftPengetahuan` with status `Draft`.
- Chat without reusable knowledge returns friendly no-draft message.
- Sensitive data in source snippet is masked.
- Draft knowledge is not used by auto-reply before approval.
- Reviewer can edit draft before approval.
- Approving draft creates active `MPengetahuan`.
- Rejecting draft does not create `MPengetahuan`.
- Approved draft stores reviewer, review time, and generated knowledge ID.
- Duplicate draft/knowledge is detected or warned.
- Auto-reply can use approved knowledge based on relevance.
- Existing provider settings and WAHA flow remain unchanged.

## Open Questions

- Apakah perlu permission granular `knowledge.learn` dan `knowledge.review`, atau cukup `knowledge.manage`?
- Apakah approval fase 1 cukup create knowledge baru, atau harus bisa update existing juga?
- Apakah ekstraksi boleh memakai provider aktif AI Agent, atau perlu provider khusus learning?
- Apakah tombol dibuat dulu hanya di histori chat, atau juga langsung di inbox aktif?
- Apakah draft perlu menyimpan response JSON penuh provider untuk audit, atau cukup ringkasan?

## Performance Reassessment: Knowledge Besar

Jika jumlah knowledge bertambah banyak, bottleneck utama bukan hanya koneksi HTTP ke provider AI, tetapi juga:

- Query database yang membaca terlalu banyak knowledge.
- Scoring PHP terhadap banyak baris.
- Prompt terlalu panjang karena terlalu banyak knowledge dimasukkan.
- Token input provider AI meningkat sehingga respons lebih lambat dan lebih mahal.
- Knowledge tidak relevan ikut masuk prompt dan menurunkan kualitas jawaban.

Karena itu, strategi retrieval harus dibuat ringan sejak awal.

## Lightweight Retrieval Strategy

### Principle

Auto-reply SHALL NOT mengirim semua knowledge ke provider AI. Sistem harus memilih sedikit knowledge paling relevan sebelum request AI.

Target awal:

- Ambil kandidat kecil dari database.
- Score kandidat secara lokal.
- Kirim maksimal 3-5 knowledge relevan.
- Batasi total knowledge context, misalnya 2500-3500 karakter.
- Cache hasil tokenisasi/keyword untuk mengurangi kerja berulang.

## Retrieval Phases

### Phase 1: Indexed Keyword Retrieval

Tambahkan kolom ringan di `MPengetahuan` atau tabel pendukung:

- `SearchText` nvarchar(max) nullable: gabungan normalized judul, tag, isi.
- `SearchKeywords` nvarchar(1000) nullable: keyword penting hasil ekstraksi lokal/admin.
- `PrioritasAi` int default 0: menaikkan knowledge penting.
- `TerakhirDipakaiAi` datetime2 nullable.
- `JumlahDipakaiAi` int default 0.

Query awal tidak membaca semua knowledge. Query menggunakan:

- `NonAktif = false`.
- Match tag/judul/search keyword dengan `LIKE` untuk token penting.
- Limit kandidat, misalnya 30-50 row.
- Baru setelah itu scoring detail di PHP.

Kelebihan:

- Tidak perlu dependency baru.
- Cocok untuk SQL Server existing.
- Implementasi cepat.

Kekurangan:

- Belum semantic search.
- Sinonim atau bahasa berbeda bisa luput.

### Phase 2: Cached Retrieval

Tambahkan cache retrieval per konteks singkat:

- Cache key dari normalized customer message + customer/instansi + top tokens.
- TTL pendek, misalnya 5-30 menit.
- Cache menyimpan ID knowledge terpilih, bukan prompt penuh.

Manfaat:

- Chat berulang dengan topik sama lebih cepat.
- Mengurangi query/scoring berulang.

### Phase 3: Embedding Optional

Jika knowledge sudah ratusan/ribuan dan keyword tidak cukup, tambahkan embedding.

Opsi deployment ringan:

- Simpan vector embedding di SQL Server sebagai JSON/nvarchar atau varbinary.
- Hitung similarity di PHP untuk kandidat kecil hasil prefilter keyword.
- Tidak langsung butuh vector database eksternal.

Opsi deployment besar:

- Pakai vector database eksternal hanya jika volume sangat besar dan latency keyword sudah tidak cukup.

Embedding SHALL be optional, not required for phase 1.

## Recommended Retrieval Algorithm Phase 1

1. Ambil pesan customer terbaru dan beberapa token penting dari riwayat chat.
2. Buang stopwords umum.
3. Pilih maksimal 8-12 token unik terkuat.
4. Query `MPengetahuan` aktif dengan prefilter:
   - judul/tag/search keywords mengandung token,
   - prioritas tinggi,
   - limit 50.
5. Score kandidat:
   - exact phrase di judul: +10,
   - token di judul: +5,
   - token di tag: +4,
   - token di search keywords: +3,
   - token di isi: +1,
   - prioritas AI: +0 sampai +5,
   - penalti jika isi terlalu panjang/tidak spesifik.
6. Ambil top 3-5 dengan score minimum.
7. Potong isi tiap knowledge maksimal 700-900 karakter.
8. Batasi total knowledge context maksimal 2500-3500 karakter.
9. Cache ID knowledge hasil retrieval untuk TTL pendek.
10. Update statistik pemakaian secara non-blocking jika memungkinkan.

## Prompt Budget Rule

Auto-reply prompt harus punya batas jelas:

- Riwayat chat: maksimal sesuai `BatasRiwayatPesan`, tetap capped.
- Knowledge context: maksimal 2500-3500 karakter.
- Per knowledge: maksimal 700-900 karakter.
- Jumlah knowledge: maksimal 5.
- Jika tidak ada score cukup, jangan masukkan knowledge.

## Database Impact for Performance

Fase awal dapat menambahkan migration opsional ke `MPengetahuan`:

- `SearchKeywords` nvarchar(1000) nullable.
- `PrioritasAi` int not null default 0.
- `TerakhirDipakaiAi` datetime2 nullable.
- `JumlahDipakaiAi` int not null default 0.

`SearchText` boleh tidak disimpan jika ingin minimal schema. Namun `SearchKeywords` dan `PrioritasAi` sangat membantu.

Index rekomendasi:

- `IX_MPengetahuan_NonAktif_PrioritasAi`
- `IX_MPengetahuan_TerakhirDipakaiAi`

Catatan SQL Server: index untuk `nvarchar(max)` tidak efektif. Karena itu `SearchKeywords` dibuat `nvarchar(1000)`, bukan max.

## Draft Learning Impact on Performance

Draft knowledge tidak boleh langsung menambah beban auto-reply karena:

- Draft disimpan di `TAiDraftPengetahuan`.
- Auto-reply hanya membaca `MPengetahuan` aktif.
- Saat approve, reviewer wajib isi tag/keyword yang jelas.
- Knowledge baru sebaiknya ringkas dan spesifik.

## Operational Controls

Untuk menjaga koneksi AI tetap cepat:

- Batasi jumlah knowledge aktif yang dikirim ke prompt.
- Buat knowledge per topik kecil, bukan artikel panjang.
- Gunakan tag konsisten.
- Tambahkan prioritas untuk SOP penting.
- Nonaktifkan knowledge usang.
- Review statistik knowledge yang sering/tidak pernah dipakai.
- Hindari auto-approve knowledge hasil chat.

## Revised Recommendation

Implementasi terbaik menjadi:

1. Fase 1 tetap draft-review-approve.
2. Sekaligus refactor retrieval agar tidak membaca semua knowledge.
3. Tambahkan `SearchKeywords` dan `PrioritasAi` pada `MPengetahuan`.
4. Approval draft wajib menghasilkan tag/keyword yang bagus.
5. Auto-reply hanya mengirim top 3-5 knowledge dengan budget karakter ketat.
6. Embedding ditunda sampai volume knowledge besar dan keyword retrieval terbukti kurang.

## Addendum: Per-Chat Knowledge Mode by CS

CS yang sedang menangani chat SHALL be able to change AI knowledge behavior for that chat from the chat panel.

Available modes:

- `Ringan`: default, fast retrieval, only top relevant knowledge is sent to AI.
- `AllKnowledge`: broader retrieval, more approved knowledge can be inserted into prompt, useful for difficult chats but slower and more expensive.
- `Nonaktif`: AI does not use Knowledge Base for that chat.

Rules:

- Mode is stored on `TChat.ModeKnowledgeAi`.
- Optional limit is stored on `TChat.BatasKnowledgeAi`.
- Mode affects only that chat session.
- Global AI Agent settings remain unchanged.
- `AllKnowledge` still uses prompt budget limits and SHALL NOT send unlimited knowledge.
- Draft knowledge remains excluded until approved.
