# VPoint Care Capability Spec

## Purpose

VPoint Care SHALL provide a centralized WhatsApp customer-service workspace for VPoint agents and administrators. The system SHALL connect WAHA WhatsApp events, SQL Server operational data, Filament admin screens, AI-assisted responses, ticketing, and realtime notifications.

## Requirements

### Requirement: Admin Authentication

The system SHALL authenticate users through the internal `MPengguna` model and SHALL authorize access through role and permission data.

#### Scenario: User opens admin panel

- WHEN a user opens `/admin`
- THEN the system SHALL require login if no valid session exists
- AND successful login SHALL show menus permitted by the user's role

#### Scenario: Inactive user logs in

- WHEN an inactive user attempts to login
- THEN the system SHALL reject access
- AND the user SHALL not be able to enter the Filament panel

### Requirement: WhatsApp Webhook Intake

The system SHALL expose a WAHA webhook endpoint for incoming WhatsApp events.

#### Scenario: Valid WAHA message arrives

- WHEN WAHA posts a message to `/webhooks/waha/{token?}` with a valid token
- THEN the system SHALL normalize the sender/chat identifier
- AND the system SHALL persist the chat session and chat detail
- AND the system SHALL log the webhook processing result
- AND the system SHALL broadcast an inbox update when applicable

#### Scenario: Invalid webhook token arrives

- WHEN a webhook request uses an invalid token
- THEN the system SHALL reject the request
- AND the system SHALL not mutate chat data

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

### Requirement: Group Identity Backfill Safety

System SHALL provide an idempotent, SQL Server-compatible maintenance command to populate missing canonical group identity from existing TChatD.PayloadJson without deleting or merging chat rows.

#### Scenario: Operator previews legacy identity repair

- **GIVEN** legacy group messages contain a detectable group JID but TChat.IdWahaTerdeteksi is empty
- **WHEN** an operator runs php artisan waha:backfill-group-chat-identity --dry-run
- **THEN** the command SHALL report candidate and unparseable counts without writing data
- **AND** it SHALL NOT print webhook tokens, API keys, message payload bodies, or passwords
- **AND** it SHALL log only aggregate safety counts and dry-run state

#### Scenario: Operator applies identity repair

- **GIVEN** a SQL Server backup exists and the dry-run output has been reviewed
- **WHEN** the operator runs the command without --dry-run
- **THEN** the command SHALL fill only canonical group identity fields for matching group rows
- **AND** it SHALL never overwrite a non-empty IdWahaTerdeteksi
- **AND** it SHALL choose one deterministic latest detail payload per chat
- **AND** it SHALL be safe to run more than once
- **AND** it SHALL leave TChatD messages and physical TChat rows intact

### Requirement: WAHA Media and Profile

The system SHALL fetch and serve WAHA media/profile data through authenticated application routes and SHALL support media stored as a URL, data URI, or embedded base64 in TChatD.PayloadJson.

#### Scenario: Authenticated agent opens media URL

- GIVEN a chat message references WAHA media through TChatD.UrlMedia
- WHEN an authenticated agent opens /admin/waha-media/{message}
- THEN the system SHALL request or read the media from the supported URL/storage source
- AND the system SHALL return valid media through the application

#### Scenario: Embedded base64 is used when media URL is empty

- GIVEN TChatD.UrlMedia is empty
- AND TChatD.PayloadJson contains valid media in a supported explicit data URI or base64 key
- WHEN an authenticated agent opens /admin/waha-media/{message}
- THEN the system SHALL decode the embedded media using strict base64 validation
- AND the system SHALL return the decoded media without persisting another copy
- AND the response SHALL NOT expose PayloadJson or the base64 text

#### Scenario: Unusable media URL falls back to embedded payload

- GIVEN TChatD.UrlMedia cannot produce valid media
- AND TChatD.PayloadJson contains a valid embedded media candidate
- WHEN an authenticated agent opens the media route
- THEN the system SHALL use the embedded payload as fallback
- AND the failure of the URL source SHALL NOT prevent access to valid embedded media

#### Scenario: Media MIME type is resolved

- GIVEN a valid media binary is available
- WHEN the media response is prepared
- THEN the system SHALL resolve MIME in order from TChatD.TipeMime, data URI metadata, payload metadata, filename extension, and file signature
- AND the system SHALL use application/octet-stream if the MIME remains unknown
- AND MIME values containing invalid syntax or control characters SHALL be rejected

