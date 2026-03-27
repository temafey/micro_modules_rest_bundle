<?php

declare(strict_types=1);

namespace MicroModule\Rest\Api;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class ApiVersion
{
    /**
     * @param string|array<string> $versions   Supported API versions (e.g., "v1", "v2" or ["v1", "v2"])
     * @param bool                 $deprecated Whether this endpoint is deprecated
     */
    public function __construct(
        public string|array $versions,
        public bool $deprecated = false,
    ) {
    }

    /**
     * Check if a specific version is supported.
     */
    public function supports(string $version): bool
    {
        return is_array($this->versions)
            ? in_array($version, $this->versions, true)
            : $this->versions === $version;
    }
}
