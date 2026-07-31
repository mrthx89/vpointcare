## MODIFIED Requirements

### Requirement: WhatsApp Inbox

The system SHALL provide an admin inbox for reading, filtering, mapping, replying to WhatsApp conversations, and switching between original WhatsApp identity and internal WACS identity without changing persisted mapping data. Tab WhatsApp Asli SHALL menampilkan identitas dari snapshot WAHA dengan prioritas fallback yang benar dan membedakan sumber data secara visual.

#### Scenario: Agent replies to customer

- GIVEN an agent has access to Inbox WhatsApp
- WHEN the agent writes and sends a reply
- THEN the system SHALL persist the outgoing message
- AND the system SHALL send the message through WAHA
- AND the system SHALL update the chat last activity timestamp

#### Scenario: Agent switches identity display mode

- GIVEN an agent is viewing a chat in Inbox WhatsApp
- WHEN the agent toggles between WhatsApp Asli and Data Internal tabs
- THEN the system SHALL display identity from WAHA snapshot (NamaGrupWaha/NamaKontakWaha) for WhatsApp Asli tab
- AND the system SHALL display identity from master mapping (MGrupWhatsapp/MNomorWhatsapp) for Data Internal tab
- AND the system SHALL NOT modify persisted mapping data when switching tabs

#### Scenario: Group chat displays correct name from WAHA

- GIVEN a group chat exists with JID ending in @g.us
- WHEN the chat is displayed in WhatsApp Asli tab
- THEN the system SHALL prioritize TChat.NamaGrupWaha as the primary group name source
- AND if NamaGrupWaha is empty, the system SHALL fallback to payload group.subject/group.name
- AND if payload is empty, the system SHALL fallback to MGrupWhatsapp.NamaGrup
- AND if all sources are empty, the system SHALL display the raw JID
- AND the system SHALL display heroicon-o-user-group icon next to the group name
- AND the system SHALL display a source badge (WAHA/Payload/Internal/JID) indicating the data origin

#### Scenario: Private chat displays correct name from WAHA

- GIVEN a private chat exists with JID ending in @c.us or @lid
- WHEN the chat is displayed in WhatsApp Asli tab
- THEN the system SHALL prioritize TChat.NamaKontakWaha as the primary contact name source
- AND if NamaKontakWaha is empty, the system SHALL fallback to payload sender.pushname/notifyName
- AND if payload is empty, the system SHALL fallback to MNomorWhatsapp.NamaKontak
- AND the system SHALL display heroicon-o-user icon next to the contact name
- AND the system SHALL display a source badge indicating the data origin

#### Scenario: Background job syncs WAHA identity

- GIVEN a new incoming message arrives via webhook
- WHEN the webhook processor completes transaction successfully
- THEN the system SHALL dispatch SyncWahaChatIdentityJob if NamaGrupWaha or NamaKontakWaha is empty or older than 24 hours
- AND the job SHALL run on queue webhooks with timeout 30 seconds
- AND the job SHALL retry up to 3 times with backoff 30/120 seconds
- AND the job SHALL deduplicate per IdChat within 60 seconds
- AND the job SHALL update TChat.NamaGrupWaha or TChat.NamaKontakWaha without modifying master mapping tables

#### Scenario: Manual refresh identity

- GIVEN an agent with INBOX_MANAGE permission is viewing a chat
- WHEN the agent clicks Refresh Identity button
- THEN the system SHALL dispatch SyncWahaChatIdentityJob for that chat
- AND the system SHALL show loading indicator until job completes
- AND after job completes, the system SHALL reload chat data without full page refresh
- AND agents without INBOX_MANAGE permission SHALL NOT see the refresh button
