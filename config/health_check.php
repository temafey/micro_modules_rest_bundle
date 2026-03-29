<?php

declare(strict_types=1);

use MicroModule\Rest\Controller\HealthCheckController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(HealthCheckController::class)
        ->tag('controller.service_arguments');
};
