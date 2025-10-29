---
title: Cache Generation Guide
description: Manual cache generation and warming for production deployments
sidebar:
  order: 4
---

Data Helpers uses a persistent cache to store metadata about your DTOs. This cache dramatically improves performance by eliminating reflection overhead on subsequent requests.

:::tip[Coming Soon]
This guide is currently being written. In the meantime, check out the [Cache Warming](/data-helpers/performance/cache-warming/) documentation for basic cache management.
:::

## Quick Reference

### Warm Cache

```bash
# Warm cache for all DTOs in a directory
php bin/warm-cache.php src/Dtos

# Warm cache for specific directory
php bin/warm-cache.php app/DataTransferObjects

# Using Taskfile
task dev:cache:warmup
```

### Clear Cache

```bash
# Clear all cache
php bin/clear-cache.php

# Using Taskfile
task dev:cache:clear
```

## Production Deployment

For production deployments with **MANUAL** cache invalidation mode:

```bash
#!/bin/bash
# deploy.sh

# Clear old cache
php bin/clear-cache.php

# Warm cache for all DTOs
php bin/warm-cache.php src/Dtos

# Deploy application
# ...
```

## See Also

- [SimpleDto Caching](/data-helpers/simple-dto/caching/) - Cache invalidation strategies
- [Cache Warming](/data-helpers/performance/cache-warming/) - Detailed cache warming documentation
- [Configuration](/data-helpers/getting-started/configuration/) - Cache configuration options

