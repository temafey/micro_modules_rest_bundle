<?php

declare(strict_types=1);

namespace MicroModule\Rest\Filter;

use Symfony\Component\JsonPath\JsonCrawler;

/**
 * JSONPath-based filter implementation using Symfony's JsonCrawler.
 *
 * Provides RFC 9535 JSONPath filtering capabilities for query results.
 */
final readonly class JsonPathFilter implements JsonPathFilterInterface
{
    public function filter(array $data, string $path): array
    {
        if ($data === []) {
            return [];
        }

        $jsonString = json_encode($data, JSON_THROW_ON_ERROR);
        $crawler = new JsonCrawler($jsonString);

        return $crawler->find($path);
    }

    public function findFirst(array $data, string $path): mixed
    {
        $results = $this->filter($data, $path);

        return $results[0] ?? null;
    }

    public function matches(array $data, string $path): bool
    {
        return $this->count($data, $path) > 0;
    }

    public function count(array $data, string $path): int
    {
        return count($this->filter($data, $path));
    }
}
