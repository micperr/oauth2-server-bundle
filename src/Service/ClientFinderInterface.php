<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Service;

use League\Bundle\OAuth2ServerBundle\Model\Client;

/**
 * @api
 */
interface ClientFinderInterface
{
    public function find(string $identifier): ?Client;
}
