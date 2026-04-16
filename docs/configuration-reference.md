# Configuration reference

```yaml
ecs_logging:

    # ECS version declared in the ecs.version field. Use '9.3.0' for Elastic Stack 9.x.
    ecs_version: '9.3.0'

    # Static tags added to every log record (ECS tags field). Values must be strings.
    tags: []

    monolog:

        # Default logging channel list the processors should be pushed to
        channels:             []

        # Default logging handler list the processors should be pushed to
        handlers:             []
    processor:
        service:
            enabled:              false
            name:                 ~
            address:              ~
            version:              ~
            ephemeral_id:         ~
            id:                   ~
            node_name:            ~
            state:                ~
            type:                 ~

            # Logging handler list the processor should be pushed to
            handlers:             []

            # Logging channel list the processor should be pushed to
            channels:             []
        error:
            enabled:              false
            field_name:           error

            # Also process context['exception'] (Symfony convention) and map it to error.*
            map_exception_key:    false

            # Logging handler list the processor should be pushed to
            handlers:             []

            # Logging channel list the processor should be pushed to
            channels:             []
        tracing:
            enabled:              false

            # "default" reads a nested array from context[field_name]. "opentelemetry" reads flat trace_id/span_id keys injected by the OTel Monolog processor (field_name is ignored).
            mode:                 default # One of "default"; "opentelemetry"

            # Context key read by the processor in "default" mode. Ignored in "opentelemetry" mode.
            field_name:           tracing

            # Logging handler list the processor should be pushed to
            handlers:             []

            # Logging channel list the processor should be pushed to
            channels:             []
        user:
            enabled:              false
            domain:               null
            provider:             null

            # Logging handler list the processor should be pushed to
            handlers:             []

            # Logging channel list the processor should be pushed to
            channels:             []
        auto_label:
            enabled:              false

            # ECS field whitelist: "bundle" (fields covered by this bundle), "full" (all ECS field sets), "custom" (only fields listed in "fields").
            mode:                 bundle # One of "bundle"; "full"; "custom"

            # Extra ECS field names to whitelist when mode is "custom". Ignored for other modes.
            fields:               []

            # Move non-ECS context fields into labels instead of dropping them.
            move_to_labels:       false

            # Also process non-ECS keys from Monolog extra (e.g. from ProcessIdProcessor, HostnameProcessor).
            include_extra:        false

            # Strategy for non-ECS context values that are not scalar. "skip" removes them silently; "json" converts them via json_encode (falls back to skip on failure).
            non_scalar_strategy:  skip # One of "skip"; "json"

            # Logging handler list the processor should be pushed to
            handlers:             []

            # Logging channel list the processor should be pushed to
            channels:             []
        host:
            enabled:              false

            # host.name - auto-detected via gethostname() if null
            name:                 null

            # host.ip - auto-detected via gethostbyname() if empty and resolve_ip is true
            ip:                   []

            # Resolve host.ip via gethostbyname() when ip is not provided. WARNING: this is a blocking DNS call at container build time.
            resolve_ip:           false

            # host.architecture - auto-detected via php_uname('m') if null
            architecture:         null

            # Logging handler list the processor should be pushed to
            handlers:             []

            # Logging channel list the processor should be pushed to
            channels:             []
        correlation_id:
            enabled:              false

            # Key to read from Monolog extra (must match the library that populates extra).
            field_name:           correlation_id

            # Where to write the correlation ID: "labels" writes to labels.correlation_id, "trace" writes to trace.id.
            target:               labels # One of "labels"; "trace"

            # Logging handler list the processor should be pushed to
            handlers:             []

            # Logging channel list the processor should be pushed to
            channels:             []
        http_request:
            enabled:              false

            # Log url.full and url.query. WARNING: may expose sensitive data (tokens, API keys) present in query parameters.
            include_full_url:     false

            # Log client.ip from the request. Uses Symfony trusted proxies to resolve the real IP behind load balancers.
            include_client_ip:    false

            # Log http.request.referrer. WARNING: the Referer header may contain external URLs with sensitive data (tokens, session identifiers).
            include_referrer:     false

            # Logging handler list the processor should be pushed to
            handlers:             []

            # Logging channel list the processor should be pushed to
            channels:             []
```
