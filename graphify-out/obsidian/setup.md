---
source_file: "src/composer.json"
type: "code"
community: "Composer Module"
location: "L40"
tags:
  - graphify/code
  - graphify/EXTRACTED
  - community/Composer_Module
---

# setup

## Connections
- [[@php -r file_exists('.env')  copy('.env.example', '.env');]] - `extends` [EXTRACTED]
- [[@php artisan keygenerate]] - `extends` [EXTRACTED]
- [[@php artisan migrate --force]] - `extends` [EXTRACTED]
- [[composer install]] - `extends` [EXTRACTED]
- [[npm install --ignore-scripts]] - `extends` [EXTRACTED]
- [[npm run build]] - `extends` [EXTRACTED]
- [[scripts]] - `contains` [EXTRACTED]

#graphify/code #graphify/EXTRACTED #community/Composer_Module