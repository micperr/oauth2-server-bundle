<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use League\Bundle\OAuth2ServerBundle\Manager\InMemory\RefreshTokenManager as InMemoryRefreshTokenManager;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;

final class InMemoryRefreshTokenManagerTest extends TestCase
{
    public function testClearExpired(): void
    {
        $inMemoryRefreshTokenManager = new InMemoryRefreshTokenManager();

        timecop_freeze(new DateTimeImmutable());

        try {
            $testData = $this->buildClearExpiredTestData();

            foreach ($testData['input'] as $token) {
                $inMemoryRefreshTokenManager->save($token);
            }

            $this->assertSame(3, $inMemoryRefreshTokenManager->clearExpired());
        } finally {
            timecop_return();
        }

        $reflectionProperty = new ReflectionProperty(InMemoryRefreshTokenManager::class, 'refreshTokens');
        $reflectionProperty->setAccessible(true);

        $this->assertSame($testData['output'], $reflectionProperty->getValue($inMemoryRefreshTokenManager));
    }

    private function buildClearExpiredTestData(): array
    {
        $validRefreshTokens = [
            '1111' => $this->buildRefreshToken('1111', '+1 day'),
            '2222' => $this->buildRefreshToken('2222', '+1 hour'),
            '3333' => $this->buildRefreshToken('3333', '+1 second'),
            '4444' => $this->buildRefreshToken('4444', 'now'),
        ];

        $expiredRefreshTokens = [
            '5555' => $this->buildRefreshToken('5555', '-1 day'),
            '6666' => $this->buildRefreshToken('6666', '-1 hour'),
            '7777' => $this->buildRefreshToken('7777', '-1 second'),
        ];

        return [
            'input' => $validRefreshTokens + $expiredRefreshTokens,
            'output' => $validRefreshTokens,
        ];
    }

    private function buildRefreshToken(string $identifier, string $modify): RefreshToken
    {
        return new RefreshToken(
            $identifier,
            new DateTimeImmutable($modify),
            new AccessToken(
                $identifier,
                new DateTimeImmutable('+1 day'),
                new Client('client', 'secret'),
                null,
                []
            )
        );
    }
}
