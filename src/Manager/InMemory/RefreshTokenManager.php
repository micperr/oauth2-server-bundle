<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\InMemory;

use DateTimeImmutable;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;

final class RefreshTokenManager implements RefreshTokenManagerInterface
{
    /**
     * @var RefreshToken[]
     */
    private $refreshTokens = [];

    /**
     * {@inheritdoc}
     */
    public function find(string $identifier): ?RefreshToken
    {
        return $this->refreshTokens[$identifier] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(RefreshToken $refreshToken): void
    {
        $this->refreshTokens[$refreshToken->getIdentifier()] = $refreshToken;
    }

    public function clearExpired(): int
    {
        $count = \count($this->refreshTokens);

        $now = new DateTimeImmutable();
        $this->refreshTokens = array_filter($this->refreshTokens, static function (RefreshToken $refreshToken) use ($now): bool {
            return $refreshToken->getExpiry() >= $now;
        });

        return $count - \count($this->refreshTokens);
    }
}