#### Scenario: Browser-supported media is previewed

- GIVEN valid media has a safe preview MIME for a raster image or sticker, audio/voice note, video, or PDF
- WHEN an authenticated agent views the message in Inbox WhatsApp
- THEN the system SHALL provide an inline media URL
- AND the renderer SHALL use an image, audio, video, or PDF-compatible browser presentation matching the resolved category
- AND the renderer SHALL also provide a separate download action

#### Scenario: Document or unknown file is available for download

- GIVEN valid media is a document, office file, archive, binary file, or another format outside the inline preview allowlist
- WHEN the message is rendered
- THEN the system SHALL display a file label and download action
- AND the system SHALL NOT render active or unknown content as trusted inline markup

#### Scenario: Agent downloads media

- GIVEN an authenticated agent can access a valid message media route
- WHEN the agent opens /admin/waha-media/{message}?download=1
- THEN the system SHALL return Content-Disposition attachment with a sanitized filename
- AND the filename SHALL NOT contain path traversal, quote injection, CRLF, or other control characters
- AND image, sticker, video, audio, PDF, document, and unknown binary media SHALL all support the download action

#### Scenario: Media is opened without download parameter

- GIVEN an authenticated agent opens a valid media route without download=1
- WHEN the resolved MIME is in the safe preview allowlist
- THEN the system SHALL return an inline response
- AND the response SHALL include Cache-Control private and X-Content-Type-Options nosniff

#### Scenario: Malformed base64 is handled safely

- GIVEN TChatD.PayloadJson contains malformed base64, an empty decoded value, unsupported JSON, or a non-media string in a candidate field
- WHEN the media route or Inbox renderer evaluates the message
- THEN the system SHALL report media as unavailable using a generic localized response
- AND the system SHALL NOT return partial binary content
- AND logs SHALL NOT include base64, full PayloadJson, media body, API keys, webhook tokens, or signed media URLs

#### Scenario: Guest opens protected media

- WHEN an unauthenticated user opens /admin/waha-media/{message}
- THEN the system SHALL require authentication
- AND the system SHALL NOT return URL-based or embedded media content


### Requirement: AI Agent Settings

The system SHALL allow administrators to configure AI provider, prompt behavior, API keys, auto-reply rules, provider connection testing, visual status, and send mode for OpenAI, DeepSeek, OpenRouter, and 9Router where configured.

#### Scenario: Administrator saves AI settings

- GIVEN an administrator has permission to manage AI Agent
- WHEN settings are saved with any supported provider
- THEN the system SHALL persist provider, model, prompt, send mode, schedule, and exclusion settings
- AND API keys SHALL be handled as secrets


#### Scenario: Administrator tests AI provider connection

- GIVEN an administrator has permission to manage AI Agent
- WHEN a test prompt is submitted from the AI Agent page
- THEN the system SHALL call the selected provider without sending a WAHA message
- AND the response or sanitized error SHALL be shown in the text result area
- AND no chat detail row SHALL be created by the test action

#### Scenario: API key is removed

- WHEN an administrator deletes a provider API key
- THEN the system SHALL remove that provider secret
- AND the provider SHALL no longer be used until a new key is configured

### Requirement: AI Auto Reply

The system SHALL generate AI replies for eligible incoming chats.

#### Scenario: Eligible incoming chat is processed

- GIVEN AI auto-reply is active
- AND the provider API key is configured
- AND the chat is inside configured auto-reply rules
- WHEN an incoming chat is received
- THEN the system SHALL build context from customer, instansi, session, and knowledge base
- AND request a reply from the selected AI provider
- AND store the AI response result

#### Scenario: Send-to-WAHA mode is active

- GIVEN `KirimKeWaha` is active
- WHEN AI generates a reply
- THEN the system SHALL send the reply through WAHA
- AND mark the response as sent or failed

#### Scenario: Draft-local mode is active

- GIVEN `KirimKeWaha` is inactive
- WHEN AI generates a reply
- THEN the system SHALL store the reply as a local draft/result
- AND SHALL NOT send the reply to WhatsApp automatically

#### Scenario: Holiday or outside working hour blocks reply

- GIVEN auto-reply rules disallow the current time or holiday
- WHEN a chat is processed
- THEN the system SHALL skip automatic reply
- AND record a skip reason for audit/debugging

### Requirement: Ticketing

The system SHALL support creating and tracking tickets from customer-service issues.

#### Scenario: Agent creates ticket

