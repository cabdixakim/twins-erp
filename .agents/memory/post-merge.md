---
name: Post-merge setup script
description: Location and purpose of the post-merge script for this Laravel project
---

The post-merge script is at `scripts/post-merge.sh` and is configured in `.replit` with a 60-second timeout.

```bash
#!/bin/bash
set -e
php artisan migrate --force
php artisan view:clear
```

**Why:** Laravel needs migrations applied and compiled views cleared after any merge that touches database schema or Blade templates. The script is non-interactive (--force flag) and idempotent.

**How to apply:** If a merge adds new migrations or changes Blade files, this script handles it automatically. If you need to add more steps (e.g. `composer install`), add them here.
