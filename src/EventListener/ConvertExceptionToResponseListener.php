<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use League\Bundle\OAuth2ServerBundle\Security\Exception\InsufficientScopesException;
use League\Bundle\OAuth2ServerBundle\Security\Exception\Oauth2AuthenticationFailedException;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class ConvertExceptionToResponseListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof InsufficientScopesException || $exception instanceof Oauth2AuthenticationFailedException) {
            $event->setResponse(new Response($exception->getMessage(), $exception->getCode()));
        }
    }
}
