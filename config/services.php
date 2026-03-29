<?php

declare(strict_types=1);

use MicroModule\Rest\Controller\AbstractApiController;
use MicroModule\Rest\Controller\UnifiedApiController;
use MicroModule\Rest\Filter\JsonPathFilter;
use MicroModule\Rest\Filter\JsonPathFilterInterface;
use MicroModule\Rest\Mapper\DtoMapper;
use MicroModule\Rest\Mapper\DtoMapperInterface;
use MicroModule\Rest\Mapper\Transform\DateTimeToIso8601Transform;
use MicroModule\Rest\Mapper\Transform\StringToDateTimeTransform;
use MicroModule\Rest\Mapper\Transform\UuidToStringTransform;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // --- DTO Mapper ---
    $services->set(DtoMapper::class)
        ->args([
            '$objectMapper' => service('object_mapper'),
        ]);

    $services->alias(DtoMapperInterface::class, DtoMapper::class)
        ->public();

    // --- Transform Callables ---
    $services->set(UuidToStringTransform::class)
        ->tag('object_mapper.transform_callable');

    $services->set(DateTimeToIso8601Transform::class)
        ->tag('object_mapper.transform_callable');

    $services->set(StringToDateTimeTransform::class)
        ->tag('object_mapper.transform_callable');

    // --- JSON Path Filter ---
    $services->set(JsonPathFilter::class);

    $services->alias(JsonPathFilterInterface::class, JsonPathFilter::class)
        ->public();

    // --- Base Controllers ---
    // AbstractApiController and UnifiedApiController are abstract — concrete subclasses
    // in the consuming project will be registered via their own service loader.
    // We only need to ensure the bundle's traits and filter are available.
};
