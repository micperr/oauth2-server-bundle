<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Event;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\Grant;

final class UserResolveEvent extends Event
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var Grant
     */
    private $grant;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var UserInterface|null
     */
    private $user;

    public function __construct(string $username, string $password, Grant $grant, Client $client)
    {
        $this->username = $username;
        $this->password = $password;
        $this->grant = $grant;
        $this->client = $client;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getGrant(): Grant
    {
        return $this->grant;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }
}