- GIVEN a customer chat requires escalation
- WHEN an agent creates a ticket
- THEN the system SHALL store ticket header/detail data
- AND allow status, priority, category, assignment, and attachment tracking

#### Scenario: Ticket appears in operational view

- WHEN a ticket is created or updated
- THEN authorized users SHALL be able to view its latest status from the Ticketing module

### Requirement: Ticket and Task Operations

The system SHALL provide database-backed ticket and task resources with permission-gated management, assignment history, progress records, private attachments, and user-specific filters.

#### Scenario: Authorized user manages tickets and tasks

- GIVEN the user has the matching `ticket.manage` or `task.manage` permission
- WHEN the user creates or updates a record
- THEN the system SHALL persist the record with a generated daily sequence number
- AND assignment changes SHALL be recorded in the matching assignment-history table
- AND the new assignee SHALL receive a database notification only when they have view permission

#### Scenario: User manages progress and private files

- GIVEN the user has manage permission
- WHEN the user adds a ticket note, task comment, task checklist item, or attachment
- THEN the system SHALL persist the related record
- AND each attachment SHALL be stored outside the public disk with a maximum size of 3 MB
- AND downloads SHALL require authentication and the matching view permission

#### Scenario: User filters assigned work

- GIVEN the user has view permission
- WHEN the user enables the "mine" filter
- THEN only tickets or tasks assigned to the authenticated `MPengguna` SHALL be displayed

### Requirement: Master Data Management

The system SHALL provide CRUD-style administration for master data needed by customer-service operations.

#### Scenario: Administrator manages WhatsApp number

- WHEN an administrator creates or updates a WhatsApp number
- THEN the system SHALL store display number, WAHA identity, active status, and metadata needed by WAHA integration

#### Scenario: Administrator manages knowledge base

- WHEN an administrator updates `MPengetahuan`
- THEN the AI Agent SHALL be able to use active knowledge entries as reply context

#### Scenario: Administrator manages holiday calendar

- WHEN an administrator updates `MHariLibur`
- THEN auto-reply and internal unanswered-chat notification rules SHALL evaluate the updated holiday data

### Requirement: VToken Instansi Synchronization

The system SHALL import customer/instansi data from the configured VToken open customer endpoint.

#### Scenario: Manual sync is triggered

- WHEN an administrator triggers sync or runs `php artisan vpoint:import-instansi-vtoken --sync`
- THEN the system SHALL call `VTOKEN_OPEN_CUSTOMERS_URL`
- AND upsert data into `MInstansi` based on the instansi/customer code

#### Scenario: Async sync is queued

- WHEN `php artisan vpoint:import-instansi-vtoken` is run without `--sync`
- THEN the system SHALL dispatch an import job to the queue

### Requirement: Scheduler and Background Jobs

The system SHALL run configured background jobs through Laravel queue and scheduler.

#### Scenario: Active job schedule exists

- GIVEN a row in `job_schedules` is active
- WHEN Laravel scheduler runs
- THEN the system SHALL schedule the configured command using the configured cron expression or schedule method
- AND prevent overlapping execution

#### Scenario: Unanswered chat notification runs

- WHEN `php artisan vpoint:kirim-notifikasi-chat-belum-terbalas` runs
- THEN the system SHALL inspect unanswered chats
- AND send internal WhatsApp notifications through WAHA for eligible chats
- AND skip sending outside configured work rules when applicable

### Requirement: Localization

The system SHALL support Indonesian and English UI labels.

#### Scenario: User switches locale

- WHEN a user opens `/locale/id` or `/locale/en`
- THEN the system SHALL store the selected locale
- AND render supported UI labels in that language

### Requirement: Logging and Auditability

The system SHALL log important integration, webhook, error, chat, and AI activities.

#### Scenario: Integration fails

- WHEN an external provider call fails
- THEN the system SHALL record enough error detail for debugging
- AND the UI/log module SHALL make the failure traceable by authorized users


### Requirement: Embedded WAHA Session Observability

The system SHALL provide an authenticated, localized WAHA Connection Center within the admin panel for observing every applicable `MSesiWhatsapp` logical session without requiring the operator to open the external WAHA dashboard.

The Connection Center SHALL present the session label/code, configured availability, normalized runtime status, connected number when WAHA provides it, capability availability, last checked timestamp, and an actionable generic condition. Runtime status SHALL be one of `running`, `starting`, `scan_required`, `stopped`, `failed`, `unavailable`, or `unknown`.

