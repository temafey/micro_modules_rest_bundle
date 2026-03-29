<?php

declare(strict_types=1);

use MicroModule\Rest\Api\ApiVersionManager;
use MicroModule\Rest\Api\ApiVersionRegistry;
use MicroModule\Rest\Api\ApiVersionResolver;
use MicroModule\Rest\Api\VersionedResponseBuilder;
use MicroModule\Rest\Listener\ApiVersionListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // --- API Version Resolver ---
    $services->set(ApiVersionResolver::class)
        ->args([
            '$supportedVersions' => param('micro_rest.versioning.supported_versions'),
            '$defaultVersion' => param('micro_rest.versioning.default_version'),
        ]);

    // --- API Version Registry ---
    $services->set(ApiVersionRegistry::class)
        ->args([
            '$versionsConfig' => param('micro_rest.versioning.versions'),
        ]);

    // --- API Version Manager ---
    $services->set(ApiVersionManager::class)
        ->args([
            '$resolver' => service(ApiVersionResolver::class),
            '$registry' => service(ApiVersionRegistry::class),
        ]);

    // --- Versioned Response Builder ---
    $services->set(VersionedResponseBuilder::class);

    // --- API Version Listener ---
    $services->set(ApiVersionListener::class)
        ->args([
            '$versionResolver' => service(ApiVersionResolver::class),
        ]);
};
