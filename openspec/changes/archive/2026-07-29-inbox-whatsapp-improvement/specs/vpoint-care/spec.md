## MODIFIED Requirements

### Requirement: WhatsApp Inbox

The system SHALL provide an admin inbox for reading, filtering, mapping, replying to WhatsApp conversations, and switching between original WhatsApp identity and internal WACS identity without changing persisted mapping data.

#### Scenario: Agent replies to customer

- **GIVEN** an agent has access to Inbox WhatsApp
- **WHEN** the agent writes and sends a reply
- **THEN** the system SHALL send the message through WAHA
- **AND** the system SHALL store the outgoing message in chat history
- **AND** the system SHALL update the conversation status/timestamp

#### Scenario: Agent saves internal note

- **GIVEN** a chat session exists
- **WHEN** an agent saves an internal note
- **THEN** the system SHALL store the note as internal-only data
- **AND** the note SHALL not be sent to WhatsApp

#### Scenario: Agent closes conversation

- **GIVEN** a chat session is open
- **WHEN** an agent closes the conversation
- **THEN** the system SHALL update chat status to closed
- **AND** the closed conversation SHALL remain visible in chat history

#### Scenario: Inbox opens with original WhatsApp identity

- **GIVEN** an authenticated agent opens Inbox WhatsApp
- **WHEN** the page initializes
- **THEN** identityDisplayMode SHALL be whatsapp
- **AND** the chat list and selected-chat header SHALL prioritize identity received from WhatsApp
- **AND** the mode SHALL NOT modify TChat, TChatD, MNomorWhatsapp, MGrupWhatsapp, MCustomer, or MInstansi

#### Scenario: Agent switches to internal identity

- **GIVEN** Inbox WhatsApp is displaying whatsapp identity mode
- **WHEN** the agent selects internal mode
- **THEN** the chat list, selected-chat header, and identity detail SHALL prioritize mapped MNomorWhatsapp, MGrupWhatsapp, MCustomer, and MInstansi data
- **AND** missing internal values SHALL fall back to available WhatsApp identity
- **AND** the selected chat, filters, message order, and persisted mapping SHALL remain unchanged

#### Scenario: Invalid identity mode is submitted

- **WHEN** Livewire receives an identityDisplayMode value other than whatsapp or internal
- **THEN** the system SHALL reset the value to whatsapp
- **AND** the system SHALL NOT execute a dynamic query or render payload-derived markup from the invalid value

#### Scenario: WhatsApp group identity is displayed

- **GIVEN** TChat.JenisChat is Grup
- **WHEN** the inbox renders the chat in whatsapp mode
- **THEN** the system SHALL label the conversation as Grup WhatsApp
- **AND** the system SHALL display the raw WhatsApp group name when available
- **AND** the system SHALL display the group JID ending in @g.us or the available raw group number
- **AND** the group identity SHALL NOT be replaced by a participant, author, or sender identifier

#### Scenario: Internally mapped group identity is displayed

- **GIVEN** TChat.JenisChat is Grup
- **AND** the chat is mapped to MGrupWhatsapp
- **WHEN** the inbox renders the chat in internal mode
- **THEN** the system SHALL display the mapped group name, mapped instansi, and mapped group identifier/number
- **AND** missing mapped values SHALL fall back individually to the WhatsApp group identity

#### Scenario: Group message sender is displayed separately

- **GIVEN** an incoming message belongs to a group chat
- **WHEN** the message bubble is rendered
- **THEN** the system SHALL display TChatD.PengirimNamaKontak as the sender name when available
- **AND** the system SHALL display TChatD.PengirimNomorWhatsapp as the sender number when available
- **AND** the sender name/number SHALL NOT replace the group name, group JID, or group number

#### Scenario: Personal chat identity is displayed

- **GIVEN** TChat.JenisChat is Pribadi
- **WHEN** the inbox renders the conversation
- **THEN** the system SHALL label the conversation as Chat Pribadi
- **AND** whatsapp mode SHALL prioritize raw contact name, number, and personal WhatsApp JID
- **AND** internal mode SHALL prioritize mapped contact, customer, instansi, and WhatsApp number with raw fallback