The runtime status SHALL remain distinct from persisted `MSesiWhatsapp.StatusSesi` and `MSesiWhatsapp.NonAktif`; ordinary status polling SHALL NOT overwrite those persisted fields.

#### Scenario: Authorized operator sees session health

- **GIVEN** an authenticated user has `waha_session.view`
- **AND** one or more `MSesiWhatsapp` records are available to the application
- **WHEN** the user opens WAHA Connection Center
- **THEN** the system SHALL show one row or card for each applicable session
- **AND** each session SHALL display its normalized runtime status and last checked timestamp
- **AND** the page SHALL use the active Bahasa Indonesia or English localization
- **AND** the user SHALL not need to open the external WAHA dashboard to see the status

#### Scenario: WAHA reports an authenticated session

- **GIVEN** WAHA reports that a logical session is authenticated and ready
- **WHEN** WACS refreshes the session status
- **THEN** the system SHALL normalize and display the state as `running`
- **AND** the connected WhatsApp number SHALL be displayed only when WAHA supplies a non-empty value
- **AND** the system SHALL preserve the existing `MSesiWhatsapp` configuration values

#### Scenario: WAHA requires a new authentication scan

- **GIVEN** WAHA reports that a session is awaiting authentication
- **WHEN** WACS refreshes the session status
- **THEN** the system SHALL normalize and display the state as `scan_required`
- **AND** the Connection Center SHALL show an operator with `waha_session.manage` how to obtain an inline QR code or supported pairing code
- **AND** the Inbox indicator for that session SHALL visibly warn that WhatsApp connectivity requires attention

#### Scenario: WAHA status endpoint is unavailable or returns an unknown state

- **GIVEN** the configured WAHA endpoint cannot be reached, rejects the configured credential, times out, or returns an unrecognized session state
- **WHEN** WACS refreshes the session status
- **THEN** the system SHALL display `unavailable` for transport/authentication failure or `unknown` for an unrecognized valid response
- **AND** the system SHALL show a localized, generic diagnostic message without exposing response body or credential details
- **AND** the system SHALL NOT display a fresh `running` state based solely on stale cached data

#### Scenario: Session is disabled in WACS configuration

- **GIVEN** `MSesiWhatsapp.NonAktif` is true for a session
- **WHEN** an authorized user views WAHA Connection Center
- **THEN** the system SHALL identify the session as disabled in WACS configuration
- **AND** the system SHALL not offer start, stop, restart, QR, or pairing actions for that session
- **AND** the system SHALL NOT modify the WAHA runtime state automatically

### Requirement: Native WAHA Connection and Session Recovery

The system SHALL allow only authorized operators to recover or manage a configured active WAHA session from within WACS through start, stop, restart/reconnect, QR authentication, and pairing-code authentication when the connected WAHA deployment supports the requested capability.

The system SHALL use an operation lock per session and SHALL not automatically retry a lifecycle mutation after a timeout or failed API response. Start, stop, and restart results SHALL be refreshed against WAHA before the UI reports the resulting session condition.

#### Scenario: Authorized operator starts a stopped session

- **GIVEN** an authenticated user has `waha_session.manage`
- **AND** a configured active session has normalized runtime status `stopped`
- **WHEN** the operator invokes Start Session
- **THEN** the system SHALL request the start operation from WAHA exactly once for that operator action
- **AND** the system SHALL lock duplicate lifecycle actions for the same session while the operation is in progress
- **AND** the system SHALL refresh and show the resulting normalized status
- **AND** the system SHALL show a localized failure state if WAHA does not accept or complete the request

#### Scenario: Operator repeats an idempotent lifecycle action

- **GIVEN** an authorized operator selects Start Session for a session already reported as `running`, or Stop Session for a session already reported as `stopped`
- **WHEN** the requested action reaches WACS
- **THEN** the system SHALL report the current resulting status without a duplicate concurrent operation
- **AND** the system SHALL NOT create a retry loop or queue job for that action
- **AND** the system SHALL keep the final outcome observable to the operator

#### Scenario: Authorized operator restarts a session

- **GIVEN** an authenticated user has `waha_session.manage`
- **AND** a configured active session is in `running`, `failed`, `unknown`, or `unavailable` state
- **WHEN** the operator confirms Restart Session
- **THEN** the system SHALL invoke only the restart/reconnect sequence supported by the connected WAHA deployment
- **AND** the system SHALL prevent an overlapping start, stop, restart, QR, or pairing operation for the same session
- **AND** the system SHALL refresh status after the operation and present its localized outcome

