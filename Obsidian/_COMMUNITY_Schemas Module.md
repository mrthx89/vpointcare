---
type: community
cohesion: 0.25
members: 8
---

# Schemas Module

**Cohesion:** 0.25 - loosely connected
**Members:** 8 nodes

## Members
- [[Cost and Latency Rationale for Split Models]] - rationale - openspec/changes/add-model-instruct/proposal.md
- [[Inbox WhatsApp First-Session Model Rule]] - rationale - openspec/changes/add-ai-instruct-model/proposal.md
- [[Model Instruct (ModelInstructAi)]] - concept - openspec/changes/add-ai-instruct-model/proposal.md
- [[ModelInstructAi Column (add-model-instruct)]] - concept - openspec/changes/add-model-instruct/proposal.md
- [[Requirement Jawaban pertama Inbox WhatsApp memakai Model Instruct]] - document - openspec/changes/add-ai-instruct-model/specs/vpoint-care/spec.md
- [[Requirement Model Instruct Column]] - document - openspec/changes/add-model-instruct/specs/vpoint-care/spec.md
- [[Requirement Pengaturan AI menyediakan Model Instruct]] - document - openspec/changes/add-ai-instruct-model/specs/vpoint-care/spec.md
- [[SchemahasColumn() Fail-Safe Guard]] - rationale - openspec/changes/add-model-instruct/proposal.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Schemas_Module
SORT file.name ASC
```

## Connections to other communities
- 1 edge to [[_COMMUNITY_Readme Module_1]]
- 1 edge to [[_COMMUNITY_File Upload Module_2]]
- 1 edge to [[_COMMUNITY_Example Test Module]]

## Top bridge nodes
- [[Model Instruct (ModelInstructAi)]] - degree 5, connects to 2 communities
- [[Requirement Pengaturan AI menyediakan Model Instruct]] - degree 2, connects to 1 community