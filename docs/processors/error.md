# ErrorProcessor

Just pass a `\Throwable` in context, the processor handles the rest. With `map_exception_key`, Symfony's own exceptions (HttpKernel, security, form...) are also converted to ECS automatically.

Converts a `\Throwable` in the log context to [ECS `error.*`](https://www.elastic.co/guide/en/ecs/current/ecs-error.html) fields.

## Configuration

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    processor:
        error:
            enabled: true
            field_name: 'error'          # context key to read from (default: 'error')
            map_exception_key: false     # also process context['exception'] → error.*

            #handlers: ['ecs']
            #channels: ['app']
```

## Basic usage

Without the processor:

```php
use Elastic\Types\Error as EcsError;

try {
    // ...
} catch (\Throwable $e) {
    $logger->error('Something failed', ['error' => new EcsError($e)]);
}
```

With the processor:

```php
try {
    // ...
} catch (\Throwable $e) {
    $logger->error('Something failed', ['error' => $e]);
}
```

ECS output:

```json
{
    "error": {
        "type": "RuntimeException",
        "message": "Something failed",
        "code": 0,
        "stack_trace": "RuntimeException: Something failed in /app/src/Service.php:42\n..."
    }
}
```

## Symfony exceptions (`map_exception_key`)

Symfony's internal log calls pass exceptions under the `exception` key (not `error`). Enable `map_exception_key` to automatically process `context['exception']` as `error.*`, without any code change:

```yaml
ecs_logging:
    processor:
        error:
            enabled: true
            map_exception_key: true
```

This covers framework logs from `HttpKernel`, security, form, etc.
