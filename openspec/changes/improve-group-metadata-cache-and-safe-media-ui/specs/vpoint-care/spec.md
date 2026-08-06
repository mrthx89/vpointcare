## MODIFIED Requirements

### Requirement: WhatsApp Inbox
The system SHALL provide an admin inbox for reading, filtering, mapping, replying to WhatsApp conversations, displaying cached group names without render-time WAHA metadata requests, and switching between original WhatsApp identity and internal WACS identity without changing persisted mapping data.

#### Scenario: Agent replies to customer

- GIVEN an agent has access to Inbox WhatsApp
- WHEN the agent writes and sends a reply
- THEN the system SHALL send the message through WAHA
- AND the system SHALL store the outgoing message in chat history
- AND the system SHALL update the conversation status/timestamp

#### Scenario: Agent saves internal note

- GIVEN a chat session exists
- WHEN an agent saves an internal note
- THEN the system SHALL store the note as internal-only data
- AND the note SHALL not be sent to WhatsApp

#### Scenario: Agent closes conversation

- GIVEN a chat session is open
- WHEN an agent closes the conversation
- THEN the system SHALL update chat status to closed
- AND the closed conversation SHALL remain visible in chat history

#### Scenario: Inbox opens with original WhatsApp identity

- GIVEN an authenticated agent opens Inbox WhatsApp
- WHEN the page initializes
- THEN identityDisplayMode SHALL be whatsapp
- AND the chat list and selected-chat header SHALL prioritize identity received from WhatsApp
- AND the mode SHALL NOT modify TChat, TChatD, MNomorWhatsapp, MGrupWhatsapp, MCustomer, or MInstansi

#### Scenario: Agent switches to internal identity

- GIVEN Inbox WhatsApp is displaying whatsapp identity mode
- WHEN the agent selects internal mode
- THEN the chat list, selected-chat header, and identity detail SHALL prioritize mapped MNomorWhatsapp, MGrupWhatsapp, MCustomer, and MInstansi data
- AND missing internal values SHALL fall back to available WhatsApp identity
- AND the selected chat, filters, message order, and persisted mapping SHALL remain unchanged

#### Scenario: Invalid identity mode is submitted

- WHEN Livewire receives an identityDisplayMode value other than whatsapp or internal
- THEN the system SHALL reset the value to whatsapp
- AND the system SHALL NOT execute a dynamic query or render payload-derived markup from the invalid value

#### Scenario: WhatsApp group identity is displayed

- GIVEN TChat.JenisChat is Grup
- WHEN the inbox renders the chat in whatsapp mode
- THEN the system SHALL label the conversation as Grup WhatsApp
- AND the system SHALL display the cached database group name when available
- AND the system SHALL display Unknown Group when the cached group name is empty
- AND the visible group title SHALL NOT be replaced by a group JID, participant, author, or sender identifier

#### Scenario: Internally mapped group identity is displayed

- GIVEN TChat.JenisChat is Grup
- AND the chat is mapped to MGrupWhatsapp
- WHEN the inbox renders the chat in internal mode
- THEN the system SHALL display the mapped group name, mapped instansi, and mapped group identifier/number
- AND missing mapped values SHALL fall back individually to the cached WhatsApp group name or Unknown Group

#### Scenario: Group message sender is displayed separately

- GIVEN an incoming message belongs to a group chat
- WHEN the message bubble is rendered
- THEN the system SHALL display TChatD.PengirimNamaKontak as the sender name when available
- AND the system SHALL display TChatD.PengirimNomorWhatsapp separately when available
- AND the sender identity SHALL NOT replace the group conversation identity

#### Scenario: Agent manually syncs selected group name

- GIVEN an agent with inbox manage permission is viewing a group chat
- WHEN the agent invokes Sync Group Name
- THEN the system SHALL dispatch RefreshWahaGroupMetadataJob for only the selected group on queue waha-metadata
- AND the request SHALL NOT perform a synchronous WAHA HTTP request
- AND the inbox SHALL remain usable while the job runs asynchronously

#### Scenario: Administrator syncs missing group names

