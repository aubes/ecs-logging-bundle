# AutoLabelProcessor

Keep your custom context fields without polluting the ECS namespace. Non-ECS keys are moved to `labels` or dropped automatically.

Removes non-ECS context keys from log records to protect the ECS namespace. Optionally moves them into [`labels`](https://www.elastic.co/guide/en/ecs/current/ecs-base.html) instead of dropping them.

## Configuration

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    processor:
        auto_label:
            enabled: true
            mode: bundle           # 'bundle' (default), 'full', or 'custom'
            fields: []             # extra field names - only used when mode is 'custom'
            move_to_labels: false  # move non-ECS fields to labels instead of dropping
            include_extra: false   # also process Monolog extra array
            non_scalar_strategy: skip  # 'skip' (default) or 'json'

            #handlers: ['ecs']
            #channels: ['app']
```

## Modes

The `mode` option defines which context keys are **kept as-is** (the ECS whitelist). Everything else is dropped or moved to `labels`.

| Mode | Whitelist |
|---|---|
| `bundle` (default) | Fields used by this bundle's processors (`error`, `host`, `http`, `service`, `trace`, `transaction`, `url`, `user`, …) |
| `full` | All ECS top-level field sets (8.x and 9.x) |
| `custom` | Only the keys listed in `fields` |

Always-protected keys (`tracing`, `span`) are preserved regardless of mode.

```yaml
auto_label:
    enabled: true
    mode: bundle   # recommended starting point
```

```yaml
auto_label:
    enabled: true
    mode: full     # maximum ECS coverage, no custom fields pass through
```

```yaml
auto_label:
    enabled: true
    mode: custom
    fields: ['error', 'user', 'http', 'trace', 'my_custom_ecs_field']
```

## Move to labels (`move_to_labels`)

By default, non-ECS fields are **dropped silently**. Enable `move_to_labels` to preserve them under `labels` instead:

```yaml
auto_label:
    enabled: true
    mode: bundle
    move_to_labels: true
```

Without the processor (non-ECS fields at root level):

```json
{
    "route": "_wdt",
    "route_parameters": { "_route": "_wdt", "_controller": "...", "token": "..." },
    "request_uri": "...",
    "method": "GET"
}
```

With `move_to_labels: true` and `non_scalar_strategy: skip`:

```json
{
    "labels": {
        "route": "_wdt",
        "request_uri": "...",
        "method": "GET"
    }
}
```

`route_parameters` is an array (non-scalar) and is dropped. Use `non_scalar_strategy: json` to convert it instead.

## Non-scalar values (`non_scalar_strategy`)

ECS requires `labels` values to be scalar (string, bool, number). This option controls what happens to non-scalar values when they would be moved to labels (has no effect when `move_to_labels` is `false`).

| Value | Behaviour |
|---|---|
| `skip` (default) | Non-scalar fields are removed silently |
| `json` | Non-scalar fields are JSON-encoded into `labels` as a string. Falls back to `skip` on encoding failure. |

With `move_to_labels: true` and `non_scalar_strategy: json`:

```json
{
    "labels": {
        "route": "_wdt",
        "route_parameters": "{\"_route\":\"_wdt\",\"_controller\":\"...\",\"token\":\"...\"}",
        "request_uri": "...",
        "method": "GET"
    }
}
```

## Monolog extra fields (`include_extra`)

Monolog's `extra` array (populated by processors like `ProcessIdProcessor`, `UidProcessor`, `HostnameProcessor`) is serialised to the ECS root by the formatter, which can pollute the namespace. Enable `include_extra` to also process unknown `extra` keys (drop or move to `labels` depending on `move_to_labels`):

```yaml
auto_label:
    enabled: true
    mode: bundle
    move_to_labels: true
    include_extra: true
```

Known ECS keys in `extra` are left in place.

## Label key collision

If a key appears both in `context['labels']` (explicitly set by the application) and as a non-ECS context field, the explicit `labels` value takes priority.
