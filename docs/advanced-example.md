# Advanced example

This example shows a Symfony application logging an error during a payment process, with every processor enabled (service, error, user, host, tracing, http_request, auto_label) and OpenTelemetry auto-instrumentation for distributed tracing.

## Prerequisites

- **Symfony 6.4+** with Monolog
- **`symfony/security-bundle`** installed (for `UserProcessor`)
- **[`open-telemetry/opentelemetry-auto-symfony`](https://github.com/opentelemetry-php/contrib-auto-symfony)** installed with `OTEL_PHP_PSR3_MODE=inject`, which injects flat `trace_id`/`span_id` keys into Monolog context. The `TracingProcessor` in `opentelemetry` mode reads these keys and maps them to ECS fields.

## Configuration

### `config/packages/monolog.yaml`

```yaml
monolog:
    handlers:
        app:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: info
            channels: ["app"]
            formatter: 'monolog.formatter.ecs'
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: warning
            channels: ["!event", "!app"]
            formatter: 'monolog.formatter.ecs'
```

### `config/packages/ecs_logging.yaml`

```yaml
ecs_logging:
    monolog:
        handlers: ['app', 'main']

    tags: ['env:prod', 'region:eu-west-1']

    processor:
        service:
            enabled: true
            name: 'my-app'
            version: '%env(string:APP_VERSION)%'
            type: 'payments'

        error:
            enabled: true
            map_exception_key: true    # also process context['exception']

        user:
            enabled: true

        host:
            enabled: true

        tracing:
            enabled: true
            mode: 'opentelemetry'

        http_request:
            enabled: true
            include_client_ip: true

        auto_label:
            enabled: true
            mode: 'bundle'
            move_to_labels: true
            include_extra: true        # process Monolog extras too
            non_scalar_strategy: json  # encode arrays/objects instead of dropping
```

## Triggering a log

```php
// src/Service/PaymentService.php
namespace App\Service;

use Psr\Log\LoggerInterface;

class PaymentService
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function process(string $orderId): void
    {
        try {
            // ... payment processing logic
            throw new \RuntimeException('Card declined by issuer');
        } catch (\Throwable $e) {
            $this->logger->error('Payment failed', [
                'error'    => $e,
                'order_id' => 'ORD-9876',
            ]);

            throw $e;
        }
    }
}
```

## Generated log

```json
{
    "@timestamp": "2026-03-20T14:32:01.000000+00:00",
    "message": "Payment failed",
    "ecs.version": "9.3.0",
    "log": {
        "logger": "app",
        "level": "error"
    },
    "service": {
        "name": "my-app",
        "version": "1.5.2",
        "type": "payments"
    },
    "error": {
        "type": "RuntimeException",
        "message": "Card declined by issuer",
        "code": 0,
        "stack_trace": "RuntimeException: Card declined by issuer in /app/src/Service/PaymentService.php:17\nStack trace:\n#0 /app/src/Controller/CheckoutController.php(34): App\\Service\\PaymentService->process('ORD-9876')\n#1 ..."
    },
    "trace": {
        "id": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4"
    },
    "transaction": {
        "id": "f6e5d4c3b2a1f6e5"
    },
    "span": {
        "id": "9f8e7d6c5b4a3210"
    },
    "user": {
        "name": "john.doe"
    },
    "host": {
        "name": "web-01.example.com",
        "ip": ["203.0.113.10"],
        "architecture": "x86_64"
    },
    "url": {
        "path": "/api/checkout",
        "scheme": "https",
        "domain": "example.com"
    },
    "http": {
        "request": {
            "method": "POST",
            "mime_type": "application/json"
        },
        "version": "2"
    },
    "client": {
        "ip": "198.51.100.42"
    },
    "labels": {
        "order_id": "ORD-9876"
    },
    "tags": ["env:prod", "region:eu-west-1"]
}
```
