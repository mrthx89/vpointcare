---
type: community
cohesion: 0.40
members: 6
---

# Example Test Module

**Cohesion:** 0.40 - moderately connected
**Members:** 6 nodes

## Members
- [[Development Rules (RouteSQL ServerSecret Constraints)]] - rationale - openspec/project.md
- [[Google and SSO External Login]] - concept - openspec/changes/add-google-sso-auth/proposal.md
- [[Idempotent SQL Server Migration Pattern]] - rationale - openspec/changes/add-task-and-ticketing-module/tasks.md
- [[MPengaturanAi.ModelInstructAi Conditional Migration]] - concept - openspec/changes/add-ai-instruct-model/tasks.md
- [[MPengguna as Internal User Source of Truth]] - rationale - openspec/changes/add-google-sso-auth/proposal.md
- [[Requirement SQL Server Compatibility]] - document - openspec/specs/vpoint-care/spec.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Example_Test_Module
SORT file.name ASC
```

## Connections to other communities
- 1 edge to [[_COMMUNITY_Schemas Module]]
- 1 edge to [[_COMMUNITY_Readme Module]]
- 1 edge to [[_COMMUNITY_Database Schema Wacs Module]]

## Top bridge nodes
- [[Development Rules (RouteSQL ServerSecret Constraints)]] - degree 4, connects to 2 communities
- [[MPengaturanAi.ModelInstructAi Conditional Migration]] - degree 3, connects to 1 community