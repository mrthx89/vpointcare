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
