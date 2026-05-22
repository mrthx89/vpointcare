# Spec Delta: VPoint Care AI Agent 9Router

## ADDED Requirements

### Requirement: 9Router Provider Option

The system SHALL allow administrators to select `9Router` as an AI provider for AI Agent settings when 9Router support is enabled in configuration.

#### Scenario: Administrator selects 9Router preset

- GIVEN an administrator has permission to manage AI Agent
- WHEN the administrator selects the `9Router` provider preset
- THEN the system SHALL set provider value to the agreed internal 9Router provider key
- AND populate the model from 9Router configuration
- AND populate the base URL from 9Router configuration
- AND show the 9Router API key state without exposing the secret value

#### Scenario: Administrator saves 9Router API key

- GIVEN an administrator has permission to manage AI Agent
- WHEN the administrator saves a new 9Router API key
- THEN the system SHALL store the key encrypted
- AND SHALL NOT store the plaintext key in source code, logs, or browser-rendered state
- AND the UI SHALL indicate that the 9Router API key is configured

#### Scenario: Administrator deletes 9Router API key

- GIVEN a 9Router API key is configured
- WHEN the administrator deletes the 9Router API key
- THEN the system SHALL remove only the 9Router secret
- AND other provider API keys SHALL remain unchanged

### Requirement: 9Router AI Auto Reply

The system SHALL generate AI replies using 9Router when AI Agent settings select 9Router and a valid API key is available.

#### Scenario: Eligible chat uses 9Router

- GIVEN AI auto-reply is active
- AND provider is set to 9Router
- AND a valid 9Router API key is configured
- AND the chat passes work-hour, holiday, exclusion, and send-mode rules
- WHEN an incoming customer chat is processed
- THEN the system SHALL build the same conversation and knowledge context used by existing providers
- AND send a chat completion request to the configured 9Router endpoint
- AND store the generated reply result with provider context for troubleshooting

#### Scenario: 9Router request fails

- GIVEN provider is set to 9Router
- WHEN the 9Router API request fails or returns an unsupported response
- THEN the system SHALL not send an empty reply to WAHA
- AND the system SHALL log a clear failure reason
- AND the chat processing flow SHALL remain recoverable for manual customer-service response

### Requirement: AI Connection Test Dialog

The system SHALL allow administrators to test the currently selected AI provider from the AI Agent page without sending any message to WhatsApp customers.

#### Scenario: Administrator opens test dialog

- GIVEN an administrator has permission to manage AI Agent
- WHEN the administrator clicks `Test Koneksi AI`
- THEN the system SHALL open a dialog/modal
- AND the dialog SHALL show an input box for a test prompt
- AND the dialog SHALL show a text result area
- AND the default/example prompt MAY be `Apakah kamu sudah siap? Nama kamu siapa?`

#### Scenario: AI connection test succeeds

- GIVEN the test dialog is open
- AND the selected provider has a valid API key, model, and base URL
- WHEN the administrator submits a test prompt
- THEN the system SHALL call the selected AI provider using the active settings
- AND the AI response SHALL be displayed in the text result area
- AND no WhatsApp message SHALL be sent
- AND no customer chat row SHALL be created or mutated

#### Scenario: AI connection test fails

- GIVEN the test dialog is open
- WHEN the selected provider cannot be reached or returns an error
- THEN the system SHALL display the error reason in the text result area
- AND the error output SHALL NOT expose API keys or secrets
- AND the dialog SHALL remain usable for another test after settings are fixed

#### Scenario: AI connection test is missing configuration

- GIVEN the test dialog is open
- WHEN API key, provider, model, or base URL configuration is missing or invalid
- THEN the system SHALL display a clear validation message in the text result area
- AND the system SHALL NOT attempt to send a request with incomplete secret configuration

### Requirement: AI Agent Visual Icon

The system SHALL display a visually distinct AI Agent icon/hero element on the AI Agent page.

#### Scenario: Administrator opens AI Agent page

- WHEN an authorized administrator opens the AI Agent page
- THEN the page SHALL show an AI Agent icon or illustration near the top of the page
- AND the visual SHALL work in light mode and dark mode
- AND the layout SHALL remain responsive on desktop and tablet widths

#### Scenario: Navigation menu renders AI Agent

- WHEN the admin navigation is rendered
- THEN the AI Agent menu SHALL continue to show a valid icon
- AND the icon SHALL not break permission-based menu configuration

## MODIFIED Requirements

### Requirement: AI Agent Settings

The system SHALL allow administrators to configure AI provider, prompt behavior, API keys, auto-reply rules, send mode, provider connection testing, and provider-specific presets including OpenAI, DeepSeek, OpenRouter, and 9Router where configured.

#### Scenario: Administrator saves AI settings

- GIVEN an administrator has permission to manage AI Agent
- WHEN settings are saved with any supported provider
- THEN the system SHALL persist provider, model, prompt, send mode, schedule, and exclusion settings
- AND API keys SHALL be handled as secrets
- AND provider-specific default model/base URL SHALL be preserved unless explicitly overridden
