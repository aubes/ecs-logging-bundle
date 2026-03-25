# Upgrade Guide

## Upgrading from 2.x to 3.0

### PHP 8.2 required

### Symfony LTS versions only

If you are on Symfony 7.0–7.3, upgrade to 7.4 before updating this bundle.

### `log.level` moved into the `log` object

The ECS formatter now produces a fully compliant structure. `log.level` is nested under `log` and lowercased:

```json
// Before (2.x)
{ "log.level": "INFO", ... }

// After (3.x)
{ "log": { "level": "info", "logger": "app" }, ... }
```

If you have Elasticsearch index mappings or Kibana queries targeting `log.level` as a root-level dot-notation key, update them to `log.level` (nested field path).

### `AutoLabelProcessor` — configuration overhaul

#### `fields` replaced by `mode`

The raw `fields` list is replaced by a `mode` option:

```yaml
# Before (2.x)
ecs_logging:
    processor:
        auto_label:
            enabled: true
            fields: ['error', 'user', 'service']

# After (3.x) — use mode: custom
ecs_logging:
    processor:
        auto_label:
            enabled: true
            mode: custom
            fields: ['error', 'user', 'service']
```

Available modes: `bundle` (default), `full`, `custom`.

#### Non-ECS fields are now dropped by default

In 2.x, non-ECS context fields were moved to `labels`. In 3.x, they are **silently dropped** by default. To restore the previous behaviour:

```yaml
ecs_logging:
    processor:
        auto_label:
            enabled: true
            move_to_labels: true
```

#### `FIELDS_MINIMAL` constant removed

`AutoLabelProcessor::FIELDS_MINIMAL` has been removed. Replace with `mode: bundle`.

#### `FIELDS_ALL` content changed

`os`, `vlan`, `interface`, and `tracing` have been removed; `entity` and `gen_ai` have been added. If you reference `AutoLabelProcessor::FIELDS_ALL` directly in code, switch to `mode: full` instead.

### `EcsUserProvider` — `user.name` instead of `user.id`

`getUserIdentifier()` (Symfony login/email) is now mapped to `user.name` instead of `user.id`. This matches the ECS specification: `user.name` is the login, `user.id` is a technical database identifier.

If you rely on `user.id` in your Elasticsearch index mappings, implement a custom provider:

```php
class MyUserProvider implements EcsUserProviderInterface
{
    public function getUser(): ?User
    {
        $ecsUser = new User();
        $ecsUser->setId($this->getCurrentUser()->getId());
        $ecsUser->setName($this->getCurrentUser()->getUserIdentifier());
        return $ecsUser;
    }

    public function getDomain(): ?string { return null; }
}
```

### `AbstractProcessor` — `getTargetField()` and `support()` are now `protected`

If you extended `AbstractProcessor` and override these methods, change their visibility from `public` to `protected`:

```php
// Before (2.x)
public function getTargetField(): string { ... }
public function support(LogRecord $record): bool { ... }

// After (3.x)
protected function getTargetField(): string { ... }
protected function support(LogRecord $record): bool { ... }
```

### `AbstractProcessor` — `getTargetField()` is now `final`

`getTargetField()` can no longer be overridden. Pass the target field name as the second argument to `parent::__construct()` instead:

```php
// Before (2.x / early 3.x)
final class MyProcessor extends AbstractProcessor
{
    protected function getTargetField(): string
    {
        return 'my_field';
    }
}

// After (3.x)
final class MyProcessor extends AbstractProcessor
{
    public function __construct(string $fieldName)
    {
        parent::__construct($fieldName, 'my_field');
    }
}
```

### Misconfigured processors now throw at boot

A processor that is enabled but has no `channels` or `handlers` configured now throws an `InvalidConfigurationException` at container compile time. Previously it silently had no effect. Fix your configuration:

```yaml
ecs_logging:
    monolog:
        channels: ['app']   # at least one channel or handler required
    processor:
        error:
            enabled: true
```
