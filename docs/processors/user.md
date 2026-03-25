# UserProcessor

Injects the currently authenticated user as [ECS `user.*`](https://www.elastic.co/guide/en/ecs/current/ecs-user.html) fields via a user provider.

## Configuration

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    processor:
        user:
            enabled: true
            # ⚠️ PII: user.name is populated from getUserIdentifier() (typically an email address).
            # This appears in every authenticated log record. Use a custom provider to log a
            # non-sensitive identifier (e.g. a numeric ID) instead.
            # No authenticated user (e.g. in a Symfony command) means the processor has no effect.
            domain: ~        # ECS user.domain (e.g. 'ldap', 'ad') - optional
            provider: ~      # Service ID of a custom EcsUserProviderInterface - optional

            #handlers: ['ecs']
            #channels: ['app']
```

## Default provider

The built-in `EcsUserProvider` reads the current Symfony Security token and maps `getUserIdentifier()` to `user.name`. It requires `symfony/security-core`.

For richer user data (e.g. `user.id`, `user.email`, `user.roles`), implement a custom provider.

## Custom user provider

Create a class implementing `EcsUserProviderInterface`:

```php
// src/Security/CustomEcsUserProvider.php
namespace App\Security;

use Aubes\EcsLoggingBundle\Security\EcsUserProviderInterface;
use Elastic\Types\User;

class CustomEcsUserProvider implements EcsUserProviderInterface
{
    public function __construct(private readonly MyUserRepository $users) {}

    public function getUser(): ?User
    {
        $appUser = $this->users->getCurrentUser();
        if ($appUser === null) {
            return null;
        }

        $ecsUser = new User();
        $ecsUser->setId((string) $appUser->getId());
        $ecsUser->setName($appUser->getUsername());
        $ecsUser->setEmail($appUser->getEmail());

        return $ecsUser;
    }

    public function getDomain(): ?string
    {
        return 'my-app';
    }
}
```

Register it as a service (if `autoconfigure: true` is enabled, tagging is automatic):

```yaml
# config/services.yaml
services:
    App\Security\CustomEcsUserProvider: ~
```

Then reference it in the bundle config:

```yaml
# config/packages/ecs_logging.yaml
ecs_logging:
    processor:
        user:
            enabled: true
            # ⚠️ FrankenPHP worker mode: if your provider caches state between calls, implement
            # Symfony\Contracts\Service\ResetInterface so Symfony clears it between requests.
            # The built-in EcsUserProvider already implements it.
            provider: 'App\Security\CustomEcsUserProvider'
```
