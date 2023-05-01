# Ecs Logging Bundle

![CI](https://github.com/aubes/ecs-logging-bundle/actions/workflows/php.yml/badge.svg)

This Symfony bundle provides the [Ecs](https://www.elastic.co/guide/en/ecs/current/ecs-reference.html) log format.

It uses [elastic/ecs-logging](https://github.com/elastic/ecs-logging).

It is compatible with :
 * PHP 7.4, 8.x
 * Symfony 5.4 and 6.x
 * Monolog 2.x

## Installation

```shell
composer require aubes/ecs-logging-bundle
```

## Configuration

### Formatter

First, you need to configure the Ecs formatter in monolog:

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        ecs:
            # [...]
            formatter: 'monolog.formatter.ecs'
```

Then configure the bundle, the configuration looks as follows :

```yaml
# config/packages/ecs-logging.yaml
ecs_logging:
    monolog:
        # Register the processors on channels or handlers, not both
        # To configure channels or handlers is recommended
        # Default logging channels the processors should be pushed to
        handlers: []

        # Default logging handlers the processors should be pushed to
        #channels: []

    processor:
        service:
            enabled: false
            name: ~
            version: ~
            ephemeral_id: ~
            id: ~
            node_name: ~
            state: ~
            type: ~

            # Logging channels the processor should be pushed to
            #handlers: []

            # Logging handlers the processor should be pushed to
            #channels: []

        error:
            enabled: false
            field_name: 'error'

            # Logging channels the processor should be pushed to
            #handlers: []

            # Logging handlers the processor should be pushed to
            #channels: []

        tracing:
            enabled: false
            field_name: 'tracing'

            # Logging channels the processor should be pushed to
            #handlers: []

            # Logging handlers the processor should be pushed to
            #channels: []

        user:
            enabled: false
            domain: ~ # Ecs user domain, example: ldap
            provider: ~ # Service Id of the Ecs user provider, default: Aubes\EcsLoggingBundle\Security\EcsUserProvider

            # Logging channels the processor should be pushed to
            #handlers: []

            # Logging handlers the processor should be pushed to
            #channels: []

        auto_label:
            enabled: false
            fields: [] # Name of internal fields, these fields will not be moved

            # Logging channels the processor should be pushed to
            #handlers: []

            # Logging handlers the processor should be pushed to
            #channels: []
```

### Configuration example

```yaml
# config/packages/ecs-logging.yaml
ecs_logging:
    monolog:
        handlers: ['ecs']

    processor:
        service:
            enabled: true
            name: 'MyApp'
            version: 'v1.0.0'
            # [...]

        user:
            enabled: false
            domain: ~ # Ecs user domain, example: ldap
```

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        ecs:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.ecs.log"
            level: info
            channels: [ "app" ]
            formatter: 'monolog.formatter.ecs'
```

## Usage

### Service processor

#### Without service processor

```php
use Elastic\Types\Service;

$service = new Service()
$service->setName(/* [...] */);
$service->setVersion(/* [...] */);

$logger->info('exception.message', [
    'service' => new Service(),
]);
```

#### With service processor

Enable the processor:

```yaml
# config/packages/ecs-logging.yaml
ecs_logging:
    # [...]
    processor:
        service:
            enabled: true
            name: # [...]
            version: # [...]
```

```php
$logger->info('message');
```

### Log error

#### Without error processor

```php
use Elastic\Types\Error as EcsError;

try {
    // [...]
} catch (\Exception $e) {
    $logger->info('exception.message', [
        'error' => new EcsError($e),
    ]);
}
```

#### With error processor

Enable the processor:

```yaml
# config/packages/ecs-logging.yaml
ecs_logging:
    # [...]
    processor:
        error:
            enabled: true
```

```php
try {
    // [...]
} catch (\Exception $e) {
    $logger->info('exception.message', [
        'error' => $e,
    ]);
}
```

### Tracing

#### Without tracing processor

```php
use Elastic\Types\Tracing;

// [...]

$logger->info('tracing.message', [
    'tracing' => new Tracing($traceId, $transactionId),
]);
```

#### With tracing processor

Enable the processor:

```yaml
# config/packages/ecs-logging.yaml
ecs_logging:
    # [...]
    processor:
        tracing:
            enabled: true
```

```php
// [...]

$logger->info('tracing.message', [
    'tracing' => [
        'trace_id' => $traceId,
        'transaction_id' => $transactionId,
    ],
]);
```

### User

#### Without user processor

```php
use Elastic\Types\User;

// [...]

$ecsUser = new User();
$ecsUser->setId($userId);
$ecsUser->setName($userName);

$logger->info('exception.message', [
    'user' => $ecsUser,
]);
```

#### With user processor

Enable the processor:

```yaml
# config/packages/ecs-logging.yaml
ecs_logging:
    # [...]
    processor:
        user:
            enabled: true
```

```php
// [...]

$logger->info('message');
```

### Auto label

To automatically move all additional fields into the Ecs `labels` field, useful for internal Symfony bundle log.

For example without the processor, a Symfony log contains these fields :

```json
{
    "route": "_wdt",
    "route_parameters": {
        "_route": "_wdt",
        "_controller": "web_profiler.controller.profiler::toolbarAction",
        "token": "afcfbe"
    },
    "request_uri": "http://localhost:8080/_wdt/afcfbe",
    "method": "GET"
}
```

With the processor, the Symfony log looks like :

```json
{
    "labels": {
        "route": "_wdt",
        "route_parameters": {
            "_route": "_wdt",
            "_controller": "web_profiler.controller.profiler::toolbarAction",
            "token": "afcfbe"
        },
        "request_uri": "http://localhost:8080/_wdt/afcfbe",
        "method": "GET"
    }
}
```

Warning, this processor can impact performance.

#### Configuration

First, you need to configure the processor:

```yaml
# config/packages/ecs-logging.yaml
ecs_logging:
    # [...]

    processor:
        auto_label:
            enabled: false
            fields: [] # Name of internal fields, these fields will not be moved
            #fields: !php/const Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor::FIELDS_MINIMAL
            #fields: !php/const Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor::FIELDS_BUNDLE
            #fields: !php/const Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor::FIELDS_ALL
```

You can define a custom list or use the built-in constant:

 * Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor::FIELDS_MINIMAL: minimal fields supported by the bundle
 * Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor::FIELDS_BUNDLE: all fields supported by the bundle
 * Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor::FIELDS_ALL: all [Ecs fields](https://www.elastic.co/guide/en/ecs/current/ecs-field-reference.html) and all bundle fields

For performance reasons, use only necessary fields.

#### Configuration example

```yaml
# config/packages/ecs-logging.yaml
ecs_logging:
    monolog:
        handlers: ['app', 'main']

    processor:
        # [...]

        auto_label:
            enabled: true
            fields: !php/const Aubes\EcsLoggingBundle\Logger\AutoLabelProcessor::FIELDS_BUNDLE
            handlers: ['main'] # do not apply on ecs channel
```

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: warning
            channels: [ "!event" ]
            formatter: 'monolog.formatter.ecs'
        app:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: info
            channels: [ "app" ]
            formatter: 'monolog.formatter.ecs'
```

## Custom Ecs user provider

The default Ecs user provider is [Aubes\EcsLoggingBundle\Security\EcsUserProvider](src/Security/EcsUserProvider.php), but you can use your own provider.

First you need to create an Ecs User Provider class and implement [EcsUserProviderInterface](src/Security/EcsUserProviderInterface.php) :

```php
// src/Security/CustomEcsUserProvider.php
namespace App\Security;

use Elastic\Types\User;
use Symfony\Component\Security\Core\Security;

class CustomEcsUserProvider implements EcsUserProviderInterface
{
    public function getUser(): ?User
    {
        // [...]
    }

    public function getDomain(): ?string
    {
        return 'custom_user_provider';
    }
}
```

Next, register your class as a service :

```yaml
# config/services.yaml
services:
    App\Security\CustomEcsUserProvider: ~
```

Then, configure the provider :

```yaml
# config/packages/ecs-logging.yaml
ecs_logging:
    # [...]

    processor:
        user:
            enabled: true
            provider: 'App\Security\CustomEcsUserProvider'
```
