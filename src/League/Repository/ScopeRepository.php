<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\League\Repository;

use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverterInterface;
use League\Bundle\OAuth2ServerBundle\Event\ScopeResolveEvent;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client as ClientModel;
use League\Bundle\OAuth2ServerBundle\Model\Grant as GrantModel;
use League\Bundle\OAuth2ServerBundle\Model\Scope as ScopeModel;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var ScopeManagerInterface
     */
    private $scopeManager;

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    /**
     * @var ScopeConverterInterface
     */
    private $scopeConverter;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        ScopeManagerInterface $scopeManager,
        ClientManagerInterface $clientManager,
        ScopeConverterInterface $scopeConverter,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->scopeManager = $scopeManager;
        $this->clientManager = $clientManager;
        $this->scopeConverter = $scopeConverter;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier($identifier)
    {
        $scope = $this->scopeManager->find($identifier);

        if (null === $scope) {
            return null;
        }

        return $this->scopeConverter->toLeague($scope);
    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {
        $client = $this->clientManager->find($clientEntity->getIdentifier());

        $scopes = $this->setupScopes($client, $this->scopeConverter->toDomainArray($scopes));

        $event = $this->eventDispatcher->dispatch(
            new ScopeResolveEvent($scopes, new GrantModel($grantType), $client, $userIdentifier)
        );

        return $this->scopeConverter->toLeagueArray($event->getScopes());
    }

    /**
     * @param ScopeModel[] $requestedScopes
     *
     * @return ScopeModel[]
     */
    private function setupScopes(ClientModel $client, array $requestedScopes): array
    {
        $clientScopes = $client->getScopes();

        if (empty($clientScopes)) {
            return $requestedScopes;
        }

        if (empty($requestedScopes)) {
            return $clientScopes;
        }

        $finalizedScopes = [];
        $clientScopesAsStrings = array_map('strval', $clientScopes);

        foreach ($requestedScopes as $requestedScope) {
            $requestedScopeAsString = (string) $requestedScope;
            if (!\in_array($requestedScopeAsString, $clientScopesAsStrings, true)) {
                throw OAuthServerException::invalidScope($requestedScopeAsString);
            }

            $finalizedScopes[] = $requestedScope;
        }

        return $finalizedScopes;
    }
}
