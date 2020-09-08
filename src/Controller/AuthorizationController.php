<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Controller;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use League\Bundle\OAuth2ServerBundle\Converter\UserConverterInterface;
use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEvent;
use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEventFactory;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;

final class AuthorizationController
{
    private $server;
    private $eventDispatcher;
    private $eventFactory;
    private $userConverter;
    private $clientManager;

    public function __construct(
        AuthorizationServer $server,
        EventDispatcherInterface $eventDispatcher,
        AuthorizationRequestResolveEventFactory $eventFactory,
        UserConverterInterface $userConverter,
        ClientManagerInterface $clientManager
    ) {
        $this->server = $server;
        $this->eventDispatcher = $eventDispatcher;
        $this->eventFactory = $eventFactory;
        $this->userConverter = $userConverter;
        $this->clientManager = $clientManager;
    }

    public function indexAction(ServerRequestInterface $serverRequest, ResponseFactoryInterface $responseFactory): ResponseInterface
    {
        $serverResponse = $responseFactory->createResponse();

        try {
            $authRequest = $this->server->validateAuthorizationRequest($serverRequest);

            if ('plain' === $authRequest->getCodeChallengeMethod()) {
                $client = $this->clientManager->find($authRequest->getClient()->getIdentifier());
                if (!$client->isPlainTextPkceAllowed()) {
                    return OAuthServerException::invalidRequest(
                        'code_challenge_method',
                        'Plain code challenge method is not allowed for this client'
                    )->generateHttpResponse($serverResponse);
                }
            }

            /** @var AuthorizationRequestResolveEvent $event */
            $event = $this->eventDispatcher->dispatch(
                $this->eventFactory->fromAuthorizationRequest($authRequest),
                OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE
            );

            $authRequest->setUser($this->userConverter->toLeague($event->getUser()));

            if ($event->hasResponse()) {
                return $event->getResponse();
            }

            $authRequest->setAuthorizationApproved($event->getAuthorizationResolution());

            return $this->server->completeAuthorizationRequest($authRequest, $serverResponse);
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse($serverResponse);
        }
    }
}
