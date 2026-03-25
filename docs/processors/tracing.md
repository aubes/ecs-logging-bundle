# TracingProcessor

Converts a tracing array in the log context to ECS `trace.id`, `transaction.id`, and `span.id`.

## Configuration

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    processor:
        tracing:
            enabled: true
            field_name: 'tracing'   # context key to read from (default: 'tracing')

            #handlers: ['ecs']
            #channels: ['app']
```

## Usage

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
        'trace_id'       => $traceId,        // required → ECS trace.id
        'transaction_id' => $transactionId,  // optional → ECS transaction.id
        'span_id'        => $spanId,         // optional → ECS span.id
    ],
]);
```

## ECS output

| Input key | ECS field | Format |
|---|---|---|
| `trace_id` | `trace.id` | 32-char hex string |
| `transaction_id` | `transaction.id` | 16-char hex string |
| `span_id` | `span.id` | 16-char hex string |

`trace_id` is required. `transaction_id` and `span_id` are optional.
