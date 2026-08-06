---
type: community
cohesion: 0.17
members: 13
---

# Design Module

**Cohesion:** 0.17 - loosely connected
**Members:** 13 nodes

## Members
- [[AiAutoReplyService_1]] - concept - .kiro/specs/ai-model-instruct-and-ui-improvements/requirements.md
- [[Dua Jalur Model AI (Model Utama vs Model Instruct)]] - rationale - docs/PLAN_AI_INSTRUCT_MODEL.md
- [[InternalChatbotService_1]] - concept - .kiro/specs/ai-model-instruct-and-ui-improvements/requirements.md
- [[Isolasi Model — AiAutoReplyService tidak berubah]] - rationale - .kiro/specs/ai-model-instruct-and-ui-improvements/requirements.md
- [[Mode Ringan (light)]] - concept - .kiro/specs/ai-model-instruct-and-ui-improvements/requirements.md
- [[ModelInstructAi (kolom nvarchar(100) NULL)]] - concept - .kiro/specs/ai-model-instruct-and-ui-improvements/requirements.md
- [[Suggested Replies]] - concept - .kiro/specs/ai-model-instruct-and-ui-improvements/requirements.md
- [[Suggested Replies Lifecycle]] - rationale - .kiro/specs/ai-model-instruct-and-ui-improvements/design.md
- [[TChatbotInternal (riwayat chatbot internal)]] - concept - docs/PLAN_SCALABILITY_OPTIMIZATION_CHATBOT.md
- [[Test Koneksi AI dari halaman AI Agent]] - concept - docs/PLAN_TAMBAH_9ROUTER_AI_AGENT.md
- [[VPoint Assistant (Internal Chatbot, Fase B)]] - concept - docs/PLAN_SCALABILITY_OPTIMIZATION_CHATBOT.md
- [[VPoint Assistant (halaman chatbot internal)]] - concept - .kiro/specs/ai-model-instruct-and-ui-improvements/requirements.md
- [[loadHistory() suggestedReplies reset fix]] - rationale - .kiro/specs/ai-model-instruct-and-ui-improvements/design.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Design_Module
SORT file.name ASC
```

## Connections to other communities
- 2 edges to [[_COMMUNITY_Date Range Component Module_5]]
- 1 edge to [[_COMMUNITY_File Upload Module_1]]
- 1 edge to [[_COMMUNITY_Select Module_9]]

## Top bridge nodes
- [[ModelInstructAi (kolom nvarchar(100) NULL)]] - degree 5, connects to 2 communities
- [[Dua Jalur Model AI (Model Utama vs Model Instruct)]] - degree 3, connects to 1 community