---
type: community
cohesion: 0.20
members: 10
---

# Database Schema Wacs Module

**Cohesion:** 0.20 - loosely connected
**Members:** 10 nodes

## Members
- [[AiKnowledgeLearningService_2]] - concept - openspec/changes/add-reviewed-ai-learning/proposal.md
- [[HashKonten Content Deduplication]] - concept - openspec/changes/add-reviewed-ai-learning/proposal.md
- [[Knowledge Extraction JSON Contract]] - concept - openspec/changes/add-reviewed-ai-learning/proposal.md
- [[OAuth statenonce and Redirect Validation]] - concept - openspec/changes/add-google-sso-auth/tasks.md
- [[PII and Sensitive Data Sanitization]] - concept - openspec/changes/add-reviewed-ai-learning/proposal.md
- [[Requirement Secret Handling]] - document - openspec/specs/vpoint-care/spec.md
- [[Requirement Secure External Auth Defaults]] - document - openspec/changes/add-google-sso-auth/specs/vpoint-care/spec.md
- [[Requirement Sensitive Data Sanitization]] - document - openspec/changes/add-reviewed-ai-learning/specs/vpoint-care/spec.md
- [[TCKTSK Daily Sequence Number Format]] - concept - openspec/changes/add-task-and-ticketing-module/proposal.md
- [[createDraftFromChat()]] - concept - openspec/changes/add-reviewed-ai-learning/proposal.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Database_Schema_Wacs_Module
SORT file.name ASC
```

## Connections to other communities
- 1 edge to [[_COMMUNITY_Date Range Component Module_6]]
- 1 edge to [[_COMMUNITY_Proposal Module_9]]
- 1 edge to [[_COMMUNITY_Example Test Module]]

## Top bridge nodes
- [[Requirement Secret Handling]] - degree 3, connects to 1 community
- [[AiKnowledgeLearningService_2]] - degree 2, connects to 1 community
- [[TCKTSK Daily Sequence Number Format]] - degree 2, connects to 1 community