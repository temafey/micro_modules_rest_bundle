<?php

declare(strict_types=1);

namespace MicroModule\Rest\Tests\Bundle;

use MicroModule\Rest\Api\ApiVersionListener;
use MicroModule\Rest\Api\ApiVersionManager;
use MicroModule\Rest\Api\ApiVersionRegistry;
use MicroModule\Rest\Api\ApiVersionResolver;
use MicroModule\Rest\Api\RemoveNullDefaultsSubscriber;
use MicroModule\Rest\Api\VersionedResponseBuilder;
use MicroModule\Rest\Controller\HealthCheckController;
use MicroModule\Rest\Filter\JsonPathFilter;
use MicroModule\Rest\Filter\JsonPathFilterInterface;
use MicroModule\Rest\Listener\CsrfTokenValidationListener;
use MicroModule\Rest\Listener\DomainExceptionListener;
use MicroModule\Rest\Listener\ProcessUuidListener;
use MicroModule\Rest\Listener\TrailingSlashRedirectListener;
use MicroModule\Rest\Mapper\DtoMapper;
use MicroModule\Rest\Mapper\DtoMapperInterface;
use MicroModule\Rest\RestBundle;
use MicroModule\Rest\Security\StatelessCsrfTokenService;
use Nyholm\BundleTest\TestKernel;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

final class BundleInitializationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(RestBundle::class);
        $kernel->handleOptions($options);

        return $kernel;
    }

    #[Test]
    public function bundleBootsWithDefaultConfig(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(__DIR__ . '/config/default.yaml');
        }]);

        $container = self::getContainer();

        // Core services should always be available
        self::assertTrue($container->has(DtoMapper::class));
        self::assertTrue($container->has(DtoMapperInterface::class));
        self::assertTrue($container->has(JsonPathFilter::class));
        self::assertTrue($container->has(JsonPathFilterInterface::class));

        // Versioning enabled by default
        self::assertTrue($container->has(ApiVersionResolver::class));
        self::assertTrue($container->has(ApiVersionRegistry::class));
        self::assertTrue($container->has(ApiVersionManager::class));
        self::assertTrue($container->has(VersionedResponseBuilder::class));

        // Listeners enabled by default
        self::assertTrue($container->has(DomainExceptionListener::class));
        self::assertTrue($container->has(TrailingSlashRedirectListener::class));
        self::assertTrue($container->has(ProcessUuidListener::class));
        self::assertTrue($container->has(RemoveNullDefaultsSubscriber::class));

        // Health check enabled by default
        self::assertTrue($container->has(HealthCheckController::class));
    }

    #[Test]
    public function versioningDisabledDoesNotRegisterVersioningServices(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(__DIR__ . '/config/versioning_disabled.yaml');
        }]);

        $container = self::getContainer();

        // Core services should still be available
        self::assertTrue($container->has(DtoMapper::class));
        self::assertTrue($container->has(JsonPathFilter::class));

        // Versioning services should NOT be registered
        self::assertFalse($container->has(ApiVersionResolver::class));
        self::assertFalse($container->has(ApiVersionRegistry::class));
        self::assertFalse($container->has(ApiVersionManager::class));
        self::assertFalse($container->has(ApiVersionListener::class));
    }

    #[Test]
    public function csrfEnabledRegistersSecurityServices(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(__DIR__ . '/config/csrf_enabled.yaml');
        }]);

        $container = self::getContainer();

        // CSRF services should be registered
        self::assertTrue($container->has(StatelessCsrfTokenService::class));
        self::assertTrue($container->has(CsrfTokenValidationListener::class));
    }

    #[Test]
    public function allListenersDisabledRegistersNoListeners(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(__DIR__ . '/config/listeners_disabled.yaml');
        }]);

        $container = self::getContainer();

        // Core services should still be available
        self::assertTrue($container->has(DtoMapper::class));

        // No listener services
        self::assertFalse($container->has(DomainExceptionListener::class));
        self::assertFalse($container->has(TrailingSlashRedirectListener::class));
        self::assertFalse($container->has(ProcessUuidListener::class));
        self::assertFalse($container->has(RemoveNullDefaultsSubscriber::class));
    }

    #[Test]
    public function healthCheckDisabledDoesNotRegisterController(): void
    {
        self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(__DIR__ . '/config/health_check_disabled.yaml');
        }]);

        $container = self::getContainer();

        self::assertFalse($container->has(HealthCheckController::class));
    }
}
