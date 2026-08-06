---
source_file: ".kiro/specs/ai-model-instruct-and-ui-improvements/design.md"
type: "rationale"
community: "Date Range Component Module"
tags:
  - graphify/rationale
  - graphify/EXTRACTED
  - community/Date_Range_Component_Module
---

# getPrimaryModel (ModelAi then provider config default)

## Connections
- [[ModelAi  Model Utama]] - `shares_data_with` [EXTRACTED]
- [[getAssistantModel (model dispatch by mode)]] - `references` [EXTRACTED]
- [[getInstructModel (instruct model with fallback)]] - `references` [EXTRACTED]

#graphify/rationale #graphify/EXTRACTED #community/Date_Range_Component_Module