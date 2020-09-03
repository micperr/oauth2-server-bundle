<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use League\OAuth2\Server\ResourceServer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Provider\OAuth2Provider;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2TokenFactory;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\User;

final class OAuth2ProviderTest extends TestCase
{
    public function testItSupportsOnlyOAuthTokenWithSameProviderKey(): void
    {
        $providerKey = 'foo';

        $tokenFactory = new OAuth2TokenFactory('ROLE_OAUTH2_');

        $provider = new OAuth2Provider(
            $this->createMock(UserProviderInterface::class),
            $this->createMock(ResourceServer::class),
            $tokenFactory,
            $providerKey
        );

        $this->assertTrue($provider->supports($this->createToken($tokenFactory, $providerKey)));
        $this->assertFalse($provider->supports($this->createToken($tokenFactory, $providerKey . 'bar')));
    }

    private function createToken(OAuth2TokenFactory $tokenFactory, string $providerKey): OAuth2Token
    {
        $scopes = [FixtureFactory::FIXTURE_SCOPE_FIRST];
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $serverRequest->expects($this->once())
            ->method('getAttribute')
            ->with('oauth_scopes', [])
            ->willReturn($scopes);

        $user = new User();

        return $tokenFactory->createOAuth2Token($serverRequest, $user, $providerKey);
    }
}
