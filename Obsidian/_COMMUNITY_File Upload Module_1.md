---
type: community
cohesion: 0.18
members: 11
---

# File Upload Module

**Cohesion:** 0.18 - loosely connected
**Members:** 11 nodes

## Members
- [[Database dan Data Safety]] - rationale - AGENTS.md
- [[ExternalAuthService_1]] - concept - docs/PLAN_LOGIN_REGISTER_GOOGLE_SSO.md
- [[Filament Panel Builder sebagai admin panel]] - rationale - docs/PLAN_APLIKASI_WEBAPPS_CS_WHATSAPP.md
- [[Kebijakan pending approval untuk user eksternal baru]] - rationale - docs/PLAN_LOGIN_REGISTER_GOOGLE_SSO.md
- [[Kenapa arsitektur ini (WAHA, SQL Server, Filament, Queue+Reverb)]] - rationale - docs/PRD_VPOINT_CARE_WACS.md
- [[Konvensi Schema SQL Server (MT, DATABASE_SCHEMA_WACS.sql)]] - concept - README.md
- [[Konvensi database uniqueidentifier + NEWSEQUENTIALID + audit column]] - rationale - docs/PLAN_APLIKASI_WEBAPPS_CS_WHATSAPP.md
- [[LoginRegister via Google dan SSO OIDC]] - document - docs/PLAN_LOGIN_REGISTER_GOOGLE_SSO.md
- [[PRD — VPoint Care (WACS)]] - document - docs/PRD_VPOINT_CARE_WACS.md
- [[Problem Statement tim CS VPoint]] - concept - docs/PRD_VPOINT_CARE_WACS.md
- [[Whitelist domain email perusahaan]] - rationale - docs/PLAN_LOGIN_REGISTER_GOOGLE_SSO.md

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/File_Upload_Module
SORT file.name ASC
```

## Connections to other communities
- 1 edge to [[_COMMUNITY_Design Module]]
- 1 edge to [[_COMMUNITY_Select Module_10]]
- 1 edge to [[_COMMUNITY_Select Module_9]]
- 1 edge to [[_COMMUNITY_Actions Module_1]]
- 1 edge to [[_COMMUNITY_Bug Analisis Duplicate Tchat Module]]
- 1 edge to [[_COMMUNITY_Select Module_11]]

## Top bridge nodes
- [[PRD — VPoint Care (WACS)]] - degree 8, connects to 4 communities
- [[LoginRegister via Google dan SSO OIDC]] - degree 4, connects to 1 community
- [[ExternalAuthService_1]] - degree 3, connects to 1 community