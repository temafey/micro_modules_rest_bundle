<?php

declare(strict_types=1);

namespace MicroModule\Rest\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class VersionedResponseBuilder
{
    public function __construct(
        private VersionContext $context,
    ) {
    }

    /**
     * Create a success response with version-appropriate formatting.
     *
     * @param mixed                 $data       Response data
     * @param int                   $statusCode HTTP status code
     * @param array<string, string> $headers    Additional headers
     */
    public function success(mixed $data, int $statusCode = 200, array $headers = []): JsonResponse
    {
        $responseData = $this->formatResponseData($data);
        $responseHeaders = array_merge($this->context->getResponseHeaders(), $headers);

        return new JsonResponse($responseData, $statusCode, $responseHeaders);
    }

    /**
     * Create an error response with version-appropriate formatting.
     *
     * @param string                $message    Error message
     * @param int                   $statusCode HTTP status code
     * @param array<string, mixed>  $errors     Additional error details
     * @param array<string, string> $headers    Additional headers
     */
    public function error(
        string $message,
        int $statusCode = 400,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        $responseData = $this->formatErrorData($message, $errors);
        $responseHeaders = array_merge($this->context->getResponseHeaders(), $headers);

        return new JsonResponse($responseData, $statusCode, $responseHeaders);
    }

    /**
     * Create a paginated response.
     *
     * @param mixed                 $data       Response data
     * @param array<string, mixed>  $pagination Pagination metadata
     * @param array<string, string> $links      Pagination links
     * @param array<string, string> $headers    Additional headers
     */
    public function paginated(
        mixed $data,
        array $pagination,
        array $links = [],
        array $headers = [],
    ): JsonResponse {
        $responseData = $this->formatPaginatedData($data, $pagination, $links);
        $responseHeaders = array_merge($this->context->getResponseHeaders(), $headers);

        return new JsonResponse($responseData, Response::HTTP_OK, $responseHeaders);
    }

    /**
     * Create a resource response with HATEOAS links.
     *
     * @param mixed                 $data       Resource data
     * @param array<string, string> $links      Resource links
     * @param int                   $statusCode HTTP status code
     * @param array<string, string> $headers    Additional headers
     */
    public function resource(
        mixed $data,
        array $links = [],
        int $statusCode = 200,
        array $headers = [],
    ): JsonResponse {
        $responseData = $this->formatResourceData($data, $links);
        $responseHeaders = array_merge($this->context->getResponseHeaders(), $headers);

        return new JsonResponse($responseData, $statusCode, $responseHeaders);
    }

    /**
     * Create a collection response.
     *
     * @param array<mixed>          $items   Collection items
     * @param array<string, mixed>  $meta    Collection metadata
     * @param array<string, string> $links   Collection links
     * @param array<string, string> $headers Additional headers
     */
    public function collection(
        array $items,
        array $meta = [],
        array $links = [],
        array $headers = [],
    ): JsonResponse {
        $responseData = $this->formatCollectionData($items, $meta, $links);
        $responseHeaders = array_merge($this->context->getResponseHeaders(), $headers);

        return new JsonResponse($responseData, Response::HTTP_OK, $responseHeaders);
    }

    /**
     * Format response data based on version.
     *
     * @return array<string, mixed>
     */
    private function formatResponseData(mixed $data): array
    {
        if ($this->context->supportsFeature('enhanced_responses')) {
            return [
                'data' => $data,
                'status' => 'success',
                'version' => $this->context->version,
                'timestamp' => date('c'),
            ];
        }

        // V1 simple format
        return [
            'data' => $data,
            'status' => 'success',
        ];
    }

    /**
     * Format error data based on version.
     *
     * @param array<string, mixed> $errors
     *
     * @return array<string, mixed>
     */
    private function formatErrorData(string $message, array $errors = []): array
    {
        $errorData = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== []) {
            $errorData['errors'] = $errors;
        }

        if ($this->context->supportsFeature('enhanced_responses')) {
            $errorData['version'] = $this->context->version;
            $errorData['timestamp'] = date('c');

            if ($this->context->isDeprecated()) {
                $errorData['deprecation_warning'] = $this->context->getDeprecationWarning();
            }
        }

        return $errorData;
    }

    /**
     * Format paginated data based on version.
     *
     * @param array<string, mixed>  $pagination
     * @param array<string, string> $links
     *
     * @return array<string, mixed>
     */
    private function formatPaginatedData(mixed $data, array $pagination, array $links): array
    {
        $response = [
            'data' => $data,
            'status' => 'success',
            'pagination' => $pagination,
        ];

        if ($links !== []) {
            $response['links'] = $links;
        }

        if ($this->context->supportsFeature('enhanced_responses')) {
            $response['version'] = $this->context->version;
            $response['timestamp'] = date('c');
        }

        return $response;
    }

    /**
     * Format resource data with HATEOAS links.
     *
     * @param array<string, string> $links
     *
     * @return array<string, mixed>
     */
    private function formatResourceData(mixed $data, array $links): array
    {
        $response = $this->formatResponseData($data);

        if ($this->context->supportsFeature('hateoas_links') && $links !== []) {
            if (isset($response['data']) && is_array($response['data'])) {
                $response['data']['links'] = $links;
            } else {
                $response['links'] = $links;
            }
        }

        return $response;
    }

    /**
     * @param array<mixed>          $items
     * @param array<string, mixed>  $meta
     * @param array<string, string> $links
     *
     * @return array<string, mixed>
     */
    private function formatCollectionData(array $items, array $meta, array $links): array
    {
        $response = [
            'data' => $items,
            'status' => 'success',
        ];

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        if ($this->context->supportsFeature('hateoas_links') && $links !== []) {
            $response['links'] = $links;
        }

        if ($this->context->supportsFeature('enhanced_responses')) {
            $response['version'] = $this->context->version;
            $response['timestamp'] = date('c');
            $response['count'] = count($items);
        }

        return $response;
    }
}
