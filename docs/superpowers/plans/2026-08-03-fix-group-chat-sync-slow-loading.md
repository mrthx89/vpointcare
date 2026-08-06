# Fix Group Chat Sync & Slow Loading Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menampilkan pesan grup terbaru secara realtime, membuka percakapan besar dengan cepat, dan menjaga chat pribadi tetap kompatibel.

**Architecture:** Persistence webhook tetap transactional dan broadcast tetap memakai kontrak `WahaInboxUpdated`. Inbox dipisahkan menjadi set-based room loading, canonical identity direct, async metadata job, dan cursor pagination latest-first; legacy group tidak dihapus, hanya dibackfill agar tidak perlu scan payload pada request.

**Tech Stack:** PHP 8.3, Laravel 13, Filament 5, Livewire, Laravel Echo/Reverb, SQL Server, WAHA, queue, PHPUnit, Vite/Tailwind.

---

## Root Cause Analysis

1. `InboxWhatsapp::selectChat()` memakai `ORDER BY d.TglPesan ASC` lalu `LIMIT 200`; group dengan lebih dari 200 pesan merender 200 pesan tertua, sehingga pesan terbaru tampak tidak sinkron.
2. `handleInboxUpdate()` memanggil `loadInbox()`. Daftar maksimal 50 room lalu menjalankan query preview `TChatD` per room, membaca payload terbaru per room, dan dapat melakukan HTTP WAHA per group.
3. `wahaGroupName()` adalah blocking network call dengan timeout lima detik pada cache-miss, sehingga realtime event sudah diterima tetapi Livewire belum selesai memuat state.
4. `groupSiblingIds()`/`findOrCreateChat()` melakukan `LIKE '%group_jid%'` terhadap `TChatD.PayloadJson`, yang tidak sargable pada SQL Server dan memburuk seiring histori.
5. `loadInbox()` dapat memilih selected room, lalu `handleInboxUpdate()` memilih active group lagi; refresh menjadi berulang.
6. Webhook memakai queue `webhooks`, broadcast memakai `broadcasts`, dan worker production harus benar-benar mengonsumsi kedua queue tersebut.

## Architecture Overview

```text
WAHA -> POST /webhooks/waha/{token?} -> ProcessWebhookJob [webhooks]
     -> WahaWebhookProcessor transaction -> TChat/TChatD
     -> SendBroadcastDebouncedJob [broadcasts]
     -> WahaInboxUpdated / waha-inbox / .inbox.updated
     -> Echo debounce -> Livewire InboxWhatsapp
          -> set-based room rows, no WAHA HTTP
          -> latest cursor page, max 100 messages

Group metadata only:
ProcessWebhookJob -> RefreshWahaGroupMetadataJob [waha-metadata]
                  -> WahaSender -> persisted TChat name -> broadcast
```

## File Map

- Modify `src/app/Support/WahaChatHelper.php`, `src/app/Services/Waha/WahaWebhookProcessor.php`, and `src/app/Filament/Pages/InboxWhatsapp.php`.
- Create `src/app/Console/Commands/BackfillGroupChatIdentity.php` and `src/app/Jobs/RefreshWahaGroupMetadataJob.php`.
- Modify `src/app/Services/Waha/WahaSender.php` and `src/app/Jobs/ProcessWebhookJob.php`.
- Modify `src/resources/views/filament/pages/inbox-whatsapp.blade.php`, `src/resources/lang/id/ui.php`, and `src/resources/lang/en/ui.php`.
- Test `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php` and `src/tests/Unit/Services/Waha/WahaWebhookProcessorTest.php`; create command/job tests.
- Modify `README.md` for queue/backfill operations. Do not modify `src/script/DATABASE_SCHEMA_WACS.sql` or add migration.

## Task 1: Lock the Regression with Tests

**Files:** `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`, `src/tests/Unit/Services/Waha/WahaWebhookProcessorTest.php`

