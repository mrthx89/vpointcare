---
type: community
cohesion: 0.05
members: 41
---

# Chart Module

**Cohesion:** 0.05 - loosely connected
**Members:** 41 nodes

## Members
- [[AiSettings Cache untuk MPengaturanAi]] - document - openspec/changes/scalability-optimization-and-chatbot/tasks.md
- [[Asynchronous Webhook Processing]] - rationale - openspec/changes/scalability-optimization-and-chatbot/proposal.md
- [[AutoReplyModelSelectionTest]] - document - openspec/changes/fix-instruct-model-auto-reply-selection/tasks.md
- [[Bug Query ke Tabel 'Pengguna' yang Tidak Ada]] - rationale - openspec/changes/fix-nama-pembuat-catatan-internal/proposal.md
- [[Cacat 2 Flag Tidak Diteruskan ke Provider Chat-Completions]] - rationale - openspec/changes/fix-instruct-model-auto-reply-selection/proposal.md
- [[Cacat 3 Fallback  Meloloskan String Kosong]] - rationale - openspec/changes/fix-instruct-model-auto-reply-selection/proposal.md
- [[Circuit Breaker WahaSender_1]] - rationale - openspec/changes/scalability-optimization-and-chatbot/proposal.md
- [[Debounced Broadcast]] - rationale - openspec/changes/scalability-optimization-and-chatbot/proposal.md
- [[Guard Anti-Regresi Auto-Reply (closing message & test koneksi)]] - document - openspec/changes/fix-instruct-model-auto-reply-selection/tasks.md
- [[InternalChatbotService (asksearchKnowledgebuildSystemPrompt)]] - document - openspec/changes/scalability-optimization-and-chatbot/tasks.md
- [[Knowledge Base RAG dari MPengetahuan]] - concept - openspec/changes/scalability-optimization-and-chatbot/proposal.md
- [[ModelInstructAi Column & Routing]] - rationale - openspec/changes/ai-model-instruct-and-ui-improvements/proposal.md
- [[Rate Limiting Endpoint Webhook]] - rationale - openspec/changes/scalability-optimization-and-chatbot/proposal.md
- [[Redis sebagai Cache & Queue Driver]] - rationale - openspec/changes/scalability-optimization-and-chatbot/proposal.md
- [[Requirement Asynchronous Webhook Processing]] - document - openspec/changes/scalability-optimization-and-chatbot/specs/vpoint-care/spec.md
- [[Requirement Auto-Reply Model Selection (MODIFIED)]] - document - openspec/changes/fix-instruct-model-auto-reply-selection/specs/vpoint-care/spec.md
- [[Requirement Circuit Breaker WahaSender]] - document - openspec/changes/scalability-optimization-and-chatbot/specs/vpoint-care/spec.md
- [[Requirement Debounced Broadcast]] - document - openspec/changes/scalability-optimization-and-chatbot/specs/vpoint-care/spec.md
- [[Requirement Dedicated Queue Workers]] - document - openspec/changes/scalability-optimization-and-chatbot/specs/vpoint-care/spec.md
- [[Requirement Multilanguage Preservation]] - document - openspec/changes/scalability-optimization-and-chatbot/specs/vpoint-care/spec.md
- [[Requirement Penanganan Model Instruct Kosong]] - document - openspec/changes/fix-instruct-model-auto-reply-selection/specs/vpoint-care/spec.md
- [[Requirement Queue — AI Deduplication]] - document - openspec/changes/scalability-optimization-and-chatbot/specs/vpoint-care/spec.md
- [[Requirement Rate Limiting Webhook]] - document - openspec/changes/scalability-optimization-and-chatbot/specs/vpoint-care/spec.md
- [[Requirement Redis sebagai Cache & Queue Driver]] - document - openspec/changes/scalability-optimization-and-chatbot/specs/vpoint-care/spec.md
- [[Requirement Referensi Tabel Pengguna yang Benar]] - document - openspec/changes/fix-nama-pembuat-catatan-internal/specs/vpoint-care/spec.md
- [[Requirement VPoint Assistant — Internal Chatbot]] - document - openspec/changes/scalability-optimization-and-chatbot/specs/vpoint-care/spec.md
- [[Resolusi Konflik Spec Pemilihan Model Auto-Reply]] - rationale - openspec/changes/fix-instruct-model-auto-reply-selection/proposal.md
- [[Satu API Call untuk Suggested Replies]] - rationale - openspec/changes/ai-model-instruct-and-ui-improvements/proposal.md
- [[SchemaCache untuk SchemahasColumnhasTable]] - document - openspec/changes/scalability-optimization-and-chatbot/tasks.md
- [[Service app (vpointcare-php85)]] - code - src/docker-compose.yml
- [[Service queue-ai (ai-replies)]] - code - src/docker-compose.yml
- [[Service queue-broadcasts]] - code - src/docker-compose.yml
- [[Service queue-webhooks]] - code - src/docker-compose.yml
- [[Service redis (redis7-alpine, appendonly)]] - code - src/docker-compose.yml
- [[Service reverb (WebSocket, port 7060)]] - code - src/docker-compose.yml
- [[Service waha (devlikeaprowaha)]] - code - res/waha/docker-compose.yml
- [[Tabel TChatbotInternal]] - document - openspec/changes/scalability-optimization-and-chatbot/tasks.md
- [[VPoint Assistant — Internal Chatbot]] - concept - openspec/changes/scalability-optimization-and-chatbot/proposal.md
- [[WAHA Global Webhook Config (hook URL, HMAC, retries)]] - code - res/waha/docker-compose.yml
- [[property_exists() Guard pada getInstructModel()]] - rationale - openspec/changes/ai-model-instruct-and-ui-improvements/proposal.md
- [[robots.txt — Allow All Crawlers]] - document - src/public/robots.txt

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Chart_Module
SORT file.name ASC
```
