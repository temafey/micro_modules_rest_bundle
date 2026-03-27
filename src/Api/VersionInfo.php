<?php

declare(strict_types=1);

namespace MicroModule\Rest\Api;

final readonly class VersionInfo
{
    /**
     * @param bool                    $deprecated         Whether this version is deprecated
     * @param array<string, bool>     $supportedFeatures  Features supported by this version
     * @param array<string>           $removedEndpoints   Endpoints removed in this version
     * @param string|null             $deprecationMessage Message to show when using deprecated version
     * @param string|null             $migrationPath      Documentation or URL for migration guide
     * @param \DateTimeInterface|null $sunsetDate         When this version will be sunset
     */
    public function __construct(
        public bool $deprecated = false,
        public array $supportedFeatures = [],
        public array $removedEndpoints = [],
        public ?string $deprecationMessage = null,
        public ?string $migrationPath = null,
        public ?\DateTimeInterface $sunsetDate = null,
    ) {
    }

    /**
     * Create from array configuration.
     *
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            deprecated: $config['deprecated'] ?? false,
            supportedFeatures: $config['supported_features'] ?? [],
            removedEndpoints: $config['removed_endpoints'] ?? [],
            deprecationMessage: $config['deprecation_message'] ?? null,
            migrationPath: $config['migration_path'] ?? null,
            sunsetDate: isset($config['sunset_date'])
                ? new \DateTimeImmutable($config['sunset_date'])
                : null
        );
    }

    /**
     * Check if feature is supported.
     */
    public function supportsFeature(string $feature): bool
    {
        return $this->supportedFeatures[$feature] ?? false;
    }

    /**
     * Check if endpoint is removed.
     */
    public function isEndpointRemoved(string $endpoint): bool
    {
        return in_array($endpoint, $this->removedEndpoints, true);
    }

    /**
     * Check if version has sunset date in the past.
     */
    public function isSunset(): bool
    {
        if (! $this->sunsetDate instanceof \DateTimeInterface) {
            return false;
        }

        return $this->sunsetDate < new \DateTimeImmutable();
    }

    public function getDaysUntilSunset(): ?int
    {
        if (! $this->sunsetDate instanceof \DateTimeInterface) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $diff = $this->sunsetDate->diff($now);

        return $diff->invert ? $diff->days : -$diff->days;
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'deprecated' => $this->deprecated,
            'supported_features' => $this->supportedFeatures,
            'removed_endpoints' => $this->removedEndpoints,
            'deprecation_message' => $this->deprecationMessage,
            'migration_path' => $this->migrationPath,
            'sunset_date' => $this->sunsetDate?->format('Y-m-d H:i:s'),
            'is_sunset' => $this->isSunset(),
            'days_until_sunset' => $this->getDaysUntilSunset(),
        ];
    }
}
