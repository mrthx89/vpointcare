---
source_file: ".kiro/specs/ai-model-instruct-and-ui-improvements/design.md"
type: "rationale"
community: "Date Range Component Module"
tags:
  - graphify/rationale
  - graphify/EXTRACTED
  - community/Date_Range_Component_Module
---

# getInstructModel (instruct model with fallback)

## Connections
- [[ModelInstructAi (kolom nvarchar(100) NULL)]] - `shares_data_with` [EXTRACTED]
- [[getAssistantModel (model dispatch by mode)]] - `references` [EXTRACTED]
- [[getPrimaryModel (ModelAi then provider config default)]] - `references` [EXTRACTED]

#graphify/rationale #graphify/EXTRACTED #community/Date_Range_Component_Module