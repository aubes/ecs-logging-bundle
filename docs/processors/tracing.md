# TracingProcessor

Maps your tracing data to ECS fields automatically, whether you use OpenTelemetry or pass tracing IDs manually.

Converts tracing data in the log context to ECS `trace.id`, `transaction.id`, and `span.id`.

## Configuration

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    processor:
        tracing:
            enabled: true
            mode: 'default'          # 'default' or 'opentelemetry'
            field_name: 'tracing'    # context key to read from (default mode only)

            #handlers: ['ecs']
            #channels: ['app']
```

## Default mode

In `default` mode, the processor reads a nested array from `context[field_name]` and maps it to ECS tracing fields.

### Usage

Without the processor:

```php
use Elastic\Types\Tracing;

$logger->info('message', [
    'tracing' => new Tracing($traceId, $transactionId),
]);
```

With the processor:

```php
$logger->info('message', [
    'tracing' => [
        'trace_id'       => $traceId,        // required
        'transaction_id' => $transactionId,  // optional
        'span_id'        => $spanId,         // optional
    ],
]);
```

ECS output:

```json
{
    "trace": { "id": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4" },
    "transaction": { "id": "f6e5d4c3b2a1f6e5" },
    "span": { "id": "f6e5d4c3b2a1f6e5" }
}
```

`trace_id` is required. `transaction_id` and `span_id` are optional.

## OpenTelemetry mode

In `opentelemetry` mode, the processor reads flat `trace_id`, `span_id`, and `trace_flags` keys from the log context (injected by the OpenTelemetry Monolog handler) and maps them to ECS fields. The `field_name` option is ignored.

This mode is designed to work with:
- [`open-telemetry/opentelemetry-auto-symfony`](https://github.com/opentelemetry-php/contrib-auto-symfony) with `OTEL_PHP_PSR3_MODE=inject`
- Any OpenTelemetry setup that injects flat tracing keys into Monolog context

### Configuration

```yaml
ecs_logging:
    processor:
        tracing:
            enabled: true
            mode: 'opentelemetry'
```

No additional dependency is required: the processor reads from keys already present in the log context. The flat OTel keys (`trace_id`, `span_id`, `trace_flags`) are cleaned up automatically.

### ECS output

| Context key | ECS field |
|---|---|
| `trace_id` | `trace.id` |
| `span_id` | `transaction.id`, `span.id` |
| `trace_flags` | removed (not an ECS field) |

```json
{
    "trace": { "id": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4" },
    "transaction": { "id": "f6e5d4c3b2a1f6e5" },
    "span": { "id": "f6e5d4c3b2a1f6e5" }
}
```
