## Requirements

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
