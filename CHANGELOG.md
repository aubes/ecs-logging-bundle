# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.1]

### Fixed

- Per-processor `channels`/`handlers` routing no longer inherits global defaults when the processor defines its own routing. Previously, a processor with only `handlers` configured would also inherit global `channels`, causing an "cannot target both channels and handlers" error.

## [3.0.0]

### Breaking Changes

- **PHP 8.2** minimum — PHP 8.1 (EOL since December 2024) is no longer supported.
- **Symfony LTS-only** — supported versions are 6.4 (LTS), 7.4 (LTS), and 8.0. Intermediate versions (7.0–7.3) are not supported.
- **`monolog.formatter.ecs`** now produces a different JSON structure: `log.level` is nested under `log` and lowercased (was a root-level dot-notation key). Existing Elasticsearch index mappings may need to be updated.
- **`AutoLabelProcessor::FIELDS_ALL`** content has changed: `os`, `vlan`, `interface`, and `tracing` have been removed; `entity` (ECS 9.x) and `gen_ai` (ECS 9.1) have been added. If you reference this constant directly in code, review your usage.
- **`AutoLabelProcessor`** — non-ECS fields are now **dropped** by default instead of being moved to `labels`. Add `move_to_labels: true` to restore the previous behaviour.
- **`AutoLabelProcessor`** — the `fields` config key is replaced by `mode` (`bundle` | `full` | `custom`). Use `mode: custom` with `fields: [...]` for the previous behaviour of passing a raw list.
- **`AutoLabelProcessor::FIELDS_MINIMAL`** constant removed. Use `mode: bundle` instead.
- **`AbstractProcessor`** — `getTargetField()` and `support()` visibility changed from `public` to `protected`. Code calling these methods from outside a subclass will fail.
- **`AbstractProcessor`** — `getTargetField()` is now `final` and no longer abstract. Subclasses must pass the target field name as the second argument to `parent::__construct()` instead of implementing `getTargetField()`.
- A misconfigured processor (enabled but no channel or handler defined) now throws an `InvalidConfigurationException` at container compile time. Previously it silently had no effect.
- **`EcsUserProvider`** — `getUserIdentifier()` is now mapped to `user.name` instead of `user.id`. Symfony's identifier is a login or email (`user.name`), not a technical database ID (`user.id`). If you rely on `user.id` in your index mappings, implement a custom `EcsUserProviderInterface`.
- **`tags`** config option — static tags added to every log record via the ECS `tags` field (e.g. `['env:prod', 'region:eu-west-1']`). Passed through to the underlying `ElasticCommonSchemaFormatter`.

### Added

- **`ecs_version`** config option — declare which ECS version to output (default `9.3.0`). Override for older Elastic Stack deployments (e.g. `8.11.0`). Invalid values throw an `InvalidConfigurationException` at boot.
- **`HttpRequestProcessor`** — injects ECS `http.*` and `url.*` fields from the current HTTP request. Optional `include_full_url` (disabled by default — may expose sensitive query parameters), `include_client_ip` (disabled by default), and `include_referrer` (disabled by default — Referer header may carry sensitive external URLs).
- **`HostProcessor`** — injects ECS `host.*` fields (`host.name`, `host.ip`, `host.architecture`) resolved at boot time. Optional `resolve_ip` (default `false`) to auto-detect `host.ip` via DNS.
- **`ErrorProcessor`** — new `map_exception_key` option to automatically process Symfony's `context['exception']` as ECS `error.*`.
- **`AutoLabelProcessor`** — new `mode` option (`bundle` | `full` | `custom`, default `bundle`) replacing the raw `fields` list. `bundle` whitelists fields used by this bundle's processors; `full` covers all ECS field sets; `custom` uses the explicit `fields` list.
- **`AutoLabelProcessor`** — new `move_to_labels` option (default `false`). Non-ECS fields are now **dropped** by default; set `move_to_labels: true` to preserve them under `labels` as before.
- **`AutoLabelProcessor`** — new `include_extra` option to also process non-ECS keys from Monolog's `extra` array.
- **`AutoLabelProcessor`** — new `non_scalar_strategy` option (`skip` | `json`, default `skip`). Non-scalar context values are either dropped (`skip`) or JSON-encoded into `labels` (`json`) when `move_to_labels` is enabled.
- **`TracingProcessor`** — new `span_id` input key. When present in the tracing array, injects ECS `span.id` into the log record.
- **`processor.user.provider`** — the referenced service is now validated at container compile time: it must implement `EcsUserProviderInterface`. Invalid configurations throw at boot instead of silently failing at log time.
- **`EcsUserProvider`** and **`UserProcessor`** — both implement `ResetInterface` for compatibility with FrankenPHP worker mode. State is cleared between requests automatically.

### Fixed

- **`AutoLabelProcessor`** — non-scalar values in non-ECS context fields were previously placed in `labels` as-is, violating ECS (labels must be scalar). They are now handled via `non_scalar_strategy`.
- **`AutoLabelProcessor`** — a non-array `context['labels']` value no longer throws `\InvalidArgumentException` at runtime. The invalid value is silently overwritten to preserve ECS compliance.

## [2.0.2]

### Fixed

- **`AutoLabelProcessor`** — ensure it runs last and validate `labels` type.
- Monolog configuration error.

## [2.0.1]

### Changed

- Symfony 7 and 8 compatibility.

### Fixed

- Custom user providers configured via `ecs_logging.processor.user.provider` were never used.
- Missing `address` field in configuration for the service processor.

## [2.0.0]

### Breaking Changes

- Requires Symfony 7.x and Monolog 3.x. Symfony 6.x and Monolog 2.x are no longer supported.

### Added

- Symfony 7 and Monolog 3 compatibility.

## [1.0.0]

### Added

- Initial release.
- `monolog.formatter.ecs` service for ECS-compliant log formatting.
- **`ServiceProcessor`** — injects static service metadata into every log record.
- **`ErrorProcessor`** — converts a `\Throwable` in context to ECS `error.*`.
- **`TracingProcessor`** — converts a tracing array in context to ECS `trace.*` / `transaction.*`.
- **`UserProcessor`** — injects the current authenticated user as ECS `user.*`.
- **`AutoLabelProcessor`** — moves non-ECS context keys into `labels`. Built-in field lists: `FIELDS_MINIMAL`, `FIELDS_BUNDLE`, `FIELDS_ALL`.
- Symfony 6.4, 7.x, and 8.x compatibility.
- PHP 8.1+ support.

[Unreleased]: https://github.com/aubes/ecs-logging-bundle/compare/v3.0.0...HEAD
[3.0.0]: https://github.com/aubes/ecs-logging-bundle/compare/v2.0.2...v3.0.0
[2.0.2]: https://github.com/aubes/ecs-logging-bundle/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/aubes/ecs-logging-bundle/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/aubes/ecs-logging-bundle/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/aubes/ecs-logging-bundle/releases/tag/v1.0.0
