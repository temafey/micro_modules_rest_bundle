<?php

declare(strict_types=1);

namespace MicroModule\Rest\Api;

use Symfony\Component\HttpFoundation\Request;

final readonly class ApiVersionManager
{
    /**
     * @param ApiVersionResolver $resolver Version resolver service
     * @param ApiVersionRegistry $registry Version registry service
     */
    public function __construct(
        private ApiVersionResolver $resolver,
        private ApiVersionRegistry $registry,
    ) {
    }

    /**
     * Resolve and validate API version from request.
     */
    public function resolveVersion(Request $request): VersionContext
    {
        $version = $this->resolver->resolve($request);
        $versionInfo = $this->registry->getVersionInfo($version);

        if (! $versionInfo instanceof VersionInfo) {
            throw new ApiVersionException('Unsupported API version: ' . $version);
        }

        return new VersionContext(version: $version, info: $versionInfo, request: $request);
    }

    /**
     * Check if a version is deprecated.
     */
    public function isDeprecated(string $version): bool
    {
        return $this->registry->isDeprecated($version);
    }

    /**
     * Get deprecation warning for a version.
     */
    public function getDeprecationWarning(string $version): ?string
    {
        $versionInfo = $this->registry->getVersionInfo($version);

        return $versionInfo?->deprecationMessage;
    }

    /**
     * Get migration path for a deprecated version.
     */
    public function getMigrationPath(string $version): ?string
    {
        $versionInfo = $this->registry->getVersionInfo($version);

        return $versionInfo?->migrationPath;
    }

    /**
     * Get all supported versions.
     *
     * @return array<string>
     */
    public function getSupportedVersions(): array
    {
        return $this->resolver->getSupportedVersions();
    }

    public function getLatestVersion(): string
    {
        return $this->resolver->getLatestVersion();
    }

    /**
     * Check if version supports a specific feature.
     */
    public function supportsFeature(string $version, string $feature): bool
    {
        $versionInfo = $this->registry->getVersionInfo($version);

        return $versionInfo?->supportedFeatures[$feature] ?? false;
    }

    /**
     * Get version compatibility matrix.
     *
     * @return array<string, mixed>
     */
    public function getCompatibilityMatrix(): array
    {
        return $this->registry->getCompatibilityMatrix();
    }

    /**
     * Validate version compatibility for endpoint.
     */
    public function validateEndpointCompatibility(string $version, string $endpoint): bool
    {
        $versionInfo = $this->registry->getVersionInfo($version);

        if (! $versionInfo instanceof VersionInfo) {
            return false;
        }

        return ! in_array($endpoint, $versionInfo->removedEndpoints, true);
    }
}