- [ ] **Step 1: Add the large-group test.** Insert 250 details with increasing timestamps, call `selectChat('chat-group-1')`, then assert latest ID, 100-row maximum, and `hasOlderMessages`.

```php
public function test_large_group_starts_with_latest_messages_and_exposes_older_cursor(): void
{
    $now = now();
    for ($index = 1; $index <= 250; $index++) {
        $this->insertChatDetail([
            'Id' => 'message-group-'.$index,
            'IdChat' => 'chat-group-1',
            'IsiPesan' => 'Group message '.$index,
            'TglPesan' => $now->copy()->addSeconds($index),
            'PayloadJson' => json_encode(['chatId' => '120363999999999999@g.us'], JSON_THROW_ON_ERROR),
        ]);
    }

    $component = Livewire::actingAs($this->agent())->test(InboxWhatsapp::class);
    $component->call('selectChat', 'chat-group-1');
    $messages = collect($component->getData()['messages'] ?? []);

    self::assertCount(100, $messages);
    self::assertTrue($messages->contains('Id', 'message-group-250'));
    self::assertFalse($messages->contains('Id', 'message-group-1'));
    self::assertTrue($component->getData()['hasOlderMessages']);
}
```

- [ ] **Step 2: Add cursor merge test.** Call `loadOlderMessages()` twice; assert no duplicate `TChatD.Id`, chronological ordering, and false older flag at the end.
- [ ] **Step 3: Add `Http::fake()` no-network test.** Mount/select an uncached group and assert no outbound request occurs in list/room render.
- [ ] **Step 4: Add private realtime test.** Insert a private message and call `handleInboxUpdate()` with its exact chat ID; assert group sibling aggregation is not used.
- [ ] **Step 5: Run focused tests before code changes.** From `src`, run `php artisan test --filter='(InboxWhatsappTest|WahaWebhookProcessorTest)'`; record expected failures.

## Task 2: Canonicalize Legacy Group Identity

**Files:** `src/app/Support/WahaChatHelper.php`, `src/app/Services/Waha/WahaWebhookProcessor.php`, `src/app/Console/Commands/BackfillGroupChatIdentity.php`, `src/tests/Feature/Console/BackfillGroupChatIdentityTest.php`

- [ ] **Step 1: Add `WahaChatHelper::groupJidFromPayload(array $payload): ?string`.** Check `chatId`, `from`, `from.id`, `id.remote`, `_data.id.remote`, `_data.Info.Chat`, `key.remoteJid`, `chat.id`, `groupId`, and `group.id`; return only a string ending in `@g.us`.
- [ ] **Step 2: Refactor `parseMessage()` to use the helper.** Preserve `JenisChat='Grup'`, participant `pengirim_jid`, and `IdPesanWaha` idempotency.
- [ ] **Step 3: Implement command signature `waha:backfill-group-chat-identity {--dry-run}`.** Chunk 500, decode legacy payloads, fill only `TChat.IdWahaTerdeteksi`, and report candidate/updated/skipped/unparseable without printing payloads or secrets.
- [ ] **Step 4: Add command tests.** Dry-run writes zero rows; real run is idempotent; unparsable rows remain intact; no row/message is deleted or merged.
- [ ] **Step 5: Remove payload scan from normal request paths.** `findOrCreateChat()` and `groupSiblingIds()` use session plus direct identity columns after backfill.

## Task 3: Remove N+1 Inbox Work

**Files:** `src/app/Filament/Pages/InboxWhatsapp.php`, `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`

