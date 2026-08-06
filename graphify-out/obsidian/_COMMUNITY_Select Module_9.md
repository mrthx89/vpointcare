---
type: community
cohesion: 0.20
members: 12
---

# Select Module

**Cohesion:** 0.20 - loosely connected
**Members:** 12 nodes

## Members
- [[AI Agent (auto-reply dan draft lokal)]] - concept - README.md
- [[Aturan Teknis WACS]] - rationale - AGENTS.md
- [[Keamanan Operasional (secret handling, HTTPS, backup)]] - rationale - README.md
- [[Kegagalan resolve @lid ke nomor telepon]] - rationale - docs/BUG-ANALISIS-DUPLICATE-TCHAT.md
- [[MPengetahuan (Knowledge Base AI)]] - concept - docs/PLAN_AI_LEARNING_DARI_CHAT_CUSTOMER.md
- [[MPengguna (sumber autentikasi internal)]] - concept - README.md
- [[Master Data (M tables)]] - concept - README.md
- [[Penamaan env NINEROUTER_ (hindari nama diawali angka)]] - rationale - docs/PLAN_TAMBAH_9ROUTER_AI_AGENT.md
- [[Penghapusan tabel users dan AppModelsUser]] - rationale - docs/PLAN_REFACTOR_AUTH_MPENGGUNA_HAPUS_USERS.md
- [[Provider 9Router pada AI Agent]] - document - docs/PLAN_TAMBAH_9ROUTER_AI_AGENT.md
- [[Refactor Auth ke MPengguna]] - document - docs/PLAN_REFACTOR_AUTH_MPENGGUNA_HAPUS_USERS.md
- [[Vector search  embedding opsional (Fase 4)]] - rationale - docs/PLAN_AI_LEARNING_DARI_CHAT_CUSTOMER.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Select_Module
SORT file.name ASC
```

## Connections to other communities
- 2 edges to [[_COMMUNITY_Bug Analisis Duplicate Tchat Module]]
- 2 edges to [[_COMMUNITY_Select Module_11]]
- 1 edge to [[_COMMUNITY_Date Range Component Module_5]]
- 1 edge to [[_COMMUNITY_Select Module_10]]
- 1 edge to [[_COMMUNITY_File Upload Module_1]]
- 1 edge to [[_COMMUNITY_Waha Media Controller Module]]
- 1 edge to [[_COMMUNITY_Design Module]]
- 1 edge to [[_COMMUNITY_Actions Module_1]]

## Top bridge nodes
- [[AI Agent (auto-reply dan draft lokal)]] - degree 6, connects to 3 communities
- [[Aturan Teknis WACS]] - degree 6, connects to 2 communities
- [[MPengguna (sumber autentikasi internal)]] - degree 5, connects to 2 communities
- [[MPengetahuan (Knowledge Base AI)]] - degree 4, connects to 1 community
- [[Provider 9Router pada AI Agent]] - degree 3, connects to 1 community