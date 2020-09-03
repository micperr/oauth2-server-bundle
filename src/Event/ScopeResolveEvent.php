<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\Grant;
use League\Bundle\OAuth2ServerBundle\Model\Scope;

final class ScopeResolveEvent extends Event
{
    /**
     * @var Scope[]
     */
    private $scopes = [];

    /**
     * @var Grant
     */
    private $grant;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string|null
     */
    private $userIdentifier;

    public function __construct(array $scopes, Grant $grant, Client $client, ?string $userIdentifier)
    {
        $this->scopes = $scopes;
        $this->grant = $grant;
        $this->client = $client;
        $this->userIdentifier = $userIdentifier;
    }

    /**
     * @return Scope[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setScopes(Scope ...$scopes): self
    {
        $this->scopes = $scopes;

        return $this;
    }

    public function getGrant(): Grant
    {
        return $this->grant;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }
}
