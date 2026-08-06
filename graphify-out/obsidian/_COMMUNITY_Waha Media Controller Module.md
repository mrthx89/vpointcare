---
type: community
cohesion: 0.15
members: 14
---

# Waha Media Controller Module

**Cohesion:** 0.15 - loosely connected
**Members:** 14 nodes

## Members
- [[Bug Duplicate TChat]] - document - docs/BUG-ANALISIS-DUPLICATE-TCHAT.md
- [[ChatInitiationService_1]] - concept - docs/BUG-ANALISIS-DUPLICATE-TCHAT.md
- [[ChatLookupService (usulan shared lookup)]] - rationale - docs/BUG-ANALISIS-DUPLICATE-TCHAT.md
- [[Indeks komposit satisfaction.score]] - rationale - docs/PRD_VPOINT_CARE_WACS.md
- [[Metrik Dashboard VPoint Care]] - concept - docs/PRD_VPOINT_CARE_WACS.md
- [[Minimalisme Implementasi]] - rationale - AGENTS.md
- [[Race condition guard (cache lock  filtered unique index)]] - rationale - docs/BUG-ANALISIS-DUPLICATE-TCHAT.md
- [[Refactor Nama Tabel Chat dan Ticket (T..T..D..)]] - document - docs/PLAN_REFACTOR_TABEL_CHAT_TICKET.md
- [[Rename TChatM - TChat dan kolom IdChatM - IdChat]] - concept - docs/PLAN_REFACTOR_TABEL_CHAT_TICKET.md
- [[Rename TTicketM - TTicket dan turunan TTicketD]] - concept - docs/PLAN_REFACTOR_TABEL_CHAT_TICKET.md
- [[Reuse sesi aktif alih-alih membuat TChat baru]] - rationale - docs/PLAN_MULAI_CHAT_INBOX_WHATSAPP.md
- [[Shared WahaChatHelper]] - concept - docs/PLAN_SCALABILITY_OPTIMIZATION_CHATBOT.md
- [[TChat (header sesi chat)]] - concept - docs/BUG-ANALISIS-DUPLICATE-TCHAT.md
- [[findOrCreateChat()]] - concept - docs/BUG-ANALISIS-DUPLICATE-TCHAT.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Waha_Media_Controller_Module
SORT file.name ASC
```

## Connections to other communities
- 2 edges to [[_COMMUNITY_Select Module_11]]
- 1 edge to [[_COMMUNITY_Select Module_9]]
- 1 edge to [[_COMMUNITY_Bug Analisis Duplicate Tchat Module]]

## Top bridge nodes
- [[Bug Duplicate TChat]] - degree 5, connects to 1 community
- [[findOrCreateChat()]] - degree 4, connects to 1 community
- [[ChatInitiationService_1]] - degree 3, connects to 1 community
- [[Rename TTicketM - TTicket dan turunan TTicketD]] - degree 2, connects to 1 community