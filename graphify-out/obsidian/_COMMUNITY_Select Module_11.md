---
type: community
cohesion: 0.18
members: 11
---

# Select Module

**Cohesion:** 0.18 - loosely connected
**Members:** 11 nodes

## Members
- [[Circuit breaker WahaSender]] - rationale - docs/PLAN_SCALABILITY_OPTIMIZATION_CHATBOT.md
- [[FAB Buat Chat di panel Daftar Chat]] - rationale - docs/PLAN_MULAI_CHAT_INBOX_WHATSAPP.md
- [[Fitur Mulai Chat Terlebih Dahulu]] - document - docs/PLAN_MULAI_CHAT_INBOX_WHATSAPP.md
- [[Inbox WhatsApp]] - concept - README.md
- [[ProcessAiAutoReplyJob (AI reply asinkron)]] - concept - docs/PLAN_SCALABILITY_OPTIMIZATION_CHATBOT.md
- [[ProcessWebhookJob (webhook asinkron)]] - concept - docs/PLAN_SCALABILITY_OPTIMIZATION_CHATBOT.md
- [[Realtime via Laravel Reverb (channel waha-agents)]] - concept - README.md
- [[Scalability Optimization (Fase A)]] - document - docs/PLAN_SCALABILITY_OPTIMIZATION_CHATBOT.md
- [[WahaSender (pengiriman pesan keluar)]] - concept - README.md
- [[WahaWebhookProcessor_1]] - concept - docs/BUG-ANALISIS-DUPLICATE-TCHAT.md
- [[Webhook WAHA (POST webhookswaha{token})]] - concept - README.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Select_Module
SORT file.name ASC
```

## Connections to other communities
- 2 edges to [[_COMMUNITY_Select Module_9]]
- 2 edges to [[_COMMUNITY_Select Module_10]]
- 2 edges to [[_COMMUNITY_Waha Media Controller Module]]
- 1 edge to [[_COMMUNITY_File Upload Module_1]]

## Top bridge nodes
- [[Webhook WAHA (POST webhookswaha{token})]] - degree 4, connects to 2 communities
- [[Inbox WhatsApp]] - degree 5, connects to 1 community
- [[WahaWebhookProcessor_1]] - degree 3, connects to 1 community
- [[Fitur Mulai Chat Terlebih Dahulu]] - degree 3, connects to 1 community
- [[WahaSender (pengiriman pesan keluar)]] - degree 3, connects to 1 community