# Improve Group Metadata Caching & Safe Media UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show cached group names without render-time WAHA calls and prevent base64/media blobs from appearing in chat UI.

**Architecture:** Inbox reads group display names from `TChat.GroupName` when available, with `NamaGrupWhatsapp` fallback and localized Unknown Group as the only visible empty-cache fallback. Metadata refresh remains asynchronous through `RefreshWahaGroupMetadataJob` on `waha-metadata`. Media payload remains available to the media route, while Livewire/Blade state receives only preview/download metadata and safe caption text.

**Tech Stack:** PHP 8.3, Laravel 13, Filament 5, Livewire, SQL Server-compatible schema checks, WAHA queue jobs, PHPUnit, Blade, Vite.

---

## Task 1: Regression Tests

**Files:**
- Modify: `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`
- Modify: `src/tests/Unit/Jobs/RefreshWahaGroupMetadataJobTest.php`

- [ ] Add tests for cached `GroupName`, Unknown Group fallback, no render-time WAHA request, sync selected group, sync all missing groups, and hidden base64 media body.
- [ ] Add `GroupName` to test `TChat` schema so optional-column behavior is covered.
- [ ] Run focused tests and verify expected failures before implementation.

## Task 2: Cached Group Name Display

**Files:**
- Modify: `src/app/Filament/Pages/InboxWhatsapp.php`

- [ ] Select optional `c.GroupName` as `GroupName` on inbox and selected-chat queries.
- [ ] Add a helper that returns `GroupName` then `NamaGrupWhatsapp`, otherwise localized Unknown Group.
- [ ] Ensure `Identity.whatsapp.GroupName`, `Identity.internal.GroupName`, `NamaGrupWhatsapp`, and visible titles never use `@g.us` as group-name fallback.

## Task 3: Async Metadata Actions

**Files:**
- Modify: `src/app/Filament/Pages/InboxWhatsapp.php`
- Modify: `src/app/Jobs/RefreshWahaGroupMetadataJob.php`
- Modify: `src/resources/views/filament/pages/inbox-whatsapp.blade.php`
- Modify: `src/resources/lang/id/ui.php`
- Modify: `src/resources/lang/en/ui.php`

- [ ] Import and dispatch `RefreshWahaGroupMetadataJob` from Inbox methods.
- [ ] Add selected-group and all-empty-group sync methods with manage permission guard and safe summary notification.
- [ ] Update job writes to fill `GroupName` when available and `NamaGrupWhatsapp` when available.
- [ ] Add buttons and translations.

## Task 4: Safe Media Text

**Files:**
- Modify: `src/app/Filament/Pages/InboxWhatsapp.php`

- [ ] Add media text sanitizer for data URI, long strict base64, and JSON/blob-looking payload text on media messages.
- [ ] Use sanitized text in `formatMessage()` while keeping media route metadata intact.

## Task 5: Verification

**Commands:**
- `cd src; php -l app/Filament/Pages/InboxWhatsapp.php app/Jobs/RefreshWahaGroupMetadataJob.php tests/Feature/Filament/Pages/InboxWhatsappTest.php tests/Unit/Jobs/RefreshWahaGroupMetadataJobTest.php`
- `cd src; php artisan test --filter='(InboxWhatsappTest|RefreshWahaGroupMetadataJobTest)'`
- `cd src; php artisan test`
- `cd src; npm run build`
- `cd src; php artisan view:cache`
- `openspec validate improve-group-metadata-cache-and-safe-media-ui --type change --strict --no-interactive`
- `git diff --check`
