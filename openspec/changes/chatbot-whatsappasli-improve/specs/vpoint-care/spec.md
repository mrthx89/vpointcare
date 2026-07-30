## MODIFIED Requirements

### Requirement: WhatsApp Inbox

The system SHALL provide an admin inbox for reading, filtering, mapping, replying to WhatsApp conversations, and switching between original WhatsApp identity and internal WACS identity without changing persisted mapping data. The original WhatsApp identity mode SHALL read persisted WAHA identity snapshots before using payload or raw identifier fallbacks.

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
- AND the system SHALL display the persisted WAHA group name when available
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
- AND whatsapp mode SHALL prioritize persisted WAHA contact name, raw contact name, number, and personal WhatsApp JID in that order
- AND internal mode SHALL prioritize mapped contact, customer, instansi, and WhatsApp number with raw fallback

#### Scenario: WhatsApp original identity uses database snapshot

- GIVEN a chat has a successful WAHA identity snapshot in TChat
- WHEN an agent opens or refreshes Inbox WhatsApp in whatsapp mode
- THEN the system SHALL render the snapshot without making a synchronous WAHA metadata request during page rendering
- AND the system SHALL display the snapshot synchronization time when available
- AND the system SHALL keep the selected chat and filter state unchanged

#### Scenario: Group name is resolved from WAHA information

- GIVEN a group chat has a raw JID ending in @g.us
- AND the webhook payload and internal mapping do not provide a group name
- WHEN background WAHA identity synchronization completes with a valid group subject or name
- THEN the system SHALL persist the validated group name as the WAHA snapshot for that TChat record
- AND whatsapp mode SHALL show the persisted group name together with the @g.us JID
- AND the system SHALL NOT create or alter MGrupWhatsapp automatically

#### Scenario: LID contact name is resolved from WAHA information

- GIVEN a personal chat has a raw JID ending in @lid
- AND no mapped MNomorWhatsapp contact name is available
- WHEN background WAHA identity synchronization obtains a valid contact name
- THEN the system SHALL persist the WAHA contact name as the snapshot for that TChat record
- AND the system SHALL preserve the original @lid identifier
- AND the system SHALL display a resolved phone number only when WAHA returns a valid phone identity

#### Scenario: WAHA identity synchronization fails

- GIVEN a chat has an existing WAHA identity snapshot or raw identifier
- WHEN WAHA metadata retrieval times out, returns an invalid response, or is blocked by the circuit breaker
- THEN the system SHALL retain the last valid snapshot and raw identifier fallback
- AND the Inbox SHALL remain renderable without exposing the WAHA response body, API key, token, or stack trace
- AND the failure SHALL be recorded with a sanitized status for authorized operational diagnosis

#### Scenario: Agent refreshes WAHA identity

- GIVEN an agent can access Inbox WhatsApp and has selected a chat
- WHEN the agent requests a WAHA identity refresh
- THEN the system SHALL enqueue a deduplicated asynchronous refresh for that chat
- AND the UI SHALL immediately retain the database snapshot while refresh is pending
- AND a debounced Inbox update SHALL be broadcast after persisted identity data changes

## ADDED Requirements

### Requirement: Persisted WAHA Identity Synchronization

The system SHALL synchronize external WAHA group and contact metadata into a persisted, sanitized snapshot associated with TChat. Synchronization SHALL run on queue `webhooks`, use timeout 30 seconds, retry at most 3 times with 30- and 120-second backoff, and deduplicate requests for the same chat for 60 seconds.

#### Scenario: New chat schedules metadata synchronization

- GIVEN a valid WAHA webhook creates or updates a TChat record
- WHEN the database transaction commits successfully
- THEN the system SHALL enqueue identity synchronization after commit
- AND the webhook HTTP response SHALL NOT wait for the metadata request
- AND duplicate messages for the same chat within 60 seconds SHALL NOT enqueue duplicate metadata work

#### Scenario: Contact metadata is safely persisted

- GIVEN identity synchronization receives a WAHA contact response
- WHEN the response contains a validated display name or resolved phone identifier
- THEN the system SHALL persist only allowed identity fields and synchronization metadata on TChat
- AND the system SHALL keep MNomorWhatsapp, MCustomer, and MInstansi unchanged
- AND the system SHALL NOT persist the full WAHA response body as the identity snapshot

#### Scenario: Group metadata is safely persisted

- GIVEN identity synchronization receives a WAHA group response for a JID ending in @g.us
- WHEN the response contains a validated group subject or name
- THEN the system SHALL persist the group name as the WAHA snapshot on TChat
- AND the JID ending in @g.us SHALL remain the group identifier
- AND the system SHALL NOT infer a group identity from an individual message sender

#### Scenario: Failed synchronization retries and preserves fallback

- GIVEN a WAHA identity synchronization attempt fails transiently
- WHEN the job has remaining retry attempts
- THEN the system SHALL retry using the defined backoff schedule
- AND after the final failure the system SHALL store only a sanitized failure status/message
- AND the last valid snapshot, if any, SHALL remain available to Inbox WhatsApp
