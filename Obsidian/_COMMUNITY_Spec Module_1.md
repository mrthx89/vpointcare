---
type: community
cohesion: 0.25
members: 8
---

# Spec Module

**Cohesion:** 0.25 - loosely connected
**Members:** 8 nodes

## Members
- [[Allowed Email Domain Whitelist]] - concept - openspec/changes/add-google-sso-auth/proposal.md
- [[Core Domains of VPoint Care]] - concept - openspec/project.md
- [[Requirement Admin Authentication]] - document - openspec/specs/vpoint-care/spec.md
- [[Requirement Google Login]] - document - openspec/changes/add-google-sso-auth/specs/vpoint-care/spec.md
- [[Requirement Ticketing (Base Spec)]] - document - openspec/specs/vpoint-care/spec.md
- [[Requirement Ticketing (Expanded Delta)]] - document - openspec/changes/add-task-and-ticketing-module/specs/vpoint-care/spec.md
- [[SLA Overdue Calculation (BatasSlaMenit)]] - concept - openspec/changes/add-task-and-ticketing-module/proposal.md
- [[VPoint Care  WACS Project]] - document - openspec/project.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Spec_Module
SORT file.name ASC
```
