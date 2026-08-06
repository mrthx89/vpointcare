---
type: community
cohesion: 0.31
members: 11
---

# Proposal Module

**Cohesion:** 0.31 - loosely connected
**Members:** 11 nodes

## Members
- [[DATABASE_SCHEMA_WACS.sql]] - code - src/script/DATABASE_SCHEMA_WACS.sql
- [[MNomorDokumen]] - code - src/script/DATABASE_SCHEMA_WACS.sql
- [[MPeran]] - code - src/script/DATABASE_SCHEMA_WACS.sql
- [[MPeranHakAkses]] - code - src/script/DATABASE_SCHEMA_WACS.sql
- [[MStatusTask]] - code - src/script/DATABASE_SCHEMA_WACS.sql
- [[TTask]] - code - src/script/DATABASE_SCHEMA_WACS.sql
- [[TTaskDChecklist]] - code - src/script/DATABASE_SCHEMA_WACS.sql
- [[TTaskDKomentar]] - code - src/script/DATABASE_SCHEMA_WACS.sql
- [[TTaskDLampiran]] - code - src/script/DATABASE_SCHEMA_WACS.sql
- [[TTaskDPenugasan]] - code - src/script/DATABASE_SCHEMA_WACS.sql
- [[notifications]] - code - src/script/DATABASE_SCHEMA_WACS.sql

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Proposal_Module
SORT file.name ASC
```
