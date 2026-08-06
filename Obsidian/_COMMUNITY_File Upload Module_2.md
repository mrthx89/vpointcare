---
type: community
cohesion: 0.25
members: 8
---

# File Upload Module

**Cohesion:** 0.25 - loosely connected
**Members:** 8 nodes

## Members
- [[Model Utama (ModelAi Primary Model)]] - concept - openspec/changes/add-ai-instruct-model/proposal.md
- [[Modified Requirement AI Agent Settings (9Router)]] - document - openspec/changes/add-9router-ai-agent/specs/vpoint-care/spec.md
- [[No-Regression Guard for Auto-Reply Model]] - rationale - openspec/changes/add-ai-instruct-model/tasks.md
- [[Requirement AI Agent Settings (Base Spec)]] - document - openspec/specs/vpoint-care/spec.md
- [[Requirement AI Connection Test Dialog]] - document - openspec/changes/add-9router-ai-agent/specs/vpoint-care/spec.md
- [[Requirement Auto-Reply uses Primary Model Only]] - document - openspec/changes/add-model-instruct/specs/vpoint-care/spec.md
- [[Requirement Auto-reply tetap memakai Model Utama]] - document - openspec/changes/add-ai-instruct-model/specs/vpoint-care/spec.md
- [[Test Koneksi AI (Provider Connection Test)]] - concept - openspec/changes/add-9router-ai-agent/proposal.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/File_Upload_Module
SORT file.name ASC
```

## Connections to other communities
- 1 edge to [[_COMMUNITY_Requirements Module]]
- 1 edge to [[_COMMUNITY_Schemas Module]]
- 1 edge to [[_COMMUNITY_Readme Module]]

## Top bridge nodes
- [[Requirement AI Connection Test Dialog]] - degree 3, connects to 1 community
- [[Requirement AI Agent Settings (Base Spec)]] - degree 3, connects to 1 community
- [[Model Utama (ModelAi Primary Model)]] - degree 2, connects to 1 community