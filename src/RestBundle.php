<?php

declare(strict_types=1);

namespace MicroModule\Rest;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Symfony bundle for reusable REST API infrastructure.
 *
 * Provides API versioning, base controllers, DTO mapping, JSON path filtering,
 * event listeners, CSRF protection, and pagination/HATEOAS traits.
 *
 * Configuration example:
 *   micro_rest:
 *     versioning:
 *       enabled: true
 *       supported_versions: [v1, v2]
 *       default_version: v1
 *       versions:
 *         v1: { deprecated: false }
 *         v2: { deprecated: false }
 *     csrf:
 *       enabled: false
 *     listeners:
 *       domain_exception: true
 *       trailing_slash_redirect: true
 *       process_uuid: true
 *       remove_null_defaults: true
 *     health_check:
 *       enabled: true
 */
final class RestBundle extends AbstractBundle
{
    protected string $extensionAlias = 'micro_rest';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('versioning')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Enable API versioning services (resolver, registry, manager, listener)')
                        ->end()
                        ->arrayNode('supported_versions')
                            ->scalarPrototype()->end()
                            ->defaultValue(['v1', 'v2'])
                            ->info('List of supported API version identifiers')
                        ->end()
                        ->scalarNode('default_version')
                            ->defaultValue('v1')
                            ->info('Default API version when none is specified')
                        ->end()
                        ->arrayNode('versions')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->booleanNode('deprecated')->defaultFalse()->end()
                                    ->scalarNode('deprecated_at')->defaultNull()->end()
                                    ->scalarNode('sunset_at')->defaultNull()->end()
                                    ->scalarNode('migration_path')->defaultNull()->end()
                                    ->arrayNode('supported_features')
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()
                                    ->arrayNode('removed_endpoints')
                                        ->scalarPrototype()->end()
                                        ->defaultValue([])
                                    ->end()
                                ->end()
                            ->end()
                            ->defaultValue([])
                            ->info('Per-version configuration with deprecation dates and feature flags')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('csrf')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                            ->info('Enable CSRF protection services (disabled by default for API-only services)')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('listeners')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('domain_exception')
                            ->defaultTrue()
                            ->info('Register DomainExceptionListener to convert domain exceptions to JSON responses')
                        ->end()
                        ->booleanNode('trailing_slash_redirect')
                            ->defaultTrue()
                            ->info('Register TrailingSlashRedirectListener for API URL normalization')
                        ->end()
                        ->booleanNode('process_uuid')
                            ->defaultTrue()
                            ->info('Register ProcessUuidListener to ensure every request has a process_uuid')
                        ->end()
                        ->booleanNode('remove_null_defaults')
                            ->defaultTrue()
                            ->info('Register RemoveNullDefaultsSubscriber for OpenAPI schema cleanup')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('health_check')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Register HealthCheckController with /health endpoints')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Always load core services (mapper, filter, base controllers, traits)
        $container->import('../config/services.php');

        // Set parameters for downstream use
        $container->parameters()->set('micro_rest.versioning.supported_versions', $config['versioning']['supported_versions']);
        $container->parameters()->set('micro_rest.versioning.default_version', $config['versioning']['default_version']);
        $container->parameters()->set('micro_rest.versioning.versions', $config['versioning']['versions']);
        $container->parameters()->set('micro_rest.csrf.enabled', $config['csrf']['enabled']);

        // Conditionally load versioning services
        if ($config['versioning']['enabled']) {
            $container->import('../config/versioning.php');
        }

        // Conditionally load CSRF services
        if ($config['csrf']['enabled'] && class_exists(\Symfony\Component\Security\Csrf\CsrfTokenManagerInterface::class)) {
            $container->import('../config/csrf.php');
        }

        // Conditionally load individual listeners
        if ($config['listeners']['domain_exception']) {
            $container->import('../config/listeners/domain_exception.php');
        }

        if ($config['listeners']['trailing_slash_redirect']) {
            $container->import('../config/listeners/trailing_slash_redirect.php');
        }

        if ($config['listeners']['process_uuid'] && class_exists(\Ramsey\Uuid\Uuid::class)) {
            $container->import('../config/listeners/process_uuid.php');
        }

        if ($config['listeners']['remove_null_defaults']) {
            $container->import('../config/listeners/remove_null_defaults.php');
        }

        // Conditionally load health check controller
        if ($config['health_check']['enabled'] && class_exists(\Doctrine\DBAL\Connection::class)) {
            $container->import('../config/health_check.php');
        }
    }
}
