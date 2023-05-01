<?php

declare(strict_types=1);

namespace Aubes\EcsLoggingBundle\Security;

use Elastic\Types\User;

interface EcsUserProviderInterface
{
    public function getUser(): ?User;

    public function getDomain(): ?string;
}
