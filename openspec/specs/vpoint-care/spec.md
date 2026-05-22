# VPoint Care Capability Spec

## Overview

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

The system SHALL provide an admin inbox for reading, filtering, mapping, and replying to WhatsApp conversations.

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

### Requirement: WAHA Media and Profile

The system SHALL fetch and serve WAHA media/profile data through authenticated application routes.

#### Scenario: Authenticated agent opens media

- GIVEN a chat message references WAHA media
- WHEN an authenticated agent opens the media route
- THEN the system SHALL request the media from WAHA
- AND return it to the browser through the application

#### Scenario: Guest opens protected media

- WHEN an unauthenticated user opens `/admin/waha-media/{message}`
- THEN the system SHALL require authentication

### Requirement: AI Agent Settings

The system SHALL allow administrators to configure AI provider, prompt behavior, API keys, auto-reply rules, and send mode.

#### Scenario: Administrator saves AI settings

- GIVEN an administrator has permission to manage AI Agent
- WHEN settings are saved
- THEN the system SHALL persist provider, model, prompt, send mode, schedule, and exclusion settings
- AND API keys SHALL be handled as secrets

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

## Non-Functional Requirements

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
