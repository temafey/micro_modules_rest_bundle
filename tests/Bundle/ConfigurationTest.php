<?php

declare(strict_types=1);

namespace MicroModule\Rest\Tests\Bundle;

use MicroModule\Rest\RestBundle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    #[Test]
    public function defaultConfigurationIsApplied(): void
    {
        $config = $this->processConfiguration([]);

        self::assertTrue($config['versioning']['enabled']);
        self::assertSame(['v1', 'v2'], $config['versioning']['supported_versions']);
        self::assertSame('v1', $config['versioning']['default_version']);
        self::assertSame([], $config['versioning']['versions']);

        self::assertFalse($config['csrf']['enabled']);

        self::assertTrue($config['listeners']['domain_exception']);
        self::assertTrue($config['listeners']['trailing_slash_redirect']);
        self::assertTrue($config['listeners']['process_uuid']);
        self::assertTrue($config['listeners']['remove_null_defaults']);

        self::assertTrue($config['health_check']['enabled']);
    }

    #[Test]
    public function customVersionsListIsParsedCorrectly(): void
    {
        $config = $this->processConfiguration([
            [
                'versioning' => [
                    'supported_versions' => ['v1', 'v2', 'v3'],
                    'default_version' => 'v2',
                    'versions' => [
                        'v1' => [
                            'deprecated' => true,
                            'deprecated_at' => '2026-06-01',
                            'sunset_at' => '2027-01-01',
                        ],
                        'v2' => [],
                        'v3' => [
                            'supported_features' => ['bulk_ops', 'streaming'],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame(['v1', 'v2', 'v3'], $config['versioning']['supported_versions']);
        self::assertSame('v2', $config['versioning']['default_version']);

        $v1 = $config['versioning']['versions']['v1'];
        self::assertTrue($v1['deprecated']);
        self::assertSame('2026-06-01', $v1['deprecated_at']);
        self::assertSame('2027-01-01', $v1['sunset_at']);

        $v3 = $config['versioning']['versions']['v3'];
        self::assertSame(['bulk_ops', 'streaming'], $v3['supported_features']);
    }

    #[Test]
    public function allFeaturesCanBeDisabled(): void
    {
        $config = $this->processConfiguration([
            [
                'versioning' => ['enabled' => false],
                'csrf' => ['enabled' => false],
                'listeners' => [
                    'domain_exception' => false,
                    'trailing_slash_redirect' => false,
                    'process_uuid' => false,
                    'remove_null_defaults' => false,
                ],
                'health_check' => ['enabled' => false],
            ],
        ]);

        self::assertFalse($config['versioning']['enabled']);
        self::assertFalse($config['csrf']['enabled']);
        self::assertFalse($config['listeners']['domain_exception']);
        self::assertFalse($config['listeners']['trailing_slash_redirect']);
        self::assertFalse($config['listeners']['process_uuid']);
        self::assertFalse($config['listeners']['remove_null_defaults']);
        self::assertFalse($config['health_check']['enabled']);
    }

    /**
     * @param array<int, array<string, mixed>> $configs
     * @return array<string, mixed>
     */
    private function processConfiguration(array $configs): array
    {
        $bundle = new RestBundle();
        $treeBuilder = new TreeBuilder('micro_rest');
        $definition = new DefinitionConfigurator($treeBuilder);
        $bundle->configure($definition);

        $processor = new Processor();

        return $processor->process($treeBuilder->buildTree(), $configs);
    }
}
