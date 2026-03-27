<?php

declare(strict_types=1);

namespace MicroModule\Rest\Api;

use Symfony\Component\HttpFoundation\Request;

final readonly class ApiVersionResolver
{
    /**
     * @param array<string> $supportedVersions List of supported API versions
     * @param string        $defaultVersion    Default API version to use
     */
    public function __construct(
        private array $supportedVersions = ['v1', 'v2'],
        private string $defaultVersion = 'v1',
    ) {
    }

    /**
     * Resolve API version from the request.
     */
    public function resolve(Request $request): string
    {
        // Try path-based versioning (preferred)
        if (preg_match('#^/api/(v\d+)/#', $request->getPathInfo(), $matches)) {
            $version = $matches[1];

            return $this->isSupported($version) ? $version : $this->defaultVersion;
        }

        // Try header-based versioning (fallback)
        $headerVersion = $request->headers->get('X-API-Version');
        if ($headerVersion && $this->isSupported($headerVersion)) {
            return $headerVersion;
        }

        // Default version
        return $this->defaultVersion;
    }

    /**
     * Check if a version is supported.
     */
    public function isSupported(string $version): bool
    {
        return in_array($version, $this->supportedVersions, true);
    }

    /**
     * Get the latest supported version.
     */
    public function getLatestVersion(): string
    {
        return $this->supportedVersions[array_key_last($this->supportedVersions)] ?? $this->defaultVersion;
    }

    /**
     * Get the default version.
     */
    public function getDefaultVersion(): string
    {
        return $this->defaultVersion;
    }

    /**
     * Get all supported versions.
     *
     * @return array<string>
     */
    public function getSupportedVersions(): array
    {
        return $this->supportedVersions;
    }
}
