# Symfony Logging Integration

This guide explains how to configure logging for the Data Helpers package in Symfony applications.

## Installation

1. **Copy configuration files:**

```bash
cp vendor/event4u/data-helpers/recipe/config/packages/data_helpers.yaml config/packages/
cp vendor/event4u/data-helpers/recipe/config/services_logging.yaml config/services/
```

2. **Include services in your main services.yaml:**

```yaml
# config/services.yaml
imports:
    - { resource: 'services_logging.yaml' }
```

## Configuration

### Basic Configuration

The package uses environment variables for configuration. Add these to your `.env` file:

```bash
# Logging Driver
DATA_HELPERS_LOG_DRIVER=framework  # filesystem, framework, or none
DATA_HELPERS_LOG_LEVEL=info        # debug, info, warning, error, etc.

# Filesystem Logger (if using filesystem driver)
DATA_HELPERS_LOG_PATH=%kernel.logs_dir%
DATA_HELPERS_LOG_FILENAME=data-helper-Y-m-d.log
```

### Slack Integration

To enable Slack notifications:

```bash
# Enable Slack
DATA_HELPERS_SLACK_ENABLED=true
DATA_HELPERS_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
DATA_HELPERS_SLACK_CHANNEL=#data-helpers-alerts
DATA_HELPERS_SLACK_USERNAME=Data Helpers Bot
DATA_HELPERS_SLACK_LEVEL=error

# Queue for async sending (optional)
DATA_HELPERS_SLACK_QUEUE=async
```

### Symfony Messenger Integration

The package automatically uses Symfony Messenger if available. Configure your messenger:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    use_notify: true
                    check_delayed_interval: 60000
                retry_strategy:
                    max_retries: 3
                    multiplier: 2

        routing:
            # Route Slack messages to async transport
            'event4u\DataHelpers\Logging\Symfony\SendLogToSlackMessage': async
```

### Grafana Integration

#### Loki (JSON Logs)

```bash
DATA_HELPERS_GRAFANA_ENABLED=true
DATA_HELPERS_GRAFANA_FORMAT=json
```

Configure Promtail to read the log files:

```yaml
# promtail-config.yaml
scrape_configs:
  - job_name: data-helpers
    static_configs:
      - targets:
          - localhost
        labels:
          job: data-helpers
          __path__: /path/to/symfony/var/log/data-helper-*.log
```

#### Prometheus Metrics

```bash
DATA_HELPERS_PROMETHEUS_ENABLED=true
DATA_HELPERS_PROMETHEUS_FILE=%kernel.project_dir%/var/metrics/data-helpers.prom
```

Configure Prometheus to scrape the metrics file:

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'data-helpers'
    static_configs:
      - targets: ['localhost:9090']
    file_sd_configs:
      - files:
          - '/path/to/symfony/var/metrics/data-helpers.prom'
```

## Usage

### Creating a Logger Instance

```php
use event4u\DataHelpers\Logging\LoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class YourService
{
    public function __construct(
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus,
    ) {}

    public function doSomething(): void
    {
        // Create logger with Symfony integration
        $dataLogger = LoggerFactory::create(
            $this->logger,      // Symfony logger
            $this->messageBus,  // Symfony Messenger for async Slack
        );

        // Use the logger
        $dataLogger->info('Operation started');
    }
}
```

### Event Configuration

Enable/disable specific events via environment variables:

```bash
# Errors (recommended: true)
DATA_HELPERS_LOG_EVENT_MAPPING_ERROR=true
DATA_HELPERS_LOG_EVENT_EXCEPTION=true
DATA_HELPERS_LOG_EVENT_FILTER_ERROR=true
DATA_HELPERS_LOG_EVENT_EXPRESSION_ERROR=true
DATA_HELPERS_LOG_EVENT_VALIDATION_FAILURE=true

# Performance (can be noisy, use sampling)
DATA_HELPERS_LOG_EVENT_PERFORMANCE_MAPPING=false
DATA_HELPERS_LOG_EVENT_PERFORMANCE_QUERY=false
DATA_HELPERS_LOG_EVENT_PERFORMANCE_PIPELINE=false

# Success (very noisy, use low sampling)
DATA_HELPERS_LOG_EVENT_MAPPING_SUCCESS=false
DATA_HELPERS_LOG_EVENT_QUERY_SUCCESS=false

# Cache
DATA_HELPERS_LOG_EVENT_CACHE_HIT=false
DATA_HELPERS_LOG_EVENT_CACHE_MISS=false

# Data Quality
DATA_HELPERS_LOG_EVENT_MISSING_FIELD=true
DATA_HELPERS_LOG_EVENT_NULL_SKIPPED=false
DATA_HELPERS_LOG_EVENT_EMPTY_RESULT=true

# Metrics
DATA_HELPERS_LOG_EVENT_PROCESSED_RECORDS=false
```

