<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Tests;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use LogicException;
use Nyholm\Psr7\Factory as Nyholm;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FakeGrant;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\FixtureFactory;
use League\Bundle\OAuth2ServerBundle\Tests\Fixtures\SecurityTestController;
use League\Bundle\OAuth2ServerBundle\Tests\Support\SqlitePlatform;

final class TestKernel extends Kernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    private const PSR_HTTP_PROVIDER_NYHOLM = 'nyholm';
    private const PSR_HTTP_PROVIDER_ZENDFRAMEWORK = 'zendframework';

    /**
     * @var string
     */
    private $psrHttpProvider;

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->determinePsrHttpFactory();
        $this->initializeEnvironmentVariables();

        parent::boot();
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \League\Bundle\OAuth2ServerBundle\LeagueOAuth2ServerBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return sprintf('%s/Tests/.kernel/cache', $this->getProjectDir());
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return sprintf('%s/Tests/.kernel/logs', $this->getProjectDir());
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->exposeManagerServices($container);
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerClass()
    {
        return parent::getContainerClass() . ucfirst($this->psrHttpProvider);
    }

    /**
     * {@inheritdoc}
     *
     * @throws LoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import('@LeagueOAuth2ServerBundle/Resources/config/routes.xml');

        $routes
            ->add('/security-test', 'League\Bundle\OAuth2ServerBundle\Tests\Fixtures\SecurityTestController:helloAction')
        ;

        $routes
            ->add('/security-test-scopes', 'League\Bundle\OAuth2ServerBundle\Tests\Fixtures\SecurityTestController:scopeAction')
            ->setDefault('oauth2_scopes', ['fancy'])
        ;

        $routes
            ->add('/security-test-roles', 'League\Bundle\OAuth2ServerBundle\Tests\Fixtures\SecurityTestController:rolesAction')
            ->setDefault('oauth2_scopes', ['fancy'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->loadFromExtension('doctrine', [
            'dbal' => [
                'driver' => 'sqlite',
                'charset' => 'utf8mb4',
                'url' => 'sqlite:///:memory:',
                'default_table_options' => [
                    'charset' => 'utf8mb4',
                    'utf8mb4_unicode_ci' => 'utf8mb4_unicode_ci',
                ],
                'platform_service' => SqlitePlatform::class,
            ],
            'orm' => null,
        ]);

        $container->loadFromExtension('framework', [
            'secret' => 'nope',
            'test' => null,
        ]);

        $container->loadFromExtension('security', [
            'firewalls' => [
                'test' => [
                    'pattern' => '^/security-test',
                    'stateless' => true,
                    'oauth2' => true,
                ],
            ],
            'providers' => [
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            FixtureFactory::FIXTURE_USER => [
                                'roles' => ['ROLE_USER'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $container->loadFromExtension('sensio_framework_extra', [
            'router' => [
                'annotations' => false,
            ],
        ]);

        $container->loadFromExtension('trikoder_oauth2', [
            'authorization_server' => [
                'private_key' => '%env(PRIVATE_KEY_PATH)%',
                'encryption_key' => '%env(ENCRYPTION_KEY)%',
            ],
            'resource_server' => [
                'public_key' => '%env(PUBLIC_KEY_PATH)%',
            ],
            'scopes' => [
                FixtureFactory::FIXTURE_SCOPE_SECOND,
            ],
            'persistence' => [
                'doctrine' => [
                    'entity_manager' => 'default',
                ],
            ],
        ]);

        $this->configureControllers($container);
        $this->configurePsrHttpFactory($container);
        $this->configureDatabaseServices($container);
        $this->registerFakeGrant($container);
    }

    private function exposeManagerServices(ContainerBuilder $container): void
    {
        $container
            ->getDefinition(
                (string) $container
                    ->getAlias(ScopeManagerInterface::class)
                    ->setPublic(true)
            )
            ->setPublic(true)
        ;

        $container
            ->getDefinition(
                (string) $container
                    ->getAlias(ClientManagerInterface::class)
                    ->setPublic(true)
            )
            ->setPublic(true)
        ;

        $container
            ->getDefinition(
                (string) $container
                    ->getAlias(AccessTokenManagerInterface::class)
                    ->setPublic(true)
            )
            ->setPublic(true)
        ;

        $container
            ->getDefinition(
                (string) $container
                    ->getAlias(RefreshTokenManagerInterface::class)
                    ->setPublic(true)
            )
            ->setPublic(true)
        ;

        $container
            ->getDefinition(
                (string) $container
                    ->getAlias(AuthorizationCodeManagerInterface::class)
                    ->setPublic(true)
            )
            ->setPublic(true)
        ;
    }

    private function configurePsrHttpFactory(ContainerBuilder $container): void
    {
        switch ($this->psrHttpProvider) {
            case self::PSR_HTTP_PROVIDER_ZENDFRAMEWORK:
                $serverRequestFactory = ServerRequestFactory::class;
                $streamFactory = StreamFactory::class;
                $uploadedFileFactory = UploadedFileFactory::class;
                $responseFactory = ResponseFactory::class;
                break;
            case self::PSR_HTTP_PROVIDER_NYHOLM:
                $serverRequestFactory = Nyholm\Psr17Factory::class;
                $streamFactory = Nyholm\Psr17Factory::class;
                $uploadedFileFactory = Nyholm\Psr17Factory::class;
                $responseFactory = Nyholm\Psr17Factory::class;
                break;
            default:
                throw new LogicException(sprintf('PSR HTTP factory provider \'%s\' is not supported.', $this->psrHttpProvider));
        }

        $container->addDefinitions([
            $serverRequestFactory => new Definition($serverRequestFactory),
            $streamFactory => new Definition($streamFactory),
            $uploadedFileFactory => new Definition($uploadedFileFactory),
            $responseFactory => new Definition($responseFactory),
        ]);

        $container->addAliases([
            ServerRequestFactoryInterface::class => $serverRequestFactory,
            StreamFactoryInterface::class => $streamFactory,
            UploadedFileFactoryInterface::class => $uploadedFileFactory,
            ResponseFactoryInterface::class => $responseFactory,
        ]);
    }

    private function configureControllers(ContainerBuilder $container): void
    {
        $container
            ->register(SecurityTestController::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
        ;
    }

    private function configureDatabaseServices(ContainerBuilder $container): void
    {
        $container
            ->register(SqlitePlatform::class)
            ->setAutoconfigured(true)
            ->setAutowired(true)
        ;
    }

    private function registerFakeGrant(ContainerBuilder $container): void
    {
        $container->register(FakeGrant::class)->setAutoconfigured(true);
    }

    private function determinePsrHttpFactory(): void
    {
        $psrHttpProvider = getenv('PSR_HTTP_PROVIDER');

        switch ($psrHttpProvider) {
            case self::PSR_HTTP_PROVIDER_ZENDFRAMEWORK:
                $this->psrHttpProvider = self::PSR_HTTP_PROVIDER_ZENDFRAMEWORK;
                break;
            case self::PSR_HTTP_PROVIDER_NYHOLM:
                $this->psrHttpProvider = self::PSR_HTTP_PROVIDER_NYHOLM;
                break;
            default:
                throw new LogicException(sprintf('PSR HTTP factory provider \'%s\' is not supported.', $psrHttpProvider));
        }
    }

    private function initializeEnvironmentVariables(): void
    {
        putenv(sprintf('PRIVATE_KEY_PATH=%s', TestHelper::PRIVATE_KEY_PATH));
        putenv(sprintf('PUBLIC_KEY_PATH=%s', TestHelper::PUBLIC_KEY_PATH));
        putenv(sprintf('ENCRYPTION_KEY=%s', TestHelper::ENCRYPTION_KEY));
    }
}
