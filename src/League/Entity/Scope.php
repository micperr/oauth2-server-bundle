<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\League\Entity;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

final class Scope implements ScopeEntityInterface
{
    use EntityTrait;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->getIdentifier();
    }
}
