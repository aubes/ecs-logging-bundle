# HostProcessor

Injects static [ECS `host.*`](https://www.elastic.co/guide/en/ecs/current/ecs-host.html) fields into every log record. Values are resolved once at container build time and cached for the lifetime of the process.

## Configuration

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    processor:
        host:
            enabled: true
            name: ~           # auto-detected via gethostname() if null
            ip: []            # explicit IPs; takes precedence over resolve_ip

            # ⚠️ resolve_ip triggers a blocking DNS call (gethostbyname()) at container build time.
            # In environments where DNS resolution is slow (cold containers, restricted networks),
            # this can delay startup by several seconds. Prefer providing ip explicitly:
            #   ip: ['%env(string:HOST_IP)%']
            resolve_ip: false

            architecture: ~   # auto-detected via php_uname('m') if null

            #handlers: ['ecs']
            #channels: ['app']
```

## Fields

| ECS field | Default behaviour |
|---|---|
| `host.name` | `gethostname()` |
| `host.ip` | Not set unless `ip` is provided or `resolve_ip: true` |
| `host.architecture` | `php_uname('m')` (e.g. `x86_64`, `aarch64`) |

