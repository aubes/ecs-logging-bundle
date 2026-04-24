# ECS Logging Bundle

![CI](https://github.com/aubes/ecs-logging-bundle/actions/workflows/php.yml/badge.svg)
[![Latest Stable Version](https://img.shields.io/packagist/v/aubes/ecs-logging-bundle)](https://packagist.org/packages/aubes/ecs-logging-bundle)
[![License](https://img.shields.io/packagist/l/aubes/ecs-logging-bundle)](https://packagist.org/packages/aubes/ecs-logging-bundle)
[![PHP Version](https://img.shields.io/packagist/dependency-v/aubes/ecs-logging-bundle/php)](https://packagist.org/packages/aubes/ecs-logging-bundle)

A Symfony bundle that formats Monolog logs as [Elastic Common Schema (ECS)](https://www.elastic.co/guide/en/ecs/current/ecs-reference.html) NDJSON, ready to be ingested by Elasticsearch and visualised in Kibana without any index mapping configuration.

Built on top of [elastic/ecs-logging](https://github.com/elastic/ecs-logging-php).

### What's included

| Component | Description |
|---|---|
| **`EcsFormatter`** | Produces ECS-compliant NDJSON (`log.level` lowercase, `ecs.version` and `tags` configurable) |
| **`ServiceProcessor`** | Injects static `service.*` metadata (name, version, id…) into every record |
| **`ErrorProcessor`** | Converts a `\Throwable` in context to ECS `error.*` fields. `map_exception_key` also catches Symfony's native exceptions |
| **`TracingProcessor`** | Maps tracing data to ECS `trace.id`, `transaction.id`, `span.id` (supports manual arrays and OpenTelemetry flat keys) |
| **`CorrelationIdProcessor`** | Maps a correlation ID from Monolog `extra` to ECS `labels.correlation_id` or `trace.id` |
| **`UserProcessor`** | Injects the authenticated user as ECS `user.*` via a customisable provider |
| **`HttpRequestProcessor`** | Injects ECS `http.*`, `url.*`, and optionally `client.ip` from the current request |
| **`HostProcessor`** | Injects static ECS `host.*` fields resolved once at boot time |
| **`AutoLabelProcessor`** | Removes non-ECS context keys to protect the ECS namespace, optionally moving them into `labels` |

### Notable defaults

- **Sensitive fields opt-in** — `client.ip`, `url.query`, `http.request.referrer`, and `user.*` (PII — see [UserProcessor](docs/processors/user.md)) are disabled by default
- **FrankenPHP worker mode** — stateful processors implement `ResetInterface`
- **ECS namespace protection** — `AutoLabelProcessor` prevents non-ECS fields from polluting root-level keys
- **ECS 8.x and 9.x** — `ecs.version` defaults to `9.3.0`, configurable per deployment

### Output example

```json
{
    "@timestamp": "2025-03-21T10:00:00.000000+00:00",
    "message": "Payment failed",
    "ecs.version": "9.3.0",
    "log": {
        "level": "error",
        "logger": "app"
    },
    "service": {
        "name": "checkout",
        "version": "1.4.2"
    },
    "error": {
        "type": "RuntimeException",
        "message": "Gateway timeout",
        "code": "504"
    },
    "trace": {
      "id": "123abc123abc123abc123abc123abc12"
    },
    "user": {
      "name": "alice"
    },
    "http": {
        "request": {
            "method": "POST",
            "mime_type": "application/json"
        },
        "version": "1.1"
    },
    "url": {
        "path": "/checkout/pay",
        "scheme": "https",
        "domain": "shop.example.com"
    }
}
```

### Compatibility

- PHP >= 8.2
- Symfony 6.4 | 7.4 | 8.0 — LTS versions only
- Monolog 3.x
- FrankenPHP (worker mode)
- ECS 8.x and 9.x

## Installation

```shell
composer require aubes/ecs-logging-bundle
```

## Quick start

**1. Configure the formatter in Monolog:**

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: info
            formatter: 'monolog.formatter.ecs'
```

**2. Enable the bundle and configure at least one processor:**

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    monolog:
        handlers: ['main']

    processor:
        service:
            enabled: true
            name: 'my-app'
            version: '%env(string:APP_VERSION)%'
```

## Documentation

- [Configuration reference](docs/configuration-reference.md)
- [Logging a Symfony app in ECS format](docs/symfony-logs.md)
- [Advanced example](docs/advanced-example.md)
- Processors
  - [ServiceProcessor](docs/processors/service.md)
  - [ErrorProcessor](docs/processors/error.md)
  - [TracingProcessor](docs/processors/tracing.md)
  - [CorrelationIdProcessor](docs/processors/correlation-id.md)
  - [UserProcessor](docs/processors/user.md)
  - [AutoLabelProcessor](docs/processors/auto-label.md)
  - [HttpRequestProcessor](docs/processors/http-request.md)
  - [HostProcessor](docs/processors/host.md)
