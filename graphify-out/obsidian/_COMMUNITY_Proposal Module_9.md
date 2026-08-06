---
type: community
cohesion: 0.40
members: 6
---

# Proposal Module

**Cohesion:** 0.40 - moderately connected
**Members:** 6 nodes

## Members
- [[Assignment History (TTicketDPenugasan  TTaskDPenugasan)]] - concept - openspec/changes/add-task-and-ticketing-module/proposal.md
- [[TaskNumberService]] - concept - openspec/changes/add-task-and-ticketing-module/tasks.md
- [[TaskService]] - concept - openspec/changes/add-task-and-ticketing-module/tasks.md
- [[TicketAssignedNotification  TaskAssignedNotification]] - concept - openspec/changes/add-task-and-ticketing-module/proposal.md
- [[TicketNumberService]] - concept - openspec/changes/add-task-and-ticketing-module/tasks.md
- [[TicketService]] - concept - openspec/changes/add-task-and-ticketing-module/tasks.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Proposal_Module
SORT file.name ASC
```

## Connections to other communities
- 1 edge to [[_COMMUNITY_Database Schema Wacs Module]]

## Top bridge nodes
- [[TicketNumberService]] - degree 3, connects to 1 community