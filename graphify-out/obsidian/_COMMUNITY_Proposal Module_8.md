---
type: community
cohesion: 0.29
members: 7
---

# Proposal Module

**Cohesion:** 0.29 - loosely connected
**Members:** 7 nodes

## Members
- [[ComposerConfigdisableProcessTimeout]] - concept - src/composer.json
- [[dev]] - code - src/composer.json
- [[laragon]] - code - src/composer.json
- [[npx concurrently -c 93c5fd,c4b5fd,86efac php artisan serve php artisan queuelisten --tries=1 --timeout=0 php artisan reverbstart --names=server,queue,reverb --kill-others]] - concept - src/composer.json
- [[npx concurrently -c 93c5fd,c4b5fd,fdba74,86efac php artisan servevpoint php artisan queuelisten --tries=1 --timeout=0 npm run dev php artisan reverbstart --names=server,queue,vite,reverb --kill-others]] - concept - src/composer.json
- [[npx concurrently -c c4b5fd,86efac php artisan queuelisten --tries=1 --timeout=0 php artisan reverbstart --names=queue,reverb --kill-others]] - concept - src/composer.json
- [[start]] - code - src/composer.json

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Proposal_Module
SORT file.name ASC
```

## Connections to other communities
- 3 edges to [[_COMMUNITY_Composer Module]]

## Top bridge nodes
- [[dev]] - degree 3, connects to 1 community
- [[laragon]] - degree 3, connects to 1 community
- [[start]] - degree 3, connects to 1 community