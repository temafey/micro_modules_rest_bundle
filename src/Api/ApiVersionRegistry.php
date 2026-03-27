<?php

declare(strict_types=1);

namespace MicroModule\Rest\Api;

final class ApiVersionRegistry
{
    /**
     * @var array<string, VersionInfo>
     */
    private array $versions = [];

    /**
     * @param array<string, array<string, mixed>> $versionsConfig Version configuration
     */
    public function __construct(array $versionsConfig = [])
    {
        $this->loadVersions($versionsConfig);
    }

    /**
     * Get version information.
     */
    public function getVersionInfo(string $version): ?VersionInfo
    {
        return $this->versions[$version] ?? null;
    }

    /**
     * Check if version is deprecated.
     */
    public function isDeprecated(string $version): bool
    {
        $info = $this->getVersionInfo($version);

        return $info?->deprecated ?? false;
    }

    /**
     * Get all registered versions.
     *
     * @return array<string, VersionInfo>
     */
    public function getAllVersions(): array
    {
        return $this->versions;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCompatibilityMatrix(): array
    {
        $matrix = [];

        foreach ($this->versions as $version => $info) {
            $matrix[$version] = [
                'deprecated' => $info->deprecated,
                'supported_features' => $info->supportedFeatures,
                'removed_endpoints' => $info->removedEndpoints,
                'migration_path' => $info->migrationPath,
                'sunset_date' => $info->sunsetDate?->format('Y-m-d'),
            ];
        }

        return $matrix;
    }

    /**
     * Register a new version.
     */
    public function registerVersion(string $version, VersionInfo $info): self
    {
        $this->versions[$version] = $info;

        return $this;
    }

    /**
     * Load versions from configuration.
     *
     * @param array<string, array<string, mixed>> $versionsConfig
     */
    private function loadVersions(array $versionsConfig): void
    {
        foreach ($versionsConfig as $version => $config) {
            $this->versions[$version] = VersionInfo::fromArray($config);
        }

        // Default configurations if not provided
        if ($this->versions === []) {
            $this->loadDefaultVersions();
        }
    }

    /**
     * Load default version configurations.
     */
    private function loadDefaultVersions(): void
    {
        $this->versions = [
            'v1' => new VersionInfo(
                deprecated: false,
                supportedFeatures: [
                    'basic_crud' => true,
                    'status_updates' => true,
                    'task_assignment' => true,
                    'pagination' => true,
                    'priority_levels' => false,
                    'due_dates' => false,
                    'bulk_operations' => false,
                    'hateoas_links' => false,
                    'enhanced_responses' => false,
                ],
                removedEndpoints: []
            ),
            'v2' => new VersionInfo(
                deprecated: false,
                supportedFeatures: [
                    'basic_crud' => true,
                    'status_updates' => true,
                    'task_assignment' => true,
                    'pagination' => true,
                    'priority_levels' => true,
                    'due_dates' => true,
                    'bulk_operations' => true,
                    'hateoas_links' => true,
                    'enhanced_responses' => true,
                    'status_comments' => true,
                ],
                removedEndpoints: []
            ),
        ];
    }
}
