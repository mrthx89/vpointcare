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

The system SHALL provide an admin inbox for reading, filtering, mapping, replying to WhatsApp conversations, and switching between original WhatsApp identity and internal WACS identity without changing persisted mapping data.

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
- AND the system SHALL display the raw WhatsApp group name when available
- AND the system SHALL display the group JID ending in @g.us or the available raw group number
- AND the group identity SHALL NOT be replaced by a participant, author, or sender identifier

#### Scenario: Internally mapped group identity is displayed

- GIVEN TChat.JenisChat is Grup
- AND the chat is mapped to MGrupWhatsapp
- WHEN the inbox renders the chat in internal mode
- THEN the system SHALL display the mapped group name, mapped instansi, and mapped group identifier/number
- AND missing mapped values SHALL fall back individually to the WhatsApp group identity

#### Scenario: Group message sender is displayed separately

- GIVEN an incoming message belongs to a group chat
- WHEN the message bubble is rendered
- THEN the system SHALL display TChatD.PengirimNamaKontak as the sender name when available
- AND the system SHALL display TChatD.PengirimNomorWhatsapp as the sender number when available
- AND the sender name/number SHALL NOT replace the group name, group JID, or group number

#### Scenario: Personal chat identity is displayed

- GIVEN TChat.JenisChat is Pribadi
- WHEN the inbox renders the conversation
- THEN the system SHALL label the conversation as Chat Pribadi
- AND whatsapp mode SHALL prioritize raw contact name, number, and personal WhatsApp JID
- AND internal mode SHALL prioritize mapped contact, customer, instansi, and WhatsApp number with raw fallback

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





