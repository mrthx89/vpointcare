---
type: community
cohesion: 0.29
members: 10
---

# Support Module

**Cohesion:** 0.29 - loosely connected
**Members:** 10 nodes

## Members
- [[jsapp.js]] - code - src/resources/js/app.js
- [[jsecho.js]] - code - src/resources/js/echo.js
- [[logReverbStatus()]] - code - src/resources/js/echo.js
- [[normalizeReverbError()]] - code - src/resources/js/echo.js
- [[readReverbStatusLogs()]] - code - src/resources/js/echo.js
- [[reverbInitialSync]] - code - src/resources/js/echo.js
- [[reverbStatusMessages]] - code - src/resources/js/echo.js
- [[setWahaWsOnline()]] - code - src/resources/js/echo.js
- [[syncCurrentReverbState()]] - code - src/resources/js/echo.js
- [[writeReverbStatusLog()]] - code - src/resources/js/echo.js

## Live Query (requires Dataview plugin)

```dataview
TABLE source_file, type FROM #community/Support_Module
SORT file.name ASC
```
