---
type: community
cohesion: 0.29
members: 7
---

# Proposal Module

**Cohesion:** 0.29 - loosely connected
**Members:** 7 nodes

## Members
- [[CatatanInternalTest]] - document - openspec/changes/fix-nama-pembuat-catatan-internal/tasks.md
- [[Kompatibilitas Dua Kunci Array (DibuatOlehNama & NamaPembuat)]] - rationale - openspec/changes/fix-nama-pembuat-catatan-internal/proposal.md
- [[Requirement Atribusi Pembuat Catatan Internal]] - document - openspec/changes/fix-nama-pembuat-catatan-internal/specs/vpoint-care/spec.md
- [[Requirement Shared WahaChatHelper]] - document - openspec/changes/scalability-optimization-and-chatbot/specs/vpoint-care/spec.md
- [[Resolusi Nama Pembuat via Satu Query (Anti N+1)]] - rationale - openspec/changes/fix-nama-pembuat-catatan-internal/proposal.md
- [[Shared WahaChatHelper_1]] - rationale - openspec/changes/scalability-optimization-and-chatbot/proposal.md
- [[Trait ResolvesCatatanInternal]] - rationale - openspec/changes/fix-nama-pembuat-catatan-internal/proposal.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Proposal_Module
SORT file.name ASC
```
