<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests\Acceptance;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;

/**
 * @covers \League\Bundle\OAuth2ServerBundle\Command\DeleteClientCommand
 */
final class DeleteClientCommandTest extends AbstractAcceptanceTest
{
    public function testDeleteClient(): void
    {
        $client = $this->fakeAClient('foobar');
        $this->getClientManager()->save($client);

        $command = $this->command();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'identifier' => $client->getIdentifier(),
        ]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Given oAuth2 client deleted successfully', $output);

        $client = $this->findClient($client->getIdentifier());
        $this->assertNull($client);
    }

    public function testDeleteNonExistentClient(): void
    {
        $identifierName = 'invalid identifier';
        $command = $this->command();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'identifier' => $identifierName,
        ]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(sprintf('oAuth2 client identified as "%s" does not exist', $identifierName), $output);
    }

    private function findClient(string $identifier): ?Client
    {
        return
            $this
                ->getClientManager()
                ->find($identifier)
            ;
    }

    private function fakeAClient(string $identifier): Client
    {
        return new Client($identifier, 'quzbaz');
    }

    private function getClientManager(): ClientManagerInterface
    {
        return
            $this
                ->client
                ->getContainer()
                ->get(ClientManagerInterface::class)
            ;
    }

    private function command(): Command
    {
        return $this->application->find('league:oauth2-server:delete-client');
    }
}