#### Scenario: Authorized operator scans an inline QR code

- **GIVEN** an authenticated user has `waha_session.manage`
- **AND** an active session has runtime status `scan_required`
- **AND** the connected WAHA deployment supports QR authentication
- **WHEN** the operator requests a QR code from WAHA Connection Center or the permitted Inbox recovery action
- **THEN** the system SHALL render the QR code inside WACS only for that authenticated operator session
- **AND** the system SHALL display expiration and refresh guidance
- **AND** the system SHALL refresh the status after the operator scans the QR code
- **AND** the system SHALL remove the QR value from the rendered state when it expires, the panel is closed, or the session becomes `running`

#### Scenario: Authorized operator requests a pairing code

- **GIVEN** an authenticated user has `waha_session.manage`
- **AND** an active session has runtime status `scan_required`
- **WHEN** the operator enters a valid phone number and requests a pairing code
- **THEN** the system SHALL request and display the pairing code only when the connected WAHA deployment reports pairing capability support
- **AND** the system SHALL validate the requested phone number before sending it to WAHA
- **AND** the system SHALL provide QR authentication as the fallback path when pairing is unsupported or unavailable

#### Scenario: User without manage permission attempts a session action

- **GIVEN** an authenticated user does not have `waha_session.manage`
- **WHEN** the user attempts to invoke start, stop, restart, QR, or pairing functionality through a direct Livewire request or UI action
- **THEN** the system SHALL deny the action
- **AND** the system SHALL NOT call WAHA or mutate session state
- **AND** the system SHALL NOT disclose QR, pairing code, API key, token, password, or raw WAHA response

### Requirement: Inbox WAHA Health Visibility

The system SHALL show a compact WAHA session-health indicator in Inbox WhatsApp independently from the Echo/Reverb client indicator so agents can distinguish WhatsApp gateway availability from browser realtime connectivity.

The indicator SHALL represent the session configured for the selected/default Inbox operation and SHALL be sourced through bounded status refresh and short-lived per-session cache. It SHALL NOT issue a WAHA request for every chat row, message bubble, or ordinary component render.

#### Scenario: Inbox displays a healthy WAHA connection

- **GIVEN** an agent with `inbox.view` opens Inbox WhatsApp
- **AND** the relevant WAHA session has normalized runtime status `running`
- **WHEN** the Inbox header renders
- **THEN** the system SHALL display a localized, visually distinct healthy WAHA indicator
- **AND** the indicator SHALL remain separate from the Echo/Reverb connectivity indicator
- **AND** the Inbox chat list, filters, selected chat, and message order SHALL remain unchanged

#### Scenario: Inbox displays a session requiring attention

- **GIVEN** an agent with `inbox.view` opens Inbox WhatsApp
- **AND** the relevant WAHA session has runtime status `scan_required`, `stopped`, `failed`, `unavailable`, or `unknown`
- **WHEN** the Inbox header renders or refreshes
- **THEN** the system SHALL display a localized warning/error indicator that identifies WAHA as the affected service
- **AND** an agent without `waha_session.manage` SHALL see no QR, pairing, or lifecycle control
- **AND** an authorized manager SHALL be offered a link or recovery entry point to the native WACS Connection Center

#### Scenario: Inbox status refresh is bounded

- **GIVEN** an Inbox contains many chat sessions and message rows
- **WHEN** the Inbox status indicator refreshes
- **THEN** the system SHALL resolve the relevant WAHA status at most once per configured status refresh/cache interval for that session
- **AND** the system SHALL NOT make a synchronous WAHA status request for each chat row or message row
- **AND** a temporary WAHA failure SHALL not prevent the Inbox from rendering existing chat data

### Requirement: Secure WAHA Session Control Observability

The system SHALL make WAHA session-control outcomes observable to authorized operators while preventing QR values, pairing codes, API keys, webhook tokens, passwords, authorization headers, and full sensitive WAHA response bodies from being persisted or logged.

The system SHALL record authorized lifecycle and onboarding actions with non-sensitive metadata when `TLogAktivitas` is available. This metadata SHALL include the operator identity, logical session code, requested action, normalized outcome, and time of the action without including secret-bearing values.

#### Scenario: Operator action is audited safely

