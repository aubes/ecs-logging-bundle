# ErrorProcessor

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
