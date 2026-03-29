<?php

declare(strict_types=1);

use MicroModule\Rest\Listener\CsrfTokenValidationListener;
use MicroModule\Rest\Security\StatelessCsrfTokenService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // --- Stateless CSRF Token Service ---
    $services->set(StatelessCsrfTokenService::class);

    // --- CSRF Validation Listener ---
    $services->set(CsrfTokenValidationListener::class)
        ->args([
            '$enabled' => param('micro_rest.csrf.enabled'),
        ]);
};