- [ ] **Step 1: Add a latest-detail derived query.** Use a SQL Server-compatible subquery with `ROW_NUMBER() OVER (PARTITION BY IdChat ORDER BY TglPesan DESC, Id DESC)`, filter `row_number = 1`, and join it to the 50-room inbox query.
- [ ] **Step 2: Pass latest fields to formatter.** Select payload, preview, media fields, and timestamps in the derived query; remove per-room `TChatD` lookup and `latestIncomingPayload()` from `formatChatRow()`.
- [ ] **Step 3: Keep display fallback local.** Resolve group display as persisted name, master mapping, already-loaded payload name, then canonical JID. No `Http` call may occur in a render method.
- [ ] **Step 4: Add bounded-query assertion.** Use `DB::listen()` in a focused test/diagnostic helper to show 10, 25, and 50 room rows do not increase detail/payload query count linearly.
- [ ] **Step 5: Run tests.** Expected room preview, identity, unread aggregation, filter, and private state match current behavior with no outbound WAHA request.

## Task 4: Add Asynchronous Group Metadata

**Files:** `src/app/Services/Waha/WahaSender.php`, `src/app/Jobs/ProcessWebhookJob.php`, `src/app/Jobs/RefreshWahaGroupMetadataJob.php`, `src/tests/Unit/Jobs/RefreshWahaGroupMetadataJobTest.php`

- [ ] **Step 1: Add `WahaSender::getGroupMetadata()`.** Reuse existing request logging/circuit behavior and return `{ok, subject, status, error}` without logging API keys or raw secrets.
- [ ] **Step 2: Create the job.** Set `onQueue('waha-metadata')`, `tries=3`, `timeout=20`, backoff `[30, 120]`, and a session+JID cache lock.
- [ ] **Step 3: Persist safely.** Update group rows for matching session/canonical JID only; do not alter `TChatD` or master mappings. Dispatch `SendBroadcastDebouncedJob` only after a successful name change.
- [ ] **Step 4: Dispatch after transaction.** Extend the non-secret processor result with group/session info and enqueue metadata only for a valid persisted group message. Metadata failure cannot fail message persistence/broadcast.
- [ ] **Step 5: Test all outcomes.** Fake success, timeout/non-2xx, retry, lock, sibling update, and follow-up broadcast; assert message flow still succeeds when metadata fails.

## Task 5: Implement Latest-First Cursor Pagination

**Files:** `src/app/Filament/Pages/InboxWhatsapp.php`, `src/resources/views/filament/pages/inbox-whatsapp.blade.php`, `src/resources/lang/id/ui.php`, `src/resources/lang/en/ui.php`, `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`

- [ ] **Step 1: Add state.** Define `MESSAGE_PAGE_SIZE = 100`, `public bool $hasOlderMessages`, `public ?string $olderMessagesBeforeAt`, and `public ?string $olderMessagesBeforeId`; reset them when room/filter changes.
- [ ] **Step 2: Extract `loadMessagesPage()`.** Query canonical `whereIn('IdChat', $roomChatIds)`, order descending by `TglPesan` and `Id`, fetch 101 rows, set older state, then reverse 100 rows before render.
- [ ] **Step 3: Implement older page action.** Apply pair cursor predicate, prepend results, and merge by `TChatD.Id` so realtime events cannot duplicate a message.

```php
$page = $query
    ->orderByDesc('d.TglPesan')
    ->orderByDesc('d.Id')
    ->limit(self::MESSAGE_PAGE_SIZE + 1)
    ->get();

$this->hasOlderMessages = $page->count() > self::MESSAGE_PAGE_SIZE;
$messages = $page->take(self::MESSAGE_PAGE_SIZE)->reverse()->values()->all();
```

- [ ] **Step 4: Add localized control.** Render `wire:click=loadOlderMessages` only while `hasOlderMessages`; add matching Indonesian/English label/loading keys.
- [ ] **Step 5: Test pagination race.** Add a new group message between old-page calls and assert the latest message remains visible, chronological sequence is preserved, and IDs are unique.

## Task 6: Make Realtime Refresh Single-Pass

**Files:** `src/app/Filament/Pages/InboxWhatsapp.php`, optionally `src/resources/js/echo.js`, `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`