- GIVEN an administrator has inbox manage permission
- WHEN the administrator invokes Sync All Group Names
- THEN the system SHALL dispatch RefreshWahaGroupMetadataJob only for group chats where the cached database group name is null or empty
- AND the system SHALL report queued, skipped, and failed counts
- AND the request SHALL NOT perform synchronous WAHA HTTP requests

#### Scenario: Media message with embedded payload renders safely

- GIVEN a media message contains an image, video, audio, document, or embedded base64 payload
- WHEN the message is rendered in the inbox
- THEN the UI SHALL show only the supported preview, filename or media label, caption text, and download action
- AND the UI SHALL NOT render base64 text, encoded binary body, or PayloadJson media blob
- AND the media route MAY still decode embedded payload internally for preview or download

### Requirement: Group Chat Latest State and Bounded Pagination
System SHALL render the latest persisted messages for a group conversation from local database state without blocking on an external WAHA request, and SHALL load older messages through deterministic cursor pagination without duplicates.

#### Scenario: Large group opens at the latest message window

- **GIVEN** a group room has more than 200 persisted `TChatD` messages across its canonical sibling rows
- **WHEN** an agent opens the room or receives a realtime refresh
- **THEN** the visible message window SHALL contain the newest messages, including the message with the greatest `(TglPesan, Id)` ordering
- **AND** the initial request SHALL load at most 100 message rows for the active room
- **AND** the request SHALL NOT make a synchronous HTTP request to WAHA for the group name or profile

#### Scenario: Agent loads older group messages

- **GIVEN** the active group room has an older page available
- **WHEN** the agent invokes the localized load-older-messages action
- **THEN** the system SHALL request the next page using the last visible cursor `(TglPesan, Id)`
- **AND** older messages SHALL be prepended in chronological order
- **AND** existing TChatD.Id values SHALL appear at most once
- **AND** the action SHALL stop when no older page remains

#### Scenario: Older page contains payload-only media

- **GIVEN** an older TChatD row has media metadata only in PayloadJson
- **WHEN** the agent loads that older page
- **THEN** the message SHALL expose the same preview URL, download URL, category, and label contract as the latest page
- **AND** pagination order and duplicate prevention SHALL remain unchanged

#### Scenario: Group metadata is unavailable

- **GIVEN** the group name is not already persisted or cached
- **WHEN** the inbox list or active room is rendered
- **THEN** the UI SHALL immediately use Unknown Group for the visible title
- **AND** group metadata retrieval SHALL run asynchronously on queue waha-metadata only when explicitly dispatched by webhook or sync action
- **AND** a successful metadata update MAY trigger a later inbox event without delaying message visibility

### Requirement: Queue-backed Group Synchronization
System SHALL persist a valid webhook message transactionally before broadcasting an inbox update, preserve webhook idempotency, and process group metadata independently from message delivery.

#### Scenario: New group message is processed with active workers

- **GIVEN** a valid, non-duplicate WAHA group message arrives
- **WHEN** ProcessWebhookJob handles it
- **THEN** the message SHALL be stored in TChatD and the parent TChat timestamp SHALL be updated before inbox.updated is broadcast
- **AND** the broadcast payload SHALL identify the persisted chat row
- **AND** any group metadata job SHALL use queue waha-metadata with timeout 20 seconds and at most 3 attempts
- **AND** the group metadata result SHALL update the cached database group name columns that exist in the environment

#### Scenario: Duplicate group webhook is retried

- **GIVEN** TChatD.IdPesanWaha already contains the incoming WAHA message ID
- **WHEN** the webhook is delivered again
- **THEN** the system SHALL not insert a second TChatD row
- **AND** the system MAY rebroadcast the existing chat identity without duplicating the visible message

#### Scenario: Queue worker is unavailable

- **GIVEN** the web request accepts the webhook but the webhooks or broadcasts worker is unavailable
- **WHEN** the webhook response is returned
- **THEN** the API SHALL still return the existing queued response contract
- **AND** the pending job SHALL remain retryable and observable
- **AND** the deployment documentation SHALL require workers for webhooks, broadcasts, and waha-metadata