### Sampling Configuration

Control log volume with sampling rates (0.0 - 1.0):

```bash
DATA_HELPERS_LOG_SAMPLING_ERRORS=1.0         # 100% - Log all errors
DATA_HELPERS_LOG_SAMPLING_SUCCESS=0.01       # 1% - Sample successful operations
DATA_HELPERS_LOG_SAMPLING_PERFORMANCE=0.1    # 10% - Sample performance metrics
DATA_HELPERS_LOG_SAMPLING_CACHE=0.05         # 5% - Sample cache operations
DATA_HELPERS_LOG_SAMPLING_DATA_QUALITY=1.0   # 100% - Log all data quality issues
DATA_HELPERS_LOG_SAMPLING_METRICS=0.1        # 10% - Sample metrics
```

## Queue Configuration

### Laravel Queue

If using Laravel, the package automatically uses Laravel's queue system:

```php
// In your .env
DATA_HELPERS_SLACK_QUEUE=default  # Queue name
```

### Symfony Messenger

If using Symfony, the package automatically uses Symfony Messenger:

```php
// In your .env
DATA_HELPERS_SLACK_QUEUE=async  # Transport name
```

The package detects which framework is available and uses the appropriate queue system.

## Grafana Dashboards

### Loki Dashboard

Create a Grafana dashboard with these queries:

```logql
# Error rate
sum(rate({job="data-helpers"} |= "error" [5m]))

# Performance metrics
{job="data-helpers"} | json | duration_ms > 1000

# Data quality issues
{job="data-helpers"} | json | event="data.missing_field"
```

### Prometheus Dashboard

Create a Grafana dashboard with these queries:

```promql
# Mapping performance
rate(data_helpers_mapping_duration_ms[5m])

# Error rate
rate(data_helpers_errors_total[5m])

# Cache hit ratio
rate(data_helpers_cache_hits_total[5m]) / rate(data_helpers_cache_total[5m])
```

## Troubleshooting

### Logs not appearing

1. Check driver configuration: `DATA_HELPERS_LOG_DRIVER`
2. Check log level: `DATA_HELPERS_LOG_LEVEL`
3. Check event configuration: `DATA_HELPERS_LOG_EVENT_*`
4. Check sampling rates: `DATA_HELPERS_LOG_SAMPLING_*`

### Slack messages not sending

1. Check webhook URL: `DATA_HELPERS_SLACK_WEBHOOK`
2. Check Slack is enabled: `DATA_HELPERS_SLACK_ENABLED=true`
3. Check log level: `DATA_HELPERS_SLACK_LEVEL`
4. Check Symfony Messenger is configured correctly
5. Check queue worker is running: `php bin/console messenger:consume async`

### Prometheus metrics not updating

1. Check metrics file path: `DATA_HELPERS_PROMETHEUS_FILE`
2. Check file permissions (must be writable)
3. Check Prometheus is enabled: `DATA_HELPERS_PROMETHEUS_ENABLED=true`
4. Check Prometheus scrape configuration

## Example: Complete Symfony Setup

```yaml
# config/packages/data_helpers.yaml
data_helpers:
  logging:
    driver: '%env(DATA_HELPERS_LOG_DRIVER)%'
    level: '%env(DATA_HELPERS_LOG_LEVEL)%'
    slack:
      enabled: '%env(bool:DATA_HELPERS_SLACK_ENABLED)%'
      webhook_url: '%env(DATA_HELPERS_SLACK_WEBHOOK)%'
      queue: '%env(DATA_HELPERS_SLACK_QUEUE)%'
    grafana:
      enabled: '%env(bool:DATA_HELPERS_GRAFANA_ENABLED)%'
      prometheus:
        enabled: '%env(bool:DATA_HELPERS_PROMETHEUS_ENABLED)%'
```

```bash
# .env
DATA_HELPERS_LOG_DRIVER=framework
DATA_HELPERS_LOG_LEVEL=info

DATA_HELPERS_SLACK_ENABLED=true
DATA_HELPERS_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/WEBHOOK
DATA_HELPERS_SLACK_QUEUE=async

DATA_HELPERS_GRAFANA_ENABLED=true
DATA_HELPERS_PROMETHEUS_ENABLED=true
```

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      async:
        dsn: 'doctrine://default'
    routing:
      'event4u\DataHelpers\Logging\Symfony\SendLogToSlackMessage': async
```

Start the messenger worker:

```bash
php bin/console messenger:consume async -vv
```

