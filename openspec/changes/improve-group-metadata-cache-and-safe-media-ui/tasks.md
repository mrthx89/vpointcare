## 1. Planning and Baseline

- [x] 1.1 Read `README.md`, `openspec/project.md`, `openspec/specs/vpoint-care/spec.md`, active changes, and related Inbox/WAHA/media files.
- [x] 1.2 Analyze group metadata flow, render-time WAHA calls, PayloadJson media handling, and Blade rendering.
- [x] 1.3 Run OpenSpec validation for this change before implementation.

## 2. Group Name Display Tests

- [x] 2.1 Add regression proving `TChat.GroupName` is the visible group title in WhatsApp identity mode.
- [x] 2.2 Add regression proving empty group cache renders localized `Unknown Group`, not `@g.us`.
- [x] 2.3 Add regression proving Inbox render does not call WAHA metadata.

## 3. Metadata Sync Action Tests

- [x] 3.1 Add regression for `syncSelectedGroupName()` dispatching one `RefreshWahaGroupMetadataJob` to `waha-metadata` for the selected group.
- [x] 3.2 Add regression for `syncMissingGroupNames()` dispatching only groups with empty cached name and reporting queued/skipped/failed counts.
- [x] 3.3 Add job regression proving metadata writes `GroupName` when column exists and preserves session scoping.

## 4. Media Rendering Tests

- [x] 4.1 Add regression proving image preview/download still render through `/admin/waha-media/{message}`.
- [x] 4.2 Add regression proving base64/data URI/media blob is absent from Livewire state and rendered HTML after preview.
- [x] 4.3 Keep existing pagination and realtime tests green.

## 5. Minimal Implementation

- [x] 5.1 Update `InboxWhatsapp` select clauses to include optional `GroupName` without requiring migration.
- [x] 5.2 Update group display helpers so visible title uses cached group name or Unknown Group, never group JID.
- [x] 5.3 Remove synchronous `wahaGroupName()` path and add async sync methods using `RefreshWahaGroupMetadataJob`.
- [x] 5.4 Update `RefreshWahaGroupMetadataJob` to write `GroupName` and `NamaGrupWhatsapp` columns that exist.
- [x] 5.5 Sanitize media `IsiPesan` before Livewire/Blade state when it contains base64/data URI/media blob.
- [x] 5.6 Add localized action labels and notifications in `id` and `en`.

## 6. Verification

- [x] 6.1 Run PHP syntax checks for changed PHP files.
- [x] 6.2 Run focused tests: `cd src; php artisan test --filter='(InboxWhatsappTest|RefreshWahaGroupMetadataJobTest)'`.
- [x] 6.3 Run full tests: `cd src; php artisan test`.
- [x] 6.4 Run frontend/view checks: `cd src; npm run build` and `php artisan view:cache`.
- [x] 6.5 Run OpenSpec strict validation and `git diff --check`.
- [x] 6.6 Report files changed, test summary, performance impact, and manual steps; do not sync/archive without approval.

