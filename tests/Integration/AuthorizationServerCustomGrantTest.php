<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Integration;

use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrant;

final class AuthorizationServerCustomGrantTest extends KernelTestCase
{
    public function testAuthorizationServerHasOurCustomGrantEnabled(): void
    {
        static::bootKernel();

        /** @var AuthorizationServer $authorizationServer */
        $authorizationServer = self::$container->get(AuthorizationServer::class);

        $reflectionClass = new ReflectionClass(AuthorizationServer::class);
        $reflectionProperty = $reflectionClass->getProperty('enabledGrantTypes');
        $reflectionProperty->setAccessible(true);

        $enabledGrantTypes = $reflectionProperty->getValue($authorizationServer);

        $this->assertArrayHasKey('fake_grant', $enabledGrantTypes);
        $this->assertInstanceOf(FakeGrant::class, $enabledGrantTypes['fake_grant']);
        $this->assertEquals(new DateInterval('PT5H'), $enabledGrantTypes['fake_grant']->getAccessTokenTTL());
    }
}
