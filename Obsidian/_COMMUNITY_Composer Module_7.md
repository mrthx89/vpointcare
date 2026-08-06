---
type: community
cohesion: 0.50
members: 4
---

# Composer Module

**Cohesion:** 0.50 - moderately connected
**Members:** 4 nodes

## Members
- [[@php -r file_exists('databasedatabase.sqlite')  touch('databasedatabase.sqlite');]] - concept - src/composer.json
- [[@php artisan keygenerate --ansi]] - concept - src/composer.json
- [[@php artisan migrate --graceful --ansi]] - concept - src/composer.json
- [[post-create-project-cmd]] - code - src/composer.json

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Composer_Module
SORT file.name ASC
```

## Connections to other communities
- 1 edge to [[_COMMUNITY_Composer Module]]

## Top bridge nodes
- [[post-create-project-cmd]] - degree 4, connects to 1 community