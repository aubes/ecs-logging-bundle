# Logging a Symfony app in ECS format

Starting point for capturing Symfony's framework logs (routing, security, Doctrine, messenger, uncaught exceptions…) in ECS format without dropping information from the Monolog context.

This is a working baseline, not a finished recipe: adapt handler paths, log levels, channel filters and enabled processors to your own routing / retention / privacy constraints.

## Configuration

### `config/packages/monolog.yaml`

```yaml
monolog:
    handlers:
        ecs:
            type: stream
            path: "%kernel.logs_dir%/ecs.log"
            level: info
            formatter: 'monolog.formatter.ecs'
```

### `config/packages/ecs_logging.yaml`

```yaml
ecs_logging:
    monolog:
        handlers: ['ecs']

    processor:
        error:
            enabled: true
            map_exception_key: true    # capture context['exception'] from ErrorListener

        auto_label:
            enabled: true
            mode: 'bundle'
            move_to_labels: true       # keep non-ECS keys in labels instead of dropping
            include_extra: true        # process Monolog extras too
            non_scalar_strategy: json  # encode arrays/objects as JSON strings
```

Add `service`, `host`, `http_request`, `user`, `tracing`… as needed - see the [advanced example](advanced-example.md).

## How context is preserved

| Source | Destination |
|---|---|
| `context['exception']` (framework) or `context['error']` (manual) | `error.type` / `error.message` / `error.code` / `error.stack_trace` |
| Scalar context keys (`route`, `firewall_name`, `message_id`…) | `labels.{key}` |
| Array / object context values | `labels.{key}` as JSON string |
| Monolog `extra` | `labels.{key}` |
| Native ECS keys in context (`user`, `http`, `url`…) | promoted to top-level |

## Trade-offs

- **Message keeps its placeholders** (`Matched route "{route}"`) - the value goes to `labels.route`. You lose the ready-to-read string, you gain a queryable field.
- **Raw `\Throwable` is replaced** by `error.*` fields. Downstream processors that expected the original object won't find it. Full trace remains in `error.stack_trace`.
- **JSON-encoded labels are opaque in Kibana** - with `non_scalar_strategy: json`, arrays/objects are preserved as strings, but not as structured fields. Prefer flat scalar context keys when you need to filter on them.
- **`error.code` is emitted as integer** (inherited from `elastic/ecs-logging-php`). ECS types it as `keyword`; Elasticsearch usually coerces on ingest.
