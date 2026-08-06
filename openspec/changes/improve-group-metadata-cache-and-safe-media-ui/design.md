# Improve Group Metadata Caching & Safe Media UI Design

## Overview

Perubahan dibuat pada shared Inbox path yang sudah memuat group aggregation, pagination, dan media mapping. Tidak ada route, schema migration, event realtime, atau endpoint baru. Kolom `GroupName` diperlakukan sebagai optional production column; `NamaGrupWhatsapp` tetap didukung sebagai legacy cache.

## Dependency Graph

```text
WAHA webhook
  -> ProcessWebhookJob [webhooks]
  -> WahaWebhookProcessor persists TChat/TChatD
  -> RefreshWahaGroupMetadataJob [waha-metadata]
  -> TChat.GroupName / TChat.NamaGrupWhatsapp
  -> SendBroadcastDebouncedJob [broadcasts]
  -> InboxWhatsapp::loadInbox/selectChat reads database only
  -> inbox-whatsapp.blade.php renders cached name and media-safe message state
```

## Call Graph

```text
InboxWhatsapp::loadInbox()
  -> formatChatRow()
    -> cachedGroupName()
    -> visibleGroupName()
    -> decodePayload() only for local payload metadata

InboxWhatsapp::selectChat()
  -> siblingChatIdsForRoom()
  -> formatMessage()
    -> WahaMediaPayload::fromPayloadJson() for media metadata only
    -> safeMessageText() hides base64/blob body for media

InboxWhatsapp::syncSelectedGroupName()
  -> selectedGroupMetadataTarget()
  -> RefreshWahaGroupMetadataJob::dispatch()

InboxWhatsapp::syncMissingGroupNames()
  -> query groups with empty cached name
  -> RefreshWahaGroupMetadataJob::dispatch() per target
```

## Key Decisions

- Visible group title never falls back to `@g.us`; empty cache renders localized Unknown Group.
- `GroupName` wins over `NamaGrupWhatsapp` when both exist.
- Async sync actions only dispatch queue jobs; no direct `WahaSender`/`Http` call from render/request path.
- Existing `RefreshWahaGroupMetadataJob` remains the only WAHA metadata caller and writes whichever cache columns exist.
- Media payload stays available for `WahaMediaController`, but Livewire state hides data URI/base64/blob-looking message body for media rows.

## Affected Files

- `src/app/Filament/Pages/InboxWhatsapp.php`: group name helper, sync methods, render-safe media text, remove sync WAHA lookup.
- `src/app/Jobs/RefreshWahaGroupMetadataJob.php`: update optional `GroupName` and legacy `NamaGrupWhatsapp`.
- `src/resources/views/filament/pages/inbox-whatsapp.blade.php`: sync buttons and existing safe media render.
- `src/resources/lang/id/ui.php`, `src/resources/lang/en/ui.php`: labels/notifications.
- `src/tests/Feature/Filament/Pages/InboxWhatsappTest.php`: display/actions/media regressions.
- `src/tests/Unit/Jobs/RefreshWahaGroupMetadataJobTest.php`: GroupName cache update regressions.

## Performance Notes

- Inbox rendering remains database-only.
- Sync All selects only groups with empty cache and dispatches jobs; it does not fetch metadata inline.
- Group name display adds no per-row external call and uses existing `TChat` row data.