### Requirement: WAHA Media and Profile

The system SHALL fetch and serve WAHA media/profile data through authenticated application routes and SHALL support media stored as a URL, data URI, or embedded base64 in TChatD.PayloadJson.

#### Scenario: Authenticated agent opens media URL

- **GIVEN** a chat message references WAHA media through TChatD.UrlMedia
- **WHEN** an authenticated agent opens /admin/waha-media/{message}
- **THEN** the system SHALL request or read the media from the supported URL/storage source
- **AND** the system SHALL return valid media through the application

#### Scenario: Embedded base64 is used when media URL is empty

- **GIVEN** TChatD.UrlMedia is empty
- **AND** TChatD.PayloadJson contains valid media in a supported explicit data URI or base64 key
- **WHEN** an authenticated agent opens /admin/waha-media/{message}
- **THEN** the system SHALL decode the embedded media using strict base64 validation
- **AND** the system SHALL return the decoded media without persisting another copy
- **AND** the response SHALL NOT expose PayloadJson or the base64 text

#### Scenario: Unusable media URL falls back to embedded payload

- **GIVEN** TChatD.UrlMedia cannot produce valid media
- **AND** TChatD.PayloadJson contains a valid embedded media candidate
- **WHEN** an authenticated agent opens the media route
- **THEN** the system SHALL use the embedded payload as fallback
- **AND** the failure of the URL source SHALL NOT prevent access to valid embedded media

#### Scenario: Media MIME type is resolved

- **GIVEN** a valid media binary is available
- **WHEN** the media response is prepared
- **THEN** the system SHALL resolve MIME in order from TChatD.TipeMime, data URI metadata, payload metadata, filename extension, and file signature
- **AND** the system SHALL use application/octet-stream if the MIME remains unknown
- **AND** MIME values containing invalid syntax or control characters SHALL be rejected

#### Scenario: Browser-supported media is previewed

- **GIVEN** valid media has a safe preview MIME for a raster image or sticker, audio/voice note, video, or PDF
- **WHEN** an authenticated agent views the message in Inbox WhatsApp
- **THEN** the system SHALL provide an inline media URL
- **AND** the renderer SHALL use an image, audio, video, or PDF-compatible browser presentation matching the resolved category
- **AND** the renderer SHALL also provide a separate download action

#### Scenario: Document or unknown file is available for download

- **GIVEN** valid media is a document, office file, archive, binary file, or another format outside the inline preview allowlist
- **WHEN** the message is rendered
- **THEN** the system SHALL display a file label and download action
- **AND** the system SHALL NOT render active or unknown content as trusted inline markup

#### Scenario: Agent downloads media

- **GIVEN** an authenticated agent can access a valid message media route
- **WHEN** the agent opens /admin/waha-media/{message}?download=1
- **THEN** the system SHALL return Content-Disposition attachment with a sanitized filename
- **AND** the filename SHALL NOT contain path traversal, quote injection, CRLF, or other control characters
- **AND** image, sticker, video, audio, PDF, document, and unknown binary media SHALL all support the download action

#### Scenario: Media is opened without download parameter

- **GIVEN** an authenticated agent opens a valid media route without download=1
- **WHEN** the resolved MIME is in the safe preview allowlist
- **THEN** the system SHALL return an inline response
- **AND** the response SHALL include Cache-Control private and X-Content-Type-Options nosniff

#### Scenario: Malformed base64 is handled safely

- **GIVEN** TChatD.PayloadJson contains malformed base64, an empty decoded value, unsupported JSON, or a non-media string in a candidate field
- **WHEN** the media route or Inbox renderer evaluates the message
- **THEN** the system SHALL report media as unavailable using a generic localized response
- **AND** the system SHALL NOT return partial binary content
- **AND** logs SHALL NOT include base64, full PayloadJson, media body, API keys, webhook tokens, or signed media URLs

#### Scenario: Guest opens protected media

- **WHEN** an unauthenticated user opens /admin/waha-media/{message}
- **THEN** the system SHALL require authentication
- **AND** the system SHALL NOT return URL-based or embedded media content
