# HttpRequestProcessor

Injects [ECS `http.*`](https://www.elastic.co/guide/en/ecs/current/ecs-http.html), [`url.*`](https://www.elastic.co/guide/en/ecs/current/ecs-url.html), and optionally `client.ip` from the current HTTP request into every log record.

> No active request (e.g. in a Symfony command) means the processor has no effect.

## Configuration

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    processor:
        http_request:
            enabled: true

            # ⚠️ query parameters frequently carry sensitive data (password reset tokens, OAuth codes,
            # API keys, session identifiers) - disabled by default
            include_full_url: false

            # ⚠️ client IP is a PII value; resolved via Symfony trusted proxies (X-Forwarded-For
            # handled correctly) - disabled by default
            include_client_ip: false

            # ⚠️ the Referer header may carry sensitive data (tokens, OAuth codes, session IDs,
            # personal search terms from external sites) - disabled by default
            include_referrer: false

            #handlers: ['ecs']
            #channels: ['app']
```

## Fields always logged

| ECS field | Source |
|---|---|
| `http.request.method` | HTTP method (`GET`, `POST`…) |
| `http.request.mime_type` | `Content-Type` header, if present |
| `http.request.bytes` | `Content-Length` header, if present |
| `http.version` | Protocol version (`1.1`, `2`) |
| `url.path` | Request path ⚠️ see security note |
| `url.scheme` | `http` or `https` |
| `url.domain` | Host (`example.com`) |
| `url.port` | Port, if non-standard (80/443 are omitted) |

> **Note - `url.path`**
> The request path can contain personal identifiers embedded in the route (e.g. `/api/users/john@example.com/`, `/reset-password/TOKEN`).
> Consider normalising routes before logging (log `/api/users/{id}` rather than `/api/users/42`).