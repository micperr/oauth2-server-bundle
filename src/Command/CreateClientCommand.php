<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Command;

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\Model\Grant;
use League\Bundle\OAuth2ServerBundle\Model\RedirectUri;
use League\Bundle\OAuth2ServerBundle\Model\Scope;

final class CreateClientCommand extends Command
{
    protected static $defaultName = 'league:oauth2-server:create-client';

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    public function __construct(ClientManagerInterface $clientManager)
    {
        parent::__construct();

        $this->clientManager = $clientManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a new oAuth2 client')
            ->addOption(
                'redirect-uri',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets redirect uri for client. Use this option multiple times to set multiple redirect URIs.',
                []
            )
            ->addOption(
                'grant-type',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets allowed grant type for client. Use this option multiple times to set multiple grant types.',
                []
            )
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets allowed scope for client. Use this option multiple times to set multiple scopes.',
                []
            )
            ->addArgument(
                'identifier',
                InputArgument::OPTIONAL,
                'The client identifier'
            )
            ->addArgument(
                'secret',
                InputArgument::OPTIONAL,
                'The client secret'
            )
            ->addOption(
                'public',
                null,
                InputOption::VALUE_NONE,
                'Create a public client.'
            )
            ->addOption(
                'allow-plain-text-pkce',
                null,
                InputOption::VALUE_NONE,
                'Create a client who is allowed to use plain challenge method for PKCE.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $client = $this->buildClientFromInput($input);
        } catch (InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return 1;
        }

        $this->clientManager->save($client);
        $io->success('New oAuth2 client created successfully.');

        $headers = ['Identifier', 'Secret'];
        $rows = [
            [$client->getIdentifier(), $client->getSecret()],
        ];
        $io->table($headers, $rows);

        return 0;
    }

    private function buildClientFromInput(InputInterface $input): Client
    {
        $identifier = $input->getArgument('identifier') ?? hash('md5', random_bytes(16));

        $isPublic = $input->getOption('public');

        if (null !== $input->getArgument('secret') && $isPublic) {
            throw new InvalidArgumentException('The client cannot have a secret and be public.');
        }

        $secret = $isPublic ? null : $input->getArgument('secret') ?? hash('sha512', random_bytes(32));

        $client = new Client($identifier, $secret);
        $client->setActive(true);
        $client->setAllowPlainTextPkce($input->getOption('allow-plain-text-pkce'));

        $redirectUris = array_map(
            static function (string $redirectUri): RedirectUri { return new RedirectUri($redirectUri); },
            $input->getOption('redirect-uri')
        );
        $client->setRedirectUris(...$redirectUris);

        $grants = array_map(
            static function (string $grant): Grant { return new Grant($grant); },
            $input->getOption('grant-type')
        );
        $client->setGrants(...$grants);

        $scopes = array_map(
            static function (string $scope): Scope { return new Scope($scope); },
            $input->getOption('scope')
        );
        $client->setScopes(...$scopes);

        return $client;
    }
}
