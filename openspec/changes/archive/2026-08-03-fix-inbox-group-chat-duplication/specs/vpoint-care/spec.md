## MODIFIED Requirements

### Requirement: Group Chat Unified Inbox

System SHALL unify incoming group WhatsApp messages from all senders into a single TChat row, regardless of whether MGrupWhatsapp mapping exists.

#### Scenario: Unmapped group receives messages from multiple members

- **GIVEN** a WhatsApp group is not mapped to any MGrupWhatsapp row
- **WHEN** member A sends the first message
- **THEN** the system SHALL create a new TChat row with JenisChat='Grup'
- **AND** IdWahaTerdeteksi SHALL be set to the group JID (ending with @g.us)
- **AND** NamaGrupWhatsapp SHALL be populated from payload if available
- **AND** the message SHALL be stored in TChatD with the correct PengirimNamaKontak and PengirimNomorWhatsapp for member A

- **WHEN** member B sends a message to the same group
- **THEN** the system SHALL find the existing TChat row via group_jid
- **AND** SHALL NOT create a separate TChat row for member B
- **AND** the message SHALL be stored in TChatD with the correct PengirimNamaKontak and PengirimNomorWhatsapp for member B
- **AND** the inbox SHALL display member B as a distinct sender in the message list

#### Scenario: Same group JID exists in multiple WhatsApp sessions

- **GIVEN** two WhatsApp sessions have group TChat rows with the same canonical `@g.us` JID
- **WHEN** a webhook arrives for one session
- **THEN** the processor SHALL route the message only to the TChat row with the matching `IdSesiWhatsapp`
- **AND** the other session SHALL not receive a new TChatD message

### Requirement: Group Room Realtime Consistency

System SHALL keep the visible WhatsApp group room synchronized with newly persisted messages for mapped, unmapped, and legacy group chats.

#### Scenario: Group payload contains a participant ID before the group ID

- **GIVEN** a WAHA payload contains a participant or sender JID ending with `@c.us` in an earlier candidate field
- **AND** another payload field contains the group JID ending with `@g.us`
- **WHEN** the webhook processor parses the message
- **THEN** the message SHALL be classified as `JenisChat='Grup'`
- **AND** `group_jid` SHALL equal the group JID
- **AND** the message SHALL not create or update a private room for the participant

#### Scenario: Mapped group receives a message while its room is open

- **GIVEN** the active Inbox room represents a mapped WhatsApp group
- **WHEN** a new group message is persisted in any TChat sibling row
- **AND** the `inbox.updated` event is received with that sibling `chatId`
- **THEN** the active room SHALL reload messages from all TChat rows belonging to the same canonical group JID
- **AND** the new message SHALL be visible without a full page reload

#### Scenario: Unmapped or legacy group receives a message while its room is open

- **GIVEN** the active Inbox room represents an unmapped or legacy WhatsApp group
- **WHEN** a new message is persisted in a sibling TChat row identified by `IdWahaTerdeteksi`, `NomorWhatsapp`, or `PayloadJson`
- **THEN** the Inbox SHALL resolve the same canonical group room
- **AND** all messages from the matching sibling rows SHALL remain visible
- **AND** private chat rooms SHALL continue loading messages only from their own TChat row

### Requirement: Group Chat Latest State and Bounded Pagination

System SHALL render the latest persisted messages for a group conversation without blocking on an external WAHA request, and SHALL load older messages through deterministic cursor pagination without duplicates.

#### Scenario: Large group opens at the latest message window

- **GIVEN** a group room has more than 200 persisted `TChatD` messages across its canonical sibling rows
- **WHEN** an agent opens the room or receives a realtime refresh
- **THEN** the visible message window SHALL contain the newest messages, including the message with the greatest `(TglPesan, Id)` ordering
- **AND** the initial request SHALL load at most 100 message rows for the active room
- **AND** the request SHALL NOT make a synchronous HTTP request to WAHA for the group name or profile

