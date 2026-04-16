# CorrelationIdProcessor

Lightweight request correlation without the full OpenTelemetry stack. Your correlation ID flows into ECS logs automatically.

Reads a correlation ID from Monolog `extra` and writes it to an ECS-compliant field in the log context. Works with any library that populates `extra` with a correlation/request ID.

## Correlation ID vs Tracing OpenTelemetry Mode

If your application already uses OpenTelemetry for distributed tracing, you probably don't need this processor: the [Tracing processor with open opentelemetry mode](tracing.md#opentelemetry-mode) maps `trace_id` and `span_id` to ECS fields automatically.

## Configuration

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    processor:
        correlation_id:
            enabled: true

            # Key to read from Monolog extra (default: "correlation_id").
            # Must match the field name configured in the library that populates extra.
            field_name: correlation_id

            # Where to write the correlation ID:
            #   "labels" (default) -> labels.correlation_id
            #   "trace"            -> trace.id
            target: labels

            #handlers: ['ecs']
            #channels: ['app']
```

## Target options

| Value | ECS field | When to use |
|---|---|---|
| `labels` (default) | `labels.correlation_id` | When the correlation ID is a business/request-scoping value distinct from distributed tracing |
| `trace` | `trace.id` | When the correlation ID serves as the trace ID (no separate tracing system). ECS expects a 32-character lowercase hex string |

ECS output with `target: labels`:

```json
{
    "labels": {
        "correlation_id": "abc-123"
    }
}
```

ECS output with `target: trace`:

```json
{
    "trace": {
        "id": "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4"
    }
}
```

The source key is removed from `extra` to prevent it from appearing at root level in the ECS output. If the field is missing, empty, or not a string, the record is left unchanged. Existing values in the target field are never overwritten.

## Custom field name

Any Monolog processor that writes a string to `extra` can be used. Configure `field_name` to match:

```yaml
ecs_logging:
    processor:
        correlation_id:
            enabled: true
            field_name: request_id   # reads from extra.request_id
```
