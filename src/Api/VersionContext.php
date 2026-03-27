<?php

declare(strict_types=1);

namespace MicroModule\Rest\Api;

use Symfony\Component\HttpFoundation\Request;

final readonly class VersionContext
{
    public function __construct(
        public string $version,
        public VersionInfo $info,
        public Request $request,
    ) {
    }

    /**
     * Check if current version supports a feature.
     */
    public function supportsFeature(string $feature): bool
    {
        return $this->info->supportsFeature($feature);
    }

    /**
     * Check if version is deprecated.
     */
    public function isDeprecated(): bool
    {
        return $this->info->deprecated;
    }

    /**
     * Get deprecation warning if applicable.
     */
    public function getDeprecationWarning(): ?string
    {
        return $this->info->deprecationMessage;
    }

    /**
     * Get migration path if available.
     */
    public function getMigrationPath(): ?string
    {
        return $this->info->migrationPath;
    }

    /**
     * Check if endpoint is removed in this version.
     */
    public function isEndpointRemoved(string $endpoint): bool
    {
        return $this->info->isEndpointRemoved($endpoint);
    }

    /**
     * Get base URL for version-specific links.
     */
    public function getVersionedBaseUrl(): string
    {
        $baseUrl = $this->request->getSchemeAndHttpHost() . $this->request->getBasePath();

        return sprintf('%s/api/%s', $baseUrl, $this->version);
    }

    /**
     * Build version-specific URL.
     */
    public function buildUrl(string $path, array $parameters = []): string
    {
        $url = $this->getVersionedBaseUrl() . '/' . ltrim($path, '/');

        if ($parameters !== []) {
            $url .= '?' . http_build_query($parameters);
        }

        return $url;
    }

    /**
     * Get version-specific headers to include in response.
     *
     * @return array<string, string>
     */
    public function getResponseHeaders(): array
    {
        $headers = [
            'X-API-Version' => $this->version,
        ];

        if ($this->info->deprecated) {
            $headers['X-API-Deprecated'] = 'true';

            if ($warning = $this->info->deprecationMessage) {
                $headers['X-API-Deprecation-Warning'] = $warning;
            }

            if ($migrationPath = $this->info->migrationPath) {
                $headers['X-API-Migration-Path'] = $migrationPath;
            }
        }

        if ($this->info->sunsetDate instanceof \DateTimeInterface) {
            $headers['X-API-Sunset'] = $this->info->sunsetDate->format('Y-m-d H:i:s');

            if ($days = $this->info->getDaysUntilSunset()) {
                $headers['X-API-Days-Until-Sunset'] = (string) $days;
            }
        }

        return $headers;
    }

    /**
     * Get version context as array for debugging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'deprecated' => $this->info->deprecated,
            'supported_features' => $this->info->supportedFeatures,
            'removed_endpoints' => $this->info->removedEndpoints,
            'versioned_base_url' => $this->getVersionedBaseUrl(),
            'response_headers' => $this->getResponseHeaders(),
        ];
    }
}
