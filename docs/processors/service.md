# ServiceProcessor

Injects static [ECS `service.*`](https://www.elastic.co/guide/en/ecs/current/ecs-service.html) metadata into every log record. Values are defined in config and injected at container build time.

## Configuration

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    processor:
        service:
            enabled: true
            name: 'my-app'
            version: '%env(string:APP_VERSION)%'
            type: 'web'
            id: ~
            ephemeral_id: ~
            node_name: ~
            state: ~
            address: ~

            #handlers: ['ecs']
            #channels: ['app']
```

| Option | ECS field | Description |
|---|---|---|
| `name` | `service.name` | Name of the service |
| `version` | `service.version` | Version of the service |
| `type` | `service.type` | Type of service (`web`, `db`…) |
| `id` | `service.id` | Unique service identifier |
| `ephemeral_id` | `service.ephemeral_id` | Ephemeral ID, regenerated on restart |
| `node_name` | `service.node.name` | Node or instance name |
| `state` | `service.state` | Current state of the service |
| `address` | `service.address` | Service address |

## Usage

Without the processor, you must build and pass the object manually:

```php
use Elastic\Types\Service;

$service = new Service();
$service->setName('my-app');
$service->setVersion('1.0.0');

$logger->info('message', ['service' => $service]);
```

With the processor enabled, every log record receives the service fields automatically:

```php
$logger->info('message');
```
