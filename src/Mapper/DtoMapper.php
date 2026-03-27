<?php

declare(strict_types=1);

namespace MicroModule\Rest\Mapper;

use MicroModule\Base\Application\Dto\DtoInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

/**
 * DTO Mapper service for transforming domain objects to DTOs.
 *
 * Wraps Symfony's ObjectMapper with domain-specific functionality
 * for mapping ReadModels, Entities, and other domain objects to DTOs.
 */
final readonly class DtoMapper implements DtoMapperInterface
{
    public function __construct(
        private ObjectMapperInterface $objectMapper,
    ) {
    }

    public function map(object $source, string $targetClass): DtoInterface
    {
        /** @var DtoInterface */
        return $this->objectMapper->map($source, $targetClass);
    }

    public function mapToExisting(object $source, DtoInterface $target): DtoInterface
    {
        /** @var DtoInterface */
        return $this->objectMapper->map($source, $target);
    }

    public function mapCollection(iterable $sources, string $targetClass): array
    {
        $result = [];

        foreach ($sources as $source) {
            $result[] = $this->map($source, $targetClass);
        }

        return $result;
    }
}
