<?php

declare(strict_types=1);

use MicroModule\Rest\Listener\ProcessUuidListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(ProcessUuidListener::class);
};
