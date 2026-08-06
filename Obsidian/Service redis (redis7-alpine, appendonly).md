---
source_file: "src/docker-compose.yml"
type: "code"
community: "Chart Module"
location: "services.redis"
tags:
  - graphify/code
  - graphify/EXTRACTED
  - community/Chart_Module
---

# Service redis (redis:7-alpine, appendonly)

## Connections
- [[Requirement Redis sebagai Cache & Queue Driver]] - `implements` [INFERRED]
- [[Service app (vpointcare-php85)]] - `shares_data_with` [EXTRACTED]
- [[Service queue-ai (ai-replies)]] - `shares_data_with` [EXTRACTED]
- [[Service queue-webhooks]] - `shares_data_with` [EXTRACTED]

#graphify/code #graphify/EXTRACTED #community/Chart_Module