- [ ] **Step 1: Separate list and room refresh.** Make `handleInboxUpdate()` refresh the list once and refresh the matching active room once, never through two implicit `selectChat()` calls.
- [ ] **Step 2: Match correct identity.** Private rooms compare exact `chatId`; group rooms compare `IdSesiWhatsapp` plus canonical JID, never participant JID.
- [ ] **Step 3: Preserve browser contract.** Keep Echo `waha-inbox`, `.inbox.updated`, Livewire `waha-inbox-updated`, `chat_id`, and debounce 300ms.
- [ ] **Step 4: Test burst/siblings.** Deliver two sibling group events and assert one room, both new messages, latest window, no duplicate IDs, and private regression coverage.

## Task 7: Validate Operations and Documentation

**Files:** `README.md`; deployment worker configuration only if queue names are not consumed

- [ ] **Step 1: Document workers.** Production must consume `webhooks`, `broadcasts`, and `waha-metadata` through dedicated workers or an explicit queue list, plus Reverb.
- [ ] **Step 2: Document backfill.** Add SQL Server backup, `--dry-run`, write, repeat, and rollback steps without exposing secrets.
- [ ] **Step 3: Run syntax checks.** From `src`, run `php -l` for every changed PHP file; expected no syntax errors.
- [ ] **Step 4: Run focused tests.** Run `php artisan test --filter='(InboxWhatsappTest|WahaWebhookProcessorTest|BackfillGroupChatIdentityTest|RefreshWahaGroupMetadataJobTest)'`; expected all PASS.
- [ ] **Step 5: Run broad validation.** Run `php artisan test`, `vendor/bin/pint --test`, and `npm run build`; record unrelated legacy Pint failures without changing them.
- [ ] **Step 6: Run SQL Server benchmark.** With 100 rooms, 100,000 detail rows, and one group over 250 messages, confirm no render-time WAHA request, no request-time payload `LIKE`, max 100 latest rows, p95 open room <=2 seconds, and realtime visibility <=3 seconds with workers active.
- [ ] **Step 7: Run staging smoke test.** Backup, dry-run/write/repeat backfill, restart `webhooks`, `broadcasts`, `waha-metadata`, and Reverb, then verify private/group incoming, duplicate webhook, older pagination, and metadata failure fallback.

## Risk Assessment

- **Legacy incomplete identity:** sibling aggregation could miss an unparseable payload. Mitigate with dry-run counts, explicit unparseable output, and backup before write.
- **Metadata service outage:** group name may remain JID, but message visibility must not block. Isolate the job and use persisted/master fallback.
- **Cursor bug:** deterministic `(TglPesan, Id)` ordering and unique-by-ID merge prevent gaps/duplicates; SQL Server staging test is mandatory.
- **Private regression:** keep group-only branches for canonicalization/metadata and test private refresh, identity, media, unread, and route behavior.
- **Queue misconfiguration:** deployment checklist must verify all three named queues and Reverb before acceptance.

## Rollback Plan

- Stop or scale down `waha-metadata` if metadata load is unsafe; webhook persistence and broadcasts remain independent.
- Revert the application release and rebuild assets; unchanged `TChat`/`TChatD` rows and existing columns remain readable.
- Reverse backfill only through the reviewed row list or SQL Server backup restore; never issue a broad destructive update.
- No schema migration is planned, so no `migrate:rollback` is required.

## Performance Validation Checklist

- [ ] Inbox list makes no outbound WAHA request.
- [ ] Latest-detail lookup is set-based, not one `TChatD` query per room.
- [ ] Group sibling resolution uses direct canonical identity, not `PayloadJson LIKE`.
- [ ] Active room returns at most 100 latest messages.
- [ ] Older pages prepend without duplicate IDs and stop at the end.
- [ ] Metadata failure does not hide messages or block the room.
- [ ] Burst group events result in one refresh path and newest state.
- [ ] Private chat behavior and existing tests remain unchanged.
