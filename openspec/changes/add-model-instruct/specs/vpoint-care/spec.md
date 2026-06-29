# Spec Delta: VPoint Care Model Instruct Feature

## ADDED Requirements

### Requirement: Model Instruct Column
The system SHALL store a separate instruct model configuration in the `MPengaturanAi` table, distinct from the primary `ModelAi` column.

#### Scenario: Migration adds ModelInstructAi column
- GIVEN the database schema does not have `ModelInstructAi` column
- WHEN the migration runs
- THEN the system SHALL add a nullable `ModelInstructAi` column of type `nvarchar(100)`
- AND the migration SHALL be idempotent, checking for column existence before adding

#### Scenario: Migration rolls back
- GIVEN the `ModelInstructAi` column exists
- WHEN the rollback migration runs
- THEN the system SHALL remove only the `ModelInstructAi` column
- AND SHALL preserve all other columns and data

### Requirement: Model Instruct UI
The system SHALL display a Model Instruct configuration field on the AI Agent settings page.

#### Scenario: Administrator views AI Agent settings
- WHEN an administrator opens the AI Agent settings page
- THEN the system SHALL show:
  - A "Model Utama" field (previously just "Model")
  - A "Model Instruct" field
  - Help text for each explaining their purpose

#### Scenario: Administrator saves Model Instruct value
- GIVEN an administrator enters a value in the Model Instruct field
- WHEN the administrator saves AI settings
- THEN the system SHALL persist the value to `MPengaturanAi.ModelInstructAi`
- AND clear the AI settings cache
- AND show a success notification

#### Scenario: Model Instruct is empty
- WHEN Model Instruct field is left empty
- THEN the system SHALL treat it as "use the Primary Model"
- AND the internal assistant services SHALL fall back to `ModelAi`

### Requirement: VPoint Assistant Model Selection
The system SHALL use Model Instruct first for VPoint Assistant responses and suggested replies, then fall back to Primary Model if empty.

#### Scenario: VPoint Assistant uses Model Instruct
- GIVEN `ModelInstructAi` is configured
- WHEN a user sends a message to VPoint Assistant
- THEN the system SHALL call the AI provider using `ModelInstructAi`
- AND the suggested replies SHALL also be generated using `ModelInstructAi`

#### Scenario: VPoint Assistant falls back to Primary Model
- GIVEN `ModelInstructAi` is empty or not configured
- WHEN a user sends a message to VPoint Assistant
- THEN the system SHALL call the AI provider using `ModelAi` as before

### Requirement: Auto-Reply uses Primary Model Only
The system SHALL NOT use Model Instruct for customer auto-replies under any circumstances.

#### Scenario: Auto-reply customer message
- GIVEN AI auto-reply is active
- WHEN an incoming customer chat is processed
- THEN the system SHALL use only `ModelAi` for the auto-reply
- AND SHALL NOT attempt to use `ModelInstructAi`

### Requirement: VPoint Assistant UI Improvements
The system SHALL fix the VPoint Assistant UI bugs related to shadow, input styling, and suggested reply behavior.

#### Scenario: Suggested reply selected
- GIVEN suggested replies are visible
- WHEN a user clicks a suggested reply
- THEN the system SHALL populate the text input with that reply
- AND SHALL clear the suggested replies from the UI

#### Scenario: Input container styling
- WHEN viewing VPoint Assistant page
- THEN the bottom input container SHALL NOT have a shadow
- AND the textarea SHALL have a reasonable max-height
- AND the textarea SHALL have appropriate padding

## MODIFIED Requirements

### Requirement: AI Agent Settings
The system SHALL allow administrators to configure both Primary Model and Model Instruct on the AI Agent settings page.

#### Scenario: Administrator applies provider preset
- GIVEN an administrator selects a provider preset
- WHEN the preset is applied
- THEN the system SHALL set `ProviderAi`, `ModelAi`, and `BaseUrl` as before
- AND SHALL set `ModelInstructAi` to the preset instruct model OR to the same value as the preset main model
- AND SHALL NOT overwrite `ModelInstructAi` if it was already customized by the administrator
