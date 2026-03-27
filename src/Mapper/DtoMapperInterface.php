<?php

declare(strict_types=1);

namespace MicroModule\Rest\Mapper;

use MicroModule\Base\Application\Dto\DtoInterface;

/**
 * Interface for DTO mapping operations.
 *
 * Provides methods to transform domain objects to DTOs
 * using Symfony's ObjectMapper component.
 */
interface DtoMapperInterface
{
    /**
     * Map a source object to a new DTO instance.
     *
     * @template T of DtoInterface
     *
     * @param object          $source      The source object to map from
     * @param class-string<T> $targetClass The target DTO class
     *
     * @return T The mapped DTO
     */
    public function map(object $source, string $targetClass): DtoInterface;

    /**
     * Map a source object to an existing DTO instance.
     *
     * @param object       $source The source object to map from
     * @param DtoInterface $target The existing target DTO to map to
     *
     * @return DtoInterface The mapped DTO
     */
    public function mapToExisting(object $source, DtoInterface $target): DtoInterface;

    /**
     * Map a collection of source objects to DTOs.
     *
     * @template T of DtoInterface
     *
     * @param iterable<object> $sources     The source objects to map
     * @param class-string<T>  $targetClass The target DTO class
     *
     * @return array<T> Array of mapped DTOs
     */
    public function mapCollection(iterable $sources, string $targetClass): array;
}
