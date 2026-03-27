<?php

declare(strict_types=1);

namespace MicroModule\Rest\Traits;

use MicroModule\Rest\Filter\JsonPathFilterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Trait for applying JSONPath filtering to API responses.
 *
 * Enables RFC 9535 JSONPath filtering via query parameter.
 *
 * Usage:
 * GET /api/v1/news?filter=$.items[?(@.status == "published")]
 * GET /api/v1/news?filter=$[?(@.publishedAt > "2024-01-01")]
 * GET /api/v1/news?filter=$..title
 */
trait JsonPathFilterTrait
{
    protected ?JsonPathFilterInterface $jsonPathFilter = null;

    /**
     * Set the JSONPath filter service.
     *
     * @required
     */
    public function setJsonPathFilter(JsonPathFilterInterface $jsonPathFilter): void
    {
        $this->jsonPathFilter = $jsonPathFilter;
    }

    /**
     * Apply JSONPath filter to data if filter parameter is present in request.
     *
     * @param Request      $request The HTTP request
     * @param array<mixed> $data    The data to filter
     *
     * @return array<mixed> Filtered data (or original if no filter specified)
     */
    protected function applyJsonPathFilter(Request $request, array $data): array
    {
        $filterPath = $request->query->getString('filter', '');

        if ($filterPath === '' || $this->jsonPathFilter === null) {
            return $data;
        }

        // Wrap in root object if data is a list
        $isSequential = array_is_list($data);
        $wrappedData = $isSequential ? [
            'items' => $data,
        ] : $data;

        // Apply the filter
        $filteredData = $this->jsonPathFilter->filter($wrappedData, $filterPath);

        return $filteredData;
    }

    /**
     * Check if the request has a JSONPath filter.
     */
    protected function hasJsonPathFilter(Request $request): bool
    {
        return $request->query->has('filter') && $request->query->getString('filter') !== '';
    }
}
