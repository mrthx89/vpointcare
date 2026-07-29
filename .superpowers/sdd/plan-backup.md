# Inbox WhatsApp Improvement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membuat Inbox WhatsApp dapat membaca dan mengunduh media embedded Base64 secara aman serta menampilkan identitas WhatsApp asli atau mapping internal tanpa mencampur identitas grup dengan anggota pengirim.

**Architecture:** Tambahkan satu helper stateless WahaMediaPayload untuk ekstraksi Base64, MIME resolution, kategori preview, dan sanitasi filename. WahaMediaController tetap menjadi satu-satunya endpoint media; InboxWhatsapp hanya membawa metadata dan route, sedangkan Blade memilih proyeksi identitas aktif dan renderer media sesuai kategori.

**Tech Stack:** PHP 8.3+, Laravel 13, Filament 5, Livewire, PHPUnit 12, Symfony Mime 7.4 yang sudah terpasang, Microsoft SQL Server untuk produksi, SQLite in-memory untuk test terisolasi, Blade, Vite, dan Tailwind CSS 4.

## Global Constraints

- Sumber kebenaran scope adalah openspec/changes/inbox-whatsapp-improvement.
- Pertahankan route GET /admin/waha-media/{message}, nama route admin.waha-media.show, dan middleware auth.
- Pertahankan MPengguna sebagai sumber autentikasi.
- Pertahankan normalisasi WAHA untuk @c.us, @s.whatsapp.net, @g.us, dan @lid.
- Tidak ada migration, perubahan schema, backfill data, dependency baru, queue, event, scheduler, atau environment variable baru.
- Jangan menyimpan Base64 ke UrlMedia, disk, cache, log, atau state Livewire.
- Jangan mencatat PayloadJson, body media, URL signed lengkap, WAHA API key, webhook token, password, atau access token.
- Preview inline hanya untuk JPEG, PNG, GIF, WebP, audio/*, video/*, dan application/pdf; SVG, HTML, executable, dan MIME unknown harus attachment.
- Semua label user-facing wajib tersedia dalam Bahasa Indonesia dan Inggris.
- Test database tidak menjalankan migration WACS yang khusus sqlsrv; setiap feature test membuat tabel SQLite minimum yang dibutuhkannya.
- Gunakan TDD: buat test gagal, jalankan dan buktikan gagal, implementasikan minimum, lalu jalankan kembali hingga lulus.
- Jangan mengubah ViewChatSession, WahaWebhookProcessor, model master, migration, DATABASE_SCHEMA_WACS.sql, route, vendor, generated asset, atau lock file untuk change ini.
- Jangan melakukan commit Git tanpa instruksi eksplisit pengguna.

---

## File Map

### File baru

- src/app/Support/WahaMediaPayload.php — parser dan normalizer media embedded/data URI/binary.
- src/tests/Unit/Support/WahaMediaPayloadTest.php — unit test ekstraksi, MIME, signature, filename, kategori, dan keamanan.
- src/tests/Feature/Http/Controllers/WahaMediaControllerTest.php — route auth, fallback sumber media, preview/download, header, dan log aman.
- src/tests/Feature/Filament/Pages/InboxWhatsappTest.php — schema SQLite minimum, Livewire state, media route, identitas grup/pribadi, dan render Blade.

### File diubah

- src/app/Http/Controllers/WahaMediaController.php:16-251 — pilih UrlMedia atau PayloadJson, normalisasi respons, download, dan sanitasi log/error.
- src/app/Filament/Pages/InboxWhatsapp.php:87-1854 — identityDisplayMode, pemisahan proyeksi identitas, payload media, sender grup, dan route download.
- src/resources/views/filament/pages/inbox-whatsapp.blade.php:150-590 — toggle identitas, daftar/header/detail, sender grup, preview, dan download.
- src/resources/lang/id/ui.php:7-13 dan 700-814 — error controller dan label Inbox baru.
- src/resources/lang/en/ui.php:7-13 dan 700-814 — padanan Inggris.
- openspec/changes/inbox-whatsapp-improvement/tasks.md — centang hanya task yang benar-benar sudah diverifikasi.

### File hanya sebagai referensi

- src/routes/web.php:31-33 — route media tetap.
- src/app/Services/Waha/WahaWebhookProcessor.php:224-275 dan 388-431 — bentuk payload, MIME, filename, URL, group JID, dan participant.
- src/app/Support/WahaChatHelper.php:9-43 — gunakan normalizeChatId() dan normalizePhoneNumber(); jangan duplikasi normalisasi.
- src/script/DATABASE_SCHEMA_WACS.sql:172-209 dan 497-574 — kontrak tabel MGrupWhatsapp, TChat, dan TChatD.
- src/database/migrations/2026_04_27_000002_add_whatsapp_group_mapping.php:62-110 — kolom grup dan pengirim.
- src/app/Models/Master/GrupWhatsapp.php dan NomorWhatsapp.php — bentuk mapping internal; tidak diubah.

---

### Task 1: Kunci Kontrak Parser dan Kasus Payload

**Tujuan:** Menetapkan interface helper dan matriks payload sebelum menulis parser agar key media, false positive, dan return shape tidak berubah di tengah implementasi.

**Dependency:** Tidak ada.

**Files:**
- Create: src/tests/Unit/Support/WahaMediaPayloadTest.php
- Reference: src/app/Services/Waha/WahaWebhookProcessor.php:224-431
- Reference: openspec/changes/inbox-whatsapp-improvement/design.md

**Interfaces:**
- Consumes: JSON string dari TChatD.PayloadJson, TipeMime, NamaFileMedia, dan JenisPesan.
- Produces untuk Task 2:
  - WahaMediaPayload::inspectPayload(?string $payloadJson, ?string $declaredMime, ?string $declaredFileName, ?string $messageType): ?array
  - WahaMediaPayload::fromPayloadJson(?string $payloadJson, ?string $declaredMime, ?string $declaredFileName, ?string $messageType): ?array
  - Return inspectPayload: array{source:string,mime_type:string,file_name:string,category:string,inline:bool}|null
  - Return fromPayloadJson: array{source:string,contents:string,mime_type:string,file_name:string,category:string,inline:bool}|null

**Test yang ditambahkan:** nested media.data, root base64, dataUrl, raw body dengan media context, teks biasa pada body, JSON invalid, Base64 invalid, dan hasil decode kosong.

- [ ] **Step 1: Buat unit test dengan payload konkret**

Tambahkan class berikut. Nilai PAYLOAD_BASE64 sengaja tidak disimpan sebagai constant produksi; test memakai string kecil agar tidak memenuhi log atau output test.

~~~php
<?php

namespace Tests\Unit\Support;

use App\Support\WahaMediaPayload;
use PHPUnit\Framework\TestCase;

class WahaMediaPayloadTest extends TestCase
{
    public function test_inspects_nested_media_without_exposing_base64(): void
    {
        $encoded = base64_encode("ID3audio");
        $payload = json_encode([
            'hasMedia' => true,
            'type' => 'ptt',
            'media' => [
                'data' => $encoded,
                'mimetype' => 'audio/ogg; codecs=opus',
                'filename' => 'voice-note.ogg',
            ],
        ], JSON_THROW_ON_ERROR);

        $media = WahaMediaPayload::inspectPayload($payload, null, null, 'Audio');

        self::assertSame('media.data', $media['source']);
        self::assertSame('audio/ogg', $media['mime_type']);
        self::assertSame('voice-note.ogg', $media['file_name']);
        self::assertSame('audio', $media['category']);
        self::assertTrue($media['inline']);
        self::assertStringNotContainsString($encoded, json_encode($media, JSON_THROW_ON_ERROR));
    }

    public function test_decodes_supported_root_base64_with_media_context(): void
    {
        $payload = json_encode([
            'hasMedia' => true,
            'type' => 'document',
            'base64' => base64_encode("%PDF-1.4
%%EOF"),
            'mimetype' => 'application/pdf',
            'filename' => 'invoice.pdf',
        ], JSON_THROW_ON_ERROR);

        $media = WahaMediaPayload::fromPayloadJson($payload, null, null, 'Dokumen');

        self::assertSame("%PDF-1.4
%%EOF", $media['contents']);
        self::assertSame('application/pdf', $media['mime_type']);
        self::assertSame('pdf', $media['category']);
    }

    public function test_rejects_plain_text_body_and_malformed_base64(): void
    {
        $plainText = json_encode(['body' => 'Halo customer'], JSON_THROW_ON_ERROR);
        $invalid = json_encode([
            'hasMedia' => true,
            'type' => 'video',
            'body' => '%%%not-base64%%%',
            'mimetype' => 'video/mp4',
        ], JSON_THROW_ON_ERROR);

        self::assertNull(WahaMediaPayload::inspectPayload($plainText, null, null, 'Teks'));
        self::assertNull(WahaMediaPayload::fromPayloadJson($invalid, null, null, 'Video'));
        self::assertNull(WahaMediaPayload::fromPayloadJson('{invalid-json', null, null, 'Dokumen'));
    }
}
~~~

- [ ] **Step 2: Jalankan test untuk membuktikan kondisi merah**

Run:

~~~powershell
cd src
php artisan test --filter=WahaMediaPayloadTest
~~~

Expected: FAIL karena class App\Support\WahaMediaPayload belum ada. Tidak boleh gagal karena syntax test.

- [ ] **Step 3: Catat aturan candidate extraction dalam test data provider**

Tambahkan data provider yang memverifikasi daftar sumber berikut secara eksplisit: dataUrl, data_url, media.dataUrl, media.data_url, base64, media.base64, media.data, media.file, file.data, dan body. Root data/file/body hanya valid bila payload memiliki hasMedia=true, MIME media, filename media, atau JenisPesan bukan Teks. Nested media.* dan data URI sudah menjadi konteks media.

- [ ] **Step 4: Jalankan kembali test dan simpan output merah sebagai gate Task 2**

Run: php artisan test --filter=WahaMediaPayloadTest

Expected: FAIL hanya karena helper/interface belum diimplementasikan.

---

### Task 2: Implementasikan Ekstraksi Data URI dan Base64 Strict

**Tujuan:** Membuat parser stateless yang menemukan kandidat media tanpa membawa Base64 ke Livewire dan mendekode konten hanya pada endpoint media.

**Dependency:** Task 1.

**Files:**
- Create: src/app/Support/WahaMediaPayload.php
- Modify: src/tests/Unit/Support/WahaMediaPayloadTest.php

**Interfaces:**
- Consumes: interface Task 1.
- Produces untuk Task 3-7: inspectPayload(), fromPayloadJson(), fromDataUri(), dan fromBinary() dengan return shape konsisten.

**Langkah implementasi:**

- [ ] **Step 1: Buat class final dan signature public yang tetap**

~~~php
<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class WahaMediaPayload
{
    public static function inspectPayload(
        ?string $payloadJson,
        ?string $declaredMime = null,
        ?string $declaredFileName = null,
        ?string $messageType = null,
    ): ?array;

    public static function fromPayloadJson(
        ?string $payloadJson,
        ?string $declaredMime = null,
        ?string $declaredFileName = null,
        ?string $messageType = null,
    ): ?array;

    public static function fromDataUri(
        string $dataUri,
        ?string $declaredMime = null,
        ?string $declaredFileName = null,
        ?string $messageType = null,
    ): ?array;

    public static function fromBinary(
        string $contents,
        ?string $declaredMime = null,
        ?string $payloadMime = null,
        ?string $declaredFileName = null,
        ?string $payloadFileName = null,
        ?string $messageType = null,
        string $source = 'binary',
    ): array;
}
~~~

Jangan membuat interface, DTO, factory, service provider, config, atau exception class baru. Satu helper array-based cukup untuk scope ini.

- [ ] **Step 2: Implementasikan candidate() private dengan urutan deterministik**

Urutan key: dataUrl, data_url, media.dataUrl, media.data_url, base64, media.base64, media.data, media.file, file.data, file.base64, data, file, body. Ambil metadata MIME dari media.mimetype, media.mimeType, media.contentType, mimetype, mimeType, contentType, file.mimetype, file.mimeType, dan _data.mimetype. Ambil filename dari media.filename, media.fileName, filename, fileName, file.filename, file.fileName, dan _data.filename.

Gunakan Arr::get(). Terima string non-kosong. Untuk data/file/body root, wajibkan konteks media. Jangan lakukan array_walk_recursive() terhadap seluruh payload.

- [ ] **Step 3: Implementasikan inspectPayload() tanpa decode binary**

inspectPayload() melakukan json_decode($payloadJson, true), memilih candidate, memisahkan header data URI bila ada, lalu memanggil fromBinary() dengan contents string kosong hanya untuk metadata. Return tidak boleh memiliki key contents, encoded, base64, payload, atau raw.

- [ ] **Step 4: Implementasikan fromPayloadJson() dan fromDataUri() dengan strict decode**

Normalisasi Base64 hanya dengan preg_replace('/\s+/', '', $encoded). Gunakan base64_decode($encoded, true). Return null untuk input kosong, decode false, atau contents kosong. Data URI harus cocok dengan pola data:<mime>;base64,<data>; data URI non-Base64 tetap mengikuti perilaku existing dengan rawurldecode agar tidak meregresi media lama.

- [ ] **Step 5: Jalankan unit test parser**

Run:

~~~powershell
cd src
php -l app/Support/WahaMediaPayload.php
php artisan test --filter=WahaMediaPayloadTest
~~~

Expected: syntax valid; test ekstraksi dan Base64 strict PASS. Test MIME/signature Task 3 belum ditambahkan.

---

### Task 3: Tambahkan MIME Detection, Filename Aman, dan Preview Allowlist

**Tujuan:** Menyelesaikan metadata media untuk URL, data URI, dan embedded payload dengan urutan MIME yang disetujui serta header-safe filename.

**Dependency:** Task 2.

**Files:**
- Modify: src/app/Support/WahaMediaPayload.php
- Modify: src/tests/Unit/Support/WahaMediaPayloadTest.php
- Reference: src/vendor/symfony/mime/MimeTypes.php

**Interfaces:**
- Consumes: fromBinary() Task 2.
- Produces:
  - WahaMediaPayload::canPreviewInline(string $mimeType): bool
  - Return media final: source, contents, mime_type, file_name, category, inline.

**Test yang ditambahkan:** precedence MIME, PDF/file signature, sticker WebP, voice note, unknown binary, filename traversal/CRLF, Unicode, extension, dan SVG/HTML tidak inline.

- [ ] **Step 1: Tambahkan test MIME precedence dan signature**

~~~php
public function test_declared_mime_wins_and_is_normalized(): void
{
    $media = WahaMediaPayload::fromBinary(
        contents: "%PDF-1.4
%%EOF",
        declaredMime: 'application/pdf; charset=binary',
        payloadMime: 'application/octet-stream',
        declaredFileName: 'report',
        payloadFileName: 'wrong.bin',
        messageType: 'Dokumen',
        source: 'payload.base64',
    );

    self::assertSame('application/pdf', $media['mime_type']);
    self::assertSame('report.pdf', $media['file_name']);
    self::assertSame('pdf', $media['category']);
    self::assertTrue($media['inline']);
}

public function test_file_signature_falls_back_when_mime_and_extension_are_missing(): void
{
    $media = WahaMediaPayload::fromBinary("%PDF-1.4\n%%EOF");

    self::assertSame('application/pdf', $media['mime_type']);
    self::assertSame('pdf', $media['category']);
}
~~~

- [ ] **Step 2: Tambahkan test filename dan inline security**

~~~php
public function test_filename_is_safe_for_content_disposition(): void
{
    $media = WahaMediaPayload::fromBinary(
        contents: 'plain document',
        declaredMime: 'application/pdf',
        declaredFileName: "..\\..\\invoice\r\nX-Evil: yes.pdf",
    );

    self::assertSame('invoiceX-Evil: yes.pdf', $media['file_name']);
    self::assertStringNotContainsString('..', $media['file_name']);
    self::assertStringNotContainsString("\r", $media['file_name']);
    self::assertStringNotContainsString("\n", $media['file_name']);
}

public function test_active_and_unknown_content_is_not_inline(): void
{
    self::assertFalse(WahaMediaPayload::canPreviewInline('image/svg+xml'));
    self::assertFalse(WahaMediaPayload::canPreviewInline('text/html'));
    self::assertFalse(WahaMediaPayload::canPreviewInline('application/octet-stream'));
    self::assertTrue(WahaMediaPayload::canPreviewInline('image/webp'));
    self::assertTrue(WahaMediaPayload::canPreviewInline('audio/ogg'));
    self::assertTrue(WahaMediaPayload::canPreviewInline('video/mp4'));
    self::assertTrue(WahaMediaPayload::canPreviewInline('application/pdf'));
}
~~~

- [ ] **Step 3: Implementasikan MIME hierarchy**

Gunakan normalizeMime() private dengan regex type/subtype dan tolak karakter kontrol. Urutan fromBinary(): declaredMime, payloadMime, MimeTypes::getDefault()->getMimeTypes(extension)[0], finfo_buffer(FILEINFO_MIME_TYPE), application/octet-stream. Jangan tambah package; symfony/mime v7.4.8 sudah terpasang sebagai dependency Laravel.

- [ ] **Step 4: Implementasikan filename dan category**

Sanitasi filename dengan basename setelah mengganti backslash menjadi slash, hapus [\x00-\x1F\x7F"], ganti whitespace berulang menjadi satu spasi, trim titik/spasi, dan batasi 180 karakter dengan mb_substr(). Tambahkan extension pertama dari MimeTypes::getDefault()->getExtensions($mime) bila filename belum memiliki extension. Category: image, video, audio, pdf, atau file. Fallback filename: whatsapp-image, whatsapp-video, whatsapp-audio, whatsapp-document, atau whatsapp-media.

- [ ] **Step 5: Jalankan seluruh unit test helper**

Run:

~~~powershell
cd src
php artisan test --filter=WahaMediaPayloadTest
vendor/bin/pint --test app/Support/WahaMediaPayload.php tests/Unit/Support/WahaMediaPayloadTest.php
~~~

Expected: seluruh test helper PASS dan Pint tidak melaporkan style error pada dua file.

---

### Task 4: Tulis Feature Test Merah untuk Route Media

**Tujuan:** Mengunci kontrak controller sebelum refactor: auth, fallback PayloadJson, URL failure fallback, inline/download, header, status failure, dan tidak ada secret pada output/log.

**Dependency:** Task 3.

**Files:**
- Create: src/tests/Feature/Http/Controllers/WahaMediaControllerTest.php
- Reference: src/routes/web.php:31-33
- Reference: src/app/Http/Controllers/WahaMediaController.php:16-251

**Interfaces:**
- Consumes: WahaMediaPayload Task 3.
- Produces gate merah untuk Task 5.

**Schema test minimum:** Buat tabel SQLite TChatD dengan kolom Id, UrlMedia, PayloadJson, NamaFileMedia, TipeMime, dan JenisPesan. Gunakan Schema::create() pada setUp() dan Schema::dropIfExists() pada tearDown(); jangan menjalankan migration sqlsrv.

- [ ] **Step 1: Buat test harness SQLite dan user auth in-memory**

~~~php
protected function setUp(): void
{
    parent::setUp();

    Schema::create('TChatD', function (Blueprint $table): void {
        $table->string('Id')->primary();
        $table->string('UrlMedia')->nullable();
        $table->text('PayloadJson')->nullable();
        $table->string('NamaFileMedia')->nullable();
        $table->string('TipeMime')->nullable();
        $table->string('JenisPesan')->nullable();
    });

    config()->set('services.waha.media_base_url', 'https://waha.test');
    config()->set('services.waha.api_key', 'test-key');
}

private function actingAgent(): static
{
    return $this->actingAs(new Pengguna([
        'Id' => 'agent-1',
        'NamaPengguna' => 'Agent Test',
        'NonAktif' => false,
    ]));
}
~~~

- [ ] **Step 2: Tambahkan test embedded fallback dan download**

~~~php
public function test_serves_embedded_media_when_url_is_empty(): void
{
    DB::table('TChatD')->insert([
        'Id' => 'message-1',
        'PayloadJson' => json_encode([
            'hasMedia' => true,
            'type' => 'document',
            'media' => [
                'data' => base64_encode("%PDF-1.4
%%EOF"),
                'mimetype' => 'application/pdf',
                'filename' => 'invoice.pdf',
            ],
        ], JSON_THROW_ON_ERROR),
        'JenisPesan' => 'Dokumen',
    ]);

    $response = $this->actingAgent()->get(route('admin.waha-media.show', ['message' => 'message-1']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    self::assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
    self::assertSame("%PDF-1.4
%%EOF", $response->getContent());
}

public function test_download_parameter_forces_attachment(): void
{
    $response = $this->actingAgent()->get(route('admin.waha-media.show', [
        'message' => 'message-1',
        'download' => 1,
    ]));

    $response->assertOk();
    self::assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
}
~~~

- [ ] **Step 3: Tambahkan test URL gagal lalu fallback payload**

Gunakan Http::fake(['https://waha.test/*' => Http::response('', 404)]). Insert UrlMedia=/api/files/missing dan PayloadJson valid. Expected response tetap 200 dengan binary payload. Tambahkan Http::assertSent() yang memastikan header X-Api-Key dikirim tanpa menulis key ke log.

- [ ] **Step 4: Tambahkan failure/security tests**

Kasus: guest tidak menerima media; ID tidak ada 404; message ada tetapi seluruh sumber invalid menghasilkan 424 generik; response tidak memuat Base64/PayloadJson; MIME HTML/SVG menghasilkan attachment walau download tidak diberikan; filename CRLF tidak muncul pada Content-Disposition.

Gunakan Log::spy() pada kasus upstream gagal. Assert context log hanya memiliki message_id, source/status, dan reason_code; tidak ada url, body, payload, base64, api_key, atau exception_message.

- [ ] **Step 5: Jalankan test untuk membuktikan kondisi merah**

Run:

~~~powershell
cd src
php artisan test --filter=WahaMediaControllerTest
~~~

Expected: FAIL pada fallback PayloadJson, query download, nosniff, safe filename, dan safe logging karena controller lama belum mendukung kontrak ini.

---

### Task 5: Refactor WahaMediaController ke Pipeline Aman

**Tujuan:** Membuat route existing melayani media URL/data URI/storage/JSON/base64 payload melalui satu response builder dan fallback yang tidak berhenti pada kegagalan URL.

**Dependency:** Task 4.

**Files:**
- Modify: src/app/Http/Controllers/WahaMediaController.php:16-251
- Modify: src/resources/lang/id/ui.php:7-13
- Modify: src/resources/lang/en/ui.php:7-13
- Test: src/tests/Feature/Http/Controllers/WahaMediaControllerTest.php

**Interfaces:**
- Consumes: WahaMediaPayload::fromPayloadJson(), fromDataUri(), fromBinary().
- Produces untuk Inbox/Blade: GET admin.waha-media.show dengan inline default dan attachment bila download=1 atau MIME tidak aman untuk inline.

- [ ] **Step 1: Perluas query row tanpa mengubah route**

Select Id, UrlMedia, PayloadJson, JenisPesan, NamaFileMedia, dan TipeMime. Gunakan Schema::hasColumn() hanya untuk kolom legacy optional yang sudah dipakai controller. Ubah guard awal menjadi abort_if(! $row, 404); jangan lagi 404 hanya karena UrlMedia kosong.

- [ ] **Step 2: Implementasikan urutan sumber media**

Gunakan alur berikut di __invoke():

~~~php
$media = filled($row->UrlMedia)
    ? $this->mediaFromUrl((string) $row->UrlMedia, $row, $message)
    : null;

$media ??= WahaMediaPayload::fromPayloadJson(
    $row->PayloadJson,
    $row->TipeMime,
    $row->NamaFileMedia,
    $row->JenisPesan,
);

if (! $media) {
    return $this->errorResponse(__('ui.controllers.waha_media.unavailable'));
}

return $this->mediaResponse($media, request()->boolean('download'));
~~~

mediaFromUrl() menangani data URI melalui helper, /storage/ melalui Storage::disk('public'), dan HTTP melalui Http timeout 45 serta X-Api-Key existing. Respons HTTP JSON diproses sebagai payload JSON oleh helper; body binary diproses dengan fromBinary(). Kegagalan URL return null agar PayloadJson masih dicoba.

- [ ] **Step 3: Satukan header dalam mediaResponse()**

Gunakan Symfony\Component\HttpFoundation\ResponseHeaderBag::makeDisposition(). Pilih attachment bila download=true atau media.inline=false; selain itu inline. Header wajib: Content-Type, Content-Disposition, Cache-Control=private, max-age=300, X-Content-Type-Options=nosniff.

- [ ] **Step 4: Sanitasi failure response dan log**

Tambahkan key ui.controllers.waha_media.unavailable pada id/en. errorResponse() tetap text/plain UTF-8 dan Cache-Control no-store. Jangan gabungkan exception message ke response. Log hanya reason_code seperti upstream_exception, upstream_status, upstream_empty, invalid_json_media, atau embedded_invalid; sertakan message_id dan status HTTP bila ada.

- [ ] **Step 5: Hapus logika yang sudah dipusatkan**

Hapus jsonMediaResponse(), firstString(), dataUrlResponse(), dan fileName() dari controller setelah seluruh pemakainya pindah ke WahaMediaPayload. Pertahankan mediaUrl(), localPublicStoragePath(), normalizeWahaAbsoluteUrl(), dan looksLikeJson() bila masih dipakai.

- [ ] **Step 6: Jalankan test controller hingga hijau**

Run:

~~~powershell
cd src
php -l app/Http/Controllers/WahaMediaController.php
php artisan test --filter=WahaMediaControllerTest
vendor/bin/pint --test app/Http/Controllers/WahaMediaController.php tests/Feature/Http/Controllers/WahaMediaControllerTest.php
~~~

Expected: seluruh test route media PASS; URL gagal memakai embedded fallback; guest tidak menerima body; download attachment; response dan log tidak membocorkan data sensitif.

---

### Task 6: Tulis Livewire Test Merah untuk Media dan Identitas

**Tujuan:** Mengunci state Inbox sebelum mengubah class besar, termasuk default mode, invalid mode, media embedded, grup, participant, dan personal mapping.

**Dependency:** Task 5.

**Files:**
- Create: src/tests/Feature/Filament/Pages/InboxWhatsappTest.php
- Reference: src/app/Filament/Pages/InboxWhatsapp.php:363-695 dan 1199-1854

**Interfaces:**
- Consumes: route media Task 5 dan WahaMediaPayload::inspectPayload().
- Produces gate merah untuk Task 7-8.

**Schema test minimum:** Buat tabel SQLite MInstansi(Id,NamaInstansi), MCustomer(Id,NamaCustomer), MNomorWhatsapp(Id,IdCustomer,IdInstansi,NamaKontak,NomorWhatsapp,NonAktif), MGrupWhatsapp(Id,IdInstansi,NamaGrup,IdGrupWaha,NomorGrupWhatsapp,NonAktif), MStatusChat(Id,KodeStatusChat,NamaStatusChat), MPengguna(Id,NamaPengguna), dan TChatDCatatanInternal(Id,IdChat,IsiCatatan,DibuatOleh,TglBuat). TChat wajib memiliki Id, IdStatusChat, IdCustomer, IdInstansi, IdNomorWhatsapp, IdGrupWhatsapp, JenisChat, NomorWhatsapp, NamaKontak, NamaGrupWhatsapp, JumlahPesanBelumDibaca, TglChatTerakhir, AutoReplyAiAktif, AiSudahMenyapa, TglAutoReplyAiTerakhir, dan TglBuat. TChatD wajib memiliki Id, IdChat, ArahPesan, JenisPesan, IsiPesan, UrlMedia, PayloadJson, PengirimNomorWhatsapp, PengirimNamaKontak, TglPesan, StatusKirim, PesanError, DihasilkanOlehAi, DibalasOleh, dan TglBuat. Jangan memakai migration sqlsrv.

- [ ] **Step 1: Buat helper schema dan seed user/chat di test**

Gunakan string UUID sederhana karena SQLite test tidak menegakkan uniqueidentifier. Jangan sertakan kolom UrlFotoProfil dan DiambilOleh agar branch Schema::hasColumn() optional tetap mati dan auto-claim tidak mengubah fixture.

- [ ] **Step 2: Tambahkan test default dan validasi mode**

~~~php
public function test_identity_mode_defaults_to_whatsapp_and_rejects_invalid_value(): void
{
    $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);

    $component->assertSet('identityDisplayMode', 'whatsapp')
        ->set('identityDisplayMode', 'invalid')
        ->assertSet('identityDisplayMode', 'whatsapp');
}
~~~

- [ ] **Step 3: Tambahkan fixture grup yang sengaja memisahkan chat dan participant**

TChat: JenisChat=Grup, NomorWhatsapp=628111111111, NamaGrupWhatsapp=Fallback Group, IdGrupWhatsapp=group-map-1. MGrupWhatsapp: NamaGrup=Internal Support Group, IdGrupWaha=120363000000000000@g.us. Payload TChatD: chatId=120363999999999999@g.us, participant=628222222222@c.us, sender.pushname=Alice Raw, media.data=base64_encode("%PDF-1.4\n%%EOF"). TChatD.PengirimNamaKontak=Alice Raw dan PengirimNomorWhatsapp=628222222222.

- [ ] **Step 4: Tambahkan assertion proyeksi grup dan media state**

~~~php
$component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
$selected = $component->get('selectedChat');
$messages = $component->get('messages');

self::assertSame('120363999999999999@g.us', $selected['Identity']['whatsapp']['ChatId']);
self::assertSame('Internal Support Group', $selected['Identity']['internal']['GroupName']);
self::assertSame('Alice Raw', $messages[0]['SenderName']);
self::assertSame('628222222222', $messages[0]['SenderNumber']);
self::assertStringContainsString('/admin/waha-media/message-group-1', $messages[0]['MediaUrl']);
self::assertStringContainsString('download=1', $messages[0]['MediaDownloadUrl']);
self::assertStringNotContainsString(base64_encode("%PDF-1.4\n%%EOF"), json_encode($messages, JSON_THROW_ON_ERROR));
~~~

- [ ] **Step 5: Tambahkan test mode internal dan chat pribadi**

Mode internal harus mengubah proyeksi yang dipilih UI, bukan isi database atau chat ID. Fixture pribadi memakai TChat raw contact berbeda dari MNomorWhatsapp; assert whatsapp memakai raw contact/JID dan internal memakai mapped contact/instansi. Assert filterType, selectedChatId, dan urutan message tetap setelah toggle.

- [ ] **Step 6: Jalankan test untuk membuktikan kondisi merah**

Run:

~~~powershell
cd src
php artisan test --filter=InboxWhatsappTest
~~~

Expected: FAIL karena identityDisplayMode, Identity projections, PayloadJson select, MediaDownloadUrl, dan SenderNumber belum ada.

---

### Task 7: Implementasikan Livewire Media dan Dua Proyeksi Identitas

**Tujuan:** Menghasilkan state yang aman dan stabil untuk Blade tanpa mengubah database atau payload webhook.

**Dependency:** Task 6.

**Files:**
- Modify: src/app/Filament/Pages/InboxWhatsapp.php:87-1854
- Test: src/tests/Feature/Filament/Pages/InboxWhatsappTest.php
- Reuse: src/app/Support/WahaChatHelper.php
- Reuse: src/app/Support/WahaMediaPayload.php

**Interfaces:**
- Consumes: WahaMediaPayload::inspectPayload(), WahaChatHelper::normalizeChatId(), normalizePhoneNumber().
- Produces:
  - public string $identityDisplayMode = 'whatsapp'
  - public function updatedIdentityDisplayMode(): void
  - chat row key Identity.whatsapp dan Identity.internal
  - message keys MediaUrl, MediaDownloadUrl, MediaCategory, MediaLabel, SenderName, SenderNumber.

- [ ] **Step 1: Tambahkan state dan validasi mode**

~~~php
public string $identityDisplayMode = 'whatsapp';

public function updatedIdentityDisplayMode(): void
{
    if (! in_array($this->identityDisplayMode, ['whatsapp', 'internal'], true)) {
        $this->identityDisplayMode = 'whatsapp';
    }
}
~~~

Jangan simpan mode ke session, cache, localStorage, atau database.

- [ ] **Step 2: Pisahkan identifier chat dari identifier participant**

Ganti mappingIdentifiers() menjadi dispatcher berdasarkan JenisChat. Untuk grup, hanya terima field row yang berakhiran @g.us dan key payload chatId, from, from.id, id.remote, id._serialized, _data.id._serialized, _data.id.remote, _data.Info.Chat, _data.chatId, key.remoteJid, chat.id, chat.id._serialized, groupId, dan group.id yang nilainya @g.us. Jangan masukkan participant, author, sender.id, _data.author, atau hasil recursive scan non-grup.

Untuk pribadi, pertahankan nomor/JID contact dan perluasan @c.us/@s.whatsapp.net/@lid. Gunakan WahaChatHelper untuk normalisasi; jangan membuat normalizer baru.

- [ ] **Step 3: Bentuk return shape Identity yang tetap**

formatChatRow() harus menambahkan:

~~~php
'Identity' => [
    'whatsapp' => [
        'PrimaryName' => $whatsappPrimaryName,
        'Instansi' => null,
        'ContactName' => $rawContactName,
        'ContactNumber' => $rawContactNumber,
        'GroupName' => $rawGroupName,
        'GroupId' => $rawGroupId,
        'ChatId' => $rawChatId,
    ],
    'internal' => [
        'PrimaryName' => $internalPrimaryName,
        'Instansi' => $mappedInstansi,
        'ContactName' => $mappedContactName ?: $rawContactName,
        'ContactNumber' => $mappedContactNumber ?: $rawContactNumber,
        'GroupName' => $mappedGroupName ?: $rawGroupName,
        'GroupId' => $mappedGroupId ?: $rawGroupId,
        'ChatId' => $mappedChatId ?: $rawChatId,
    ],
],
~~~

Untuk grup, rawGroupId wajib payload @g.us lebih dulu, lalu TChat/IdWaha yang benar-benar @g.us. rawGroupName memakai payload group.subject, group.name, chat.name, atau _data.chat.name bila ada; TChat.NamaGrupWhatsapp hanya fallback karena source saat ini dapat berisi mapping internal. Untuk pribadi, raw contact memakai TChat.NamaKontak/NomorWhatsapp dan payload chat JID.

Pertahankan key lama NamaInstansi, NamaKontak, NamaGrupWhatsapp, NomorWhatsapp, dan IdWaha sebagai fallback compatibility sampai Blade dan caller existing tervalidasi. Jangan hapus pada change ini.

- [ ] **Step 4: Tambahkan PayloadJson ke query message dan metadata media**

Pada selectChat(), select d.PayloadJson. Panggil WahaMediaPayload::inspectPayload() hanya ketika UrlMedia kosong atau metadata perlu dilengkapi. Set MediaUrl bila UrlMedia terisi atau inspectPayload() tidak null. Set MediaDownloadUrl ke route yang sama dengan download=1. Gunakan MIME/category/filename hasil inspect sebagai fallback, tetapi jangan tambahkan PayloadJson atau Base64 ke array messages.

- [ ] **Step 5: Pisahkan sender grup pada message row**

Tambahkan SenderNumber. Untuk pesan masuk, prioritas PengirimNamaKontak dan PengirimNomorWhatsapp. Bila kosong, decode PayloadJson sebagai array dan baca sender.pushname/notifyName/pushName untuk nama serta participant/author/sender.id/_data.author untuk nomor; normalisasi nomor melalui WahaChatHelper::normalizePhoneNumber(). Untuk pesan keluar SenderNumber null dan SenderName tetap Medina/CS seperti existing.

- [ ] **Step 6: Jalankan test Livewire sampai hijau**

Run:

~~~powershell
cd src
php -l app/Filament/Pages/InboxWhatsapp.php
php artisan test --filter=InboxWhatsappTest
vendor/bin/pint --test app/Filament/Pages/InboxWhatsapp.php tests/Feature/Filament/Pages/InboxWhatsappTest.php
~~~

Expected: state default/invalid mode PASS; grup memakai @g.us payload; participant hanya sender; embedded media membentuk dua route tanpa Base64 di state; personal whatsapp/internal berbeda sesuai fixture.

---

### Task 8: Implementasikan Toggle, Identitas, Preview, dan Download di Blade

**Tujuan:** Menampilkan state Task 7 secara accessible dan localized pada daftar, header, detail, serta bubble media.

**Dependency:** Task 7.

**Files:**
- Modify: src/resources/views/filament/pages/inbox-whatsapp.blade.php:150-590
- Modify: src/resources/lang/id/ui.php:700-814
- Modify: src/resources/lang/en/ui.php:700-814
- Modify: src/tests/Feature/Filament/Pages/InboxWhatsappTest.php

**Interfaces:**
- Consumes: identityDisplayMode, Identity projections, MediaUrl, MediaDownloadUrl, MediaCategory, SenderNumber.
- Produces: toggle Livewire, badge type chat, identitas grup/personal, sender grup, preview, dan download.

- [ ] **Step 1: Tambahkan assertion render merah sebelum Blade diubah**

Pada InboxWhatsappTest, gunakan assertSee untuk label localized WhatsApp asli/Data internal, Grup WhatsApp/Chat Pribadi, Unduh media, group JID, Alice Raw, dan 628222222222. Gunakan assertSeeHtml untuk wire:click atau wire:model identityDisplayMode serta href download=1. Jalankan test dan pastikan gagal pada label/toggle/download.

- [ ] **Step 2: Tambahkan key localization id/en**

Di ui.pages.inbox tambahkan identity_mode, identity_whatsapp, identity_internal, whatsapp_group, personal_chat, group_name, group_id, sender_name, sender_number, preview_media, download_media, media_unavailable, dan unknown_media. Ubah media_received_unavailable agar tidak menyatakan URL kosong karena media kini dapat berasal dari payload.

- [ ] **Step 3: Tambahkan toggle accessible pada header daftar chat**

Gunakan dua button type=button dengan wire:click set whatsapp/internal, aria-pressed berdasarkan state, focus-visible ring, dan label localized. Jangan gunakan Alpine/localStorage. Tempatkan di header daftar chat agar mode berlaku jelas untuk seluruh daftar/header/detail.

- [ ] **Step 4: Gunakan proyeksi aktif pada daftar dan header**

Di loop chat, set $identity = $chat['Identity'][$identityDisplayMode] ?? $chat['Identity']['whatsapp']. Gunakan PrimaryName, GroupName/ContactName, dan GroupId/ContactNumber. Tambahkan badge Grup WhatsApp atau Chat Pribadi. Di selected header/detail, set $selectedIdentity dengan pola yang sama. Tampilkan group name dan group ID pada baris berbeda; pada pribadi tampilkan contact dan number/JID.

- [ ] **Step 5: Tampilkan sender anggota grup pada bubble masuk**

Pertahankan SenderName pada metadata bubble. Bila selectedChat JenisChat=Grup, message masuk, dan SenderNumber terisi, tampilkan SenderNumber sebagai teks monospaced kecil di bawah SenderName. Jangan tampilkan sender number sebagai group ID.

- [ ] **Step 6: Tambahkan renderer dan download untuk setiap kategori**

- image: anchor preview + img, termasuk sticker WebP.
- video: video controls preload=metadata.
- audio: audio controls preload=metadata.
- pdf: iframe/object dengan title localized dan anchor preview fallback.
- file: file card/link tanpa embed.
- semua kategori dengan MediaDownloadUrl: button/link download localized.
- semua external target baru: target=_blank dan rel=noopener.
- media unavailable: label localized; jangan tampilkan raw URL, Base64, atau JSON.

- [ ] **Step 7: Jalankan test render dan build**

Run:

~~~powershell
cd src
php artisan test --filter=InboxWhatsappTest
npm run build
~~~

Expected: test render PASS; Vite/Tailwind build selesai tanpa error; toggle dan seluruh label muncul dalam locale id default. Tambahkan satu assertion locale en dengan app()->setLocale('en') agar key Inggris tidak hilang.

---

### Task 9: Regression, Security, OpenSpec Tracking, dan Validasi Penuh

**Tujuan:** Membuktikan perubahan tidak mematahkan media existing, auth, filter Inbox, reply flow, atau kontrak route serta menyelaraskan hasil dengan OpenSpec.

**Dependency:** Task 8.

**Files:**
- Modify jika test menemukan gap: tiga test file change ini dan file implementasi terkait saja.
- Modify tracking: openspec/changes/inbox-whatsapp-improvement/tasks.md
- Do not modify: route, migration, schema SQL, vendor, lock file, generated asset.

**Interfaces:**
- Consumes: seluruh interface helper, controller, Livewire, dan Blade dari Task 2-8.
- Verifies: route admin.waha-media.show, identityDisplayMode, Identity projections, MediaDownloadUrl, dan kontrak tanpa perubahan schema/route.

**Test yang wajib ada sebelum selesai:**
- Unit: semua candidate key; plain body false positive; Base64 invalid; MIME precedence; extension; signature; filename; allowlist.
- Controller integration: data URI, storage local, remote binary, remote JSON Base64, UrlMedia gagal + PayloadJson valid, PayloadJson invalid, inline, download, auth, 404, 424, safe log.
- Livewire integration: UrlMedia dan embedded MediaUrl, no Base64 state, default/invalid toggle, group raw/internal, personal raw/internal, sender member terpisah.
- UI regression: filter pribadi/grup/keduanya, selectedChatId tidak berubah saat toggle, message order, unread count, reply form, internal note, close action tetap dirender.

- [ ] **Step 1: Jalankan targeted tests secara berurutan**

~~~powershell
cd src
php artisan test --filter=WahaMediaPayloadTest
php artisan test --filter=WahaMediaControllerTest
php artisan test --filter=InboxWhatsappTest
~~~

Expected: ketiga command PASS tanpa skipped test yang menyembunyikan requirement.

- [ ] **Step 2: Jalankan syntax dan formatter**

~~~powershell
php -l app/Support/WahaMediaPayload.php
php -l app/Http/Controllers/WahaMediaController.php
php -l app/Filament/Pages/InboxWhatsapp.php
vendor/bin/pint --test
~~~

Expected: seluruh PHP syntax valid dan Pint exit code 0. Jangan memperbaiki file unrelated jika full Pint menemukan issue lama; jalankan Pint targeted untuk membuktikan file change bersih dan dokumentasikan issue unrelated.

- [ ] **Step 3: Jalankan full test suite**

Run: php artisan test

Expected: seluruh suite PASS. Jika failure unrelated sudah ada, simpan command/output, buktikan targeted test change tetap PASS, dan jangan memperluas scope.

- [ ] **Step 4: Jalankan frontend build**

Run: npm run build

Expected: build produksi exit code 0 tanpa error Blade/Vite/Tailwind.

- [ ] **Step 5: Verifikasi security secara manual**

Gunakan satu message Base64 invalid, satu filename ../../evil CRLF, satu MIME text/html, dan satu SVG. Expected: tidak ada partial binary; filename aman; HTML/SVG attachment; response/log tidak memuat payload/secret. Verifikasi guest route tidak mengembalikan media.

- [ ] **Step 6: Verifikasi identitas secara manual**

Buka satu chat pribadi, satu grup mapped, dan satu grup unmapped. Dalam mode whatsapp, pastikan raw chat/group JID diprioritaskan. Dalam mode internal, pastikan mapping diprioritaskan. Pada grup, pastikan nama/nomor anggota berada pada bubble dan tidak menggantikan nama/JID grup.

- [ ] **Step 7: Sinkronkan checkbox OpenSpec setelah bukti tersedia**

Centang hanya item openspec/changes/inbox-whatsapp-improvement/tasks.md yang command/verification-nya benar-benar selesai. Jangan centang manual verification yang belum dilakukan.

- [ ] **Step 8: Validasi OpenSpec**

~~~powershell
cd ..
openspec validate inbox-whatsapp-improvement --strict
openspec status --change inbox-whatsapp-improvement --json
~~~

Expected: change valid tanpa error/warning; artifact tetap done; completedTasks sesuai checkbox yang benar-benar diverifikasi.

- [ ] **Step 9: Catat deployment dan rollback**

Deployment: publish file dan asset build, lalu dari src jalankan php artisan optimize:clear. Tidak ada migration, backup data, queue restart, Reverb restart, scheduler restart, atau env change untuk scope ini. Rollback: kembalikan helper/controller/Inbox/Blade/translation/test dan asset build sebelumnya; tidak ada rollback schema/data.

---

## Requirement-to-Task Coverage

- Embedded Base64 image/video/audio/voice/PDF/document/sticker/file: Tasks 1-5 dan 8.
- MIME, extension, signature, filename aman: Task 3.
- Preview browser dan download semua media: Tasks 4-5 dan 8.
- UrlMedia kosong/gagal dan Base64 invalid: Tasks 4-5.
- Mode whatsapp/internal dan invalid state: Tasks 6-8.
- Grup name/JID versus sender name/number: Tasks 6-8.
- Auth, no secret logging, inline allowlist, CRLF/path traversal: Tasks 3-5 dan 9.
- Regression, build, formatter, OpenSpec: Task 9.

## Deliberate Simplifications

- Tidak membuat media DTO, repository, interface, factory, config, migration, atau cache; satu helper stateless cukup.
- Tidak mengubah WahaWebhookProcessor untuk memperbaiki data historis; Inbox membaca PayloadJson existing dan memisahkan group/participant pada presentation path.
- Tidak mengubah ViewChatSession; change ini khusus Inbox WhatsApp. Endpoint media tetap kompatibel untuk caller existing.
- Tidak menyimpan mode tampilan; reload kembali ke whatsapp sesuai spec.
- Tidak menambah streaming/range request; decode in-memory dipertahankan sesuai scope, evaluasi streaming hanya bila ukuran media produksi terbukti bermasalah.

## Execution Handoff

Plan ini tidak mengizinkan commit otomatis. Implementasi harus mengikuti salah satu workflow berikut setelah pengguna memilih:

1. Subagent-Driven (recommended) — gunakan superpowers:subagent-driven-development, satu worker per task, review antartask.
2. Inline Execution — gunakan superpowers:executing-plans, eksekusi berurutan dengan checkpoint setelah setiap gate merah/hijau.
