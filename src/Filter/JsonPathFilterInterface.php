<?php

declare(strict_types=1);

namespace MicroModule\Rest\Filter;

/**
 * Interface for JSONPath-based result filtering.
 *
 * Uses RFC 9535 JSONPath syntax to filter and extract data from query results.
 */
interface JsonPathFilterInterface
{
    /**
     * Filter data using a JSONPath expression.
     *
     * @param array<mixed> $data The data to filter
     * @param string       $path JSONPath expression (e.g., "$.items[?(@.status == 'active')]")
     *
     * @return array<mixed> Filtered results
     */
    public function filter(array $data, string $path): array;

    /**
     * Extract a single value using a JSONPath expression.
     *
     * @param array<mixed> $data The data to query
     * @param string       $path JSONPath expression
     *
     * @return mixed First matching value or null
     */
    public function findFirst(array $data, string $path): mixed;

    /**
     * Check if data matches a JSONPath expression.
     *
     * @param array<mixed> $data The data to check
     * @param string       $path JSONPath expression
     *
     * @return bool True if the path matches at least one element
     */
    public function matches(array $data, string $path): bool;

    /**
     * Count matching elements for a JSONPath expression.
     *
     * @param array<mixed> $data The data to query
     * @param string       $path JSONPath expression
     *
     * @return int Number of matching elements
     */
    public function count(array $data, string $path): int;
}