#### Scenario: Agent loads older group messages

- **GIVEN** the active group room has an older page available
- **WHEN** the agent invokes the localized “load older messages” action
- **THEN** the system SHALL request the next page using the last visible `(TglPesan, Id)` cursor
- **AND** older messages SHALL be prepended in chronological order
- **AND** existing `TChatD.Id` values SHALL appear at most once
- **AND** the action SHALL stop when no older page remains

#### Scenario: Older page contains payload-only media

- **GIVEN** an older `TChatD` row has media metadata only in `PayloadJson`
- **WHEN** the agent loads that older page
- **THEN** the message SHALL expose the same preview URL, download URL, category, and label contract as the latest page
- **AND** pagination order and duplicate prevention SHALL remain unchanged

#### Scenario: Group metadata is unavailable

- **GIVEN** the group name is not already persisted or cached
- **WHEN** the inbox list or active room is rendered
- **THEN** the UI SHALL immediately use the available master group name or canonical `@g.us` identifier
- **AND** group metadata retrieval SHALL run asynchronously on queue `waha-metadata`
- **AND** a successful metadata update MAY trigger a later inbox event without delaying message visibility

### Requirement: Queue-backed Group Synchronization

System SHALL persist a valid webhook message transactionally before broadcasting an inbox update, preserve webhook idempotency, and process group metadata independently from message delivery.

#### Scenario: New group message is processed with active workers

- **GIVEN** a valid, non-duplicate WAHA group message arrives
- **WHEN** `ProcessWebhookJob` handles it
- **THEN** the message SHALL be stored in `TChatD` and the parent `TChat` timestamp SHALL be updated before `inbox.updated` is broadcast
- **AND** the broadcast payload SHALL identify the persisted chat row
- **AND** any group metadata job SHALL use queue `waha-metadata` with timeout 20 seconds and at most 3 attempts

#### Scenario: Duplicate group webhook is retried

- **GIVEN** `TChatD.IdPesanWaha` already contains the incoming WAHA message ID
- **WHEN** the webhook is delivered again
- **THEN** the system SHALL not insert a second `TChatD` row
- **AND** the system MAY rebroadcast the existing chat identity without duplicating the visible message

#### Scenario: Queue worker is unavailable

- **GIVEN** the web request accepts the webhook but the `webhooks` or `broadcasts` worker is unavailable
- **WHEN** the webhook response is returned
- **THEN** the API SHALL still return the existing queued response contract
- **AND** the pending job SHALL remain retryable and observable
- **AND** the deployment documentation SHALL require workers for `webhooks`, `broadcasts`, and `waha-metadata`

### Requirement: Group Identity Backfill Safety

System SHALL provide an idempotent, SQL Server-compatible maintenance command to populate missing canonical group identity from existing `TChatD.PayloadJson` without deleting or merging chat rows.

#### Scenario: Operator previews legacy identity repair

- **GIVEN** legacy group messages contain a detectable group JID but `TChat.IdWahaTerdeteksi` is empty
- **WHEN** an operator runs `php artisan waha:backfill-group-chat-identity --dry-run`
- **THEN** the command SHALL report candidate and unparseable counts without writing data
- **AND** it SHALL NOT print webhook tokens, API keys, message payload bodies, or passwords
- **AND** it SHALL log only aggregate safety counts and dry-run state

#### Scenario: Operator applies identity repair

- **GIVEN** a SQL Server backup exists and the dry-run output has been reviewed
- **WHEN** the operator runs the command without `--dry-run`
- **THEN** the command SHALL fill only canonical group identity fields for matching group rows
- **AND** it SHALL never overwrite a non-empty `IdWahaTerdeteksi`
- **AND** it SHALL choose one deterministic latest detail payload per chat
- **AND** it SHALL be safe to run more than once
- **AND** it SHALL leave `TChatD` messages and physical `TChat` rows intact
