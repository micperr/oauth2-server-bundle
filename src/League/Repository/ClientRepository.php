<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\League\Repository;

use League\Bundle\OAuth2ServerBundle\League\Entity\Client as ClientEntity;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client as ClientModel;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

final class ClientRepository implements ClientRepositoryInterface
{
    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    public function __construct(ClientManagerInterface $clientManager)
    {
        $this->clientManager = $clientManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientEntity($clientIdentifier)
    {
        $client = $this->clientManager->find($clientIdentifier);

        if (null === $client) {
            return null;
        }

        return $this->buildClientEntity($client);
    }

    /**
     * {@inheritdoc}
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        $client = $this->clientManager->find($clientIdentifier);

        if (null === $client) {
            return false;
        }

        if (!$client->isActive()) {
            return false;
        }

        if (!$this->isGrantSupported($client, $grantType)) {
            return false;
        }

        if (!$client->isConfidential() || hash_equals($client->getSecret(), (string) $clientSecret)) {
            return true;
        }

        return false;
    }

    private function buildClientEntity(ClientModel $client): ClientEntity
    {
        $clientEntity = new ClientEntity();
        $clientEntity->setIdentifier($client->getIdentifier());
        $clientEntity->setRedirectUri(array_map('strval', $client->getRedirectUris()));
        $clientEntity->setConfidential($client->isConfidential());
        $clientEntity->setAllowPlainTextPkce($client->isPlainTextPkceAllowed());

        return $clientEntity;
    }

    private function isGrantSupported(ClientModel $client, ?string $grant): bool
    {
        if (null === $grant) {
            return true;
        }

        $grants = $client->getGrants();

        if (empty($grants)) {
            return true;
        }

        return \in_array($grant, $client->getGrants());
    }
}
