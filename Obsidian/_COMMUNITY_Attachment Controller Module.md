---
type: community
cohesion: 0.13
members: 29
---

# Attachment Controller Module

**Cohesion:** 0.13 - loosely connected
**Members:** 29 nodes

## Members
- [[dot-__construct()_7]] - code - src/app/Services/Ai/AiAutoReplyService.php
- [[dot-__construct()_10]] - code - src/app/Services/Waha/WahaWebhookProcessor.php
- [[dot-encodeWahaPathId()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-firstPhoneContactId()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-getContactProfilePictureUrl()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-getJson()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-getPhoneNumberByLid()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-isCircuitOpen()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-latestIncomingWahaChatId()_2]] - code - src/app/Support/WahaChatHelper.php
- [[dot-markCircuitBreakerLog()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-normalizeChatId()]] - code - src/app/Support/WahaChatHelper.php
- [[dot-normalizeContactId()]] - code - src/app/Support/WahaChatHelper.php
- [[dot-normalizePhoneNumber()_1]] - code - src/app/Support/WahaChatHelper.php
- [[dot-postJson()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-recordCircuitResult()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-recordWahaFailure()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-recordWahaSuccess()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-resolveLidPhoneNumber()_1]] - code - src/app/Support/WahaChatHelper.php
- [[dot-sendMedia()]] - code - src/app/Services/Waha/WahaSender.php
- [[dot-sendText()]] - code - src/app/Services/Waha/WahaSender.php
- [[AiAutoReplyService.php]] - code - src/app/Services/Ai/AiAutoReplyService.php
- [[ChatBelumTerbalasNotifier.php]] - code - src/app/Services/Ai/ChatBelumTerbalasNotifier.php
- [[ChatInitiationService.php]] - code - src/app/Services/Chat/ChatInitiationService.php
- [[SchemaCache.php]] - code - src/app/Support/SchemaCache.php
- [[WahaChatHelper]] - code - src/app/Support/WahaChatHelper.php
- [[WahaChatHelper.php]] - code - src/app/Support/WahaChatHelper.php
- [[WahaSender]] - code - src/app/Services/Waha/WahaSender.php
- [[WahaSender.php]] - code - src/app/Services/Waha/WahaSender.php
- [[WahaWebhookProcessor.php]] - code - src/app/Services/Waha/WahaWebhookProcessor.php

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Attachment_Controller_Module
SORT file.name ASC
```

## Connections to other communities
- 6 edges to [[_COMMUNITY_Locale Formatter Module]]
- 5 edges to [[_COMMUNITY_Kategori Ticket Resource Module]]
- 4 edges to [[_COMMUNITY_Dashboard Module]]
- 4 edges to [[_COMMUNITY_Ai Auto Reply Service Module]]
- 4 edges to [[_COMMUNITY_Rich Editor Module_8]]
- 3 edges to [[_COMMUNITY_Select Module_4]]
- 3 edges to [[_COMMUNITY_Status Task Resource Module]]
- 2 edges to [[_COMMUNITY_Rich Editor Module_7]]
- 1 edge to [[_COMMUNITY_Inbox Whatsapp Blade Module]]
- 1 edge to [[_COMMUNITY_Pengguna Resource Module]]
- 1 edge to [[_COMMUNITY_Anggota Grup Whatsapp Module]]

## Top bridge nodes
- [[WahaSender]] - degree 32, connects to 6 communities
- [[AiAutoReplyService.php]] - degree 8, connects to 5 communities
- [[SchemaCache.php]] - degree 8, connects to 4 communities
- [[ChatInitiationService.php]] - degree 5, connects to 3 communities
- [[WahaChatHelper]] - degree 13, connects to 2 communities