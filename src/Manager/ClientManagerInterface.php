<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager;

use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Service\ClientFinderInterface;

interface ClientManagerInterface extends ClientFinderInterface
{
    public function save(Client $client): void;

    public function remove(Client $client): void;

    /**
     * @return Client[]
     */
    public function list(?ClientFilter $clientFilter): array;
}