- **GIVEN** an authorized operator refreshes status, starts, stops, restarts, requests QR, or requests a pairing code
- **WHEN** WACS completes or rejects the action
- **THEN** the system SHALL record an audit outcome when `TLogAktivitas` is available
- **AND** the audit SHALL contain only non-sensitive action metadata
- **AND** the action outcome SHALL remain visible to the operator through localized UI feedback

#### Scenario: QR or pairing response is received

- **GIVEN** WAHA returns a QR value, QR image, pairing code, or other authentication artifact
- **WHEN** WACS prepares the inline operator response
- **THEN** the system SHALL keep the artifact only in ephemeral authenticated UI state
- **AND** the system SHALL NOT write the artifact to `TLogIntegrasi`, `TLogAktivitas`, `TLogError`, application log, database, or status cache
- **AND** the system SHALL NOT expose the artifact to users without `waha_session.manage`

#### Scenario: WAHA returns a sensitive or malformed failure response

- **GIVEN** WAHA returns an error response that includes sensitive fields or malformed content
- **WHEN** WACS handles the failure
- **THEN** the system SHALL map the failure to a generic localized message and normalized status/error category
- **AND** the system SHALL redact or omit sensitive fields before any audit or technical log is written
- **AND** the system SHALL preserve the existing webhook route and chat data without mutation from the failed control-plane request

### Requirement: WAHA Control-Plane Compatibility and Operational Safety

The system SHALL preserve existing WAHA data-plane behavior while adding the embedded control plane. The existing `/webhooks/waha/{token?}` route, WAHA message delivery, chat identity normalization, SQL Server compatibility, and existing queue contracts SHALL remain unchanged.

The control plane SHALL not require a schema migration in this change and SHALL not create queue jobs for status polling or lifecycle mutation. Existing webhook, AI, broadcast, and WAHA metadata queues SHALL retain their configured names, timeouts, retries, and idempotency behavior.

#### Scenario: Existing inbound and outbound messaging continues during normal status observation

- **GIVEN** a WAHA session is `running`
- **AND** the Connection Center or Inbox status indicator is active
- **WHEN** WAHA posts a valid message to `/webhooks/waha/{token?}` or an authorized agent sends a message from Inbox
- **THEN** the system SHALL preserve the existing webhook validation, idempotent persistence, queue dispatch, and WAHA send behavior
- **AND** status observation SHALL NOT alter `TChat`, `TChatD`, message order, or WAHA identifier normalization

#### Scenario: Control-plane feature is deployed to an existing SQL Server database

- **GIVEN** WACS uses an existing supported Microsoft SQL Server database
- **WHEN** the embedded WAHA session-control feature is deployed
- **THEN** the feature SHALL operate using existing session and log tables when present
- **AND** the deployment SHALL NOT require `migrate:fresh`, `db:wipe`, destructive reset, or a new schema migration for this feature
- **AND** the application SHALL continue to function if optional audit storage is unavailable in a legacy test environment

#### Scenario: Deployment runs with existing workers

- **GIVEN** the web server, queue workers, scheduler, Reverb, and WAHA service are running under the existing deployment topology
- **WHEN** the embedded Connection Center is used
- **THEN** the system SHALL not require a new queue worker solely for status, QR, pairing, start, stop, or restart operations
- **AND** existing workers for `webhooks`, `ai-replies`, `broadcasts`, and `waha-metadata` SHALL continue their existing responsibilities
- **AND** status failures SHALL remain visible without silently discarding webhook or queued work

<!-- Non-Functional Requirements -->

### Requirement: Production Safety

The system SHALL be deployable with environment-specific secrets and production optimizations.

#### Scenario: Production deployment is published

- WHEN a new version is deployed
- THEN dependencies SHALL be installed without dev packages
- AND assets SHALL be built
- AND migrations SHALL run with `--force`
- AND caches SHALL be regenerated
- AND queue/reverb/scheduler processes SHALL be restarted

### Requirement: SQL Server Compatibility

The system SHALL remain compatible with Microsoft SQL Server.

#### Scenario: Migration is executed

- WHEN migrations run
- THEN SQL Server-specific schema scripts SHALL execute only through the `sqlsrv` connection
- AND unsupported database drivers SHALL be rejected for the WACS schema migration

### Requirement: Secret Handling

The system SHALL keep operational secrets outside source code.

#### Scenario: Source code is committed

- WHEN repository files are committed
- THEN `.env`, production API keys, production webhook tokens, and production database passwords SHALL NOT be included





