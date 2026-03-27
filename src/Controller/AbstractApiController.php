<?php

declare(strict_types=1);

namespace MicroModule\Rest\Controller;

use MicroModule\Rest\Api\ApiVersion;
use MicroModule\Rest\Traits\PaginationTrait;
use MicroModule\Rest\Traits\ResourceLinksTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[ApiVersion(['v1', 'v2'])]
abstract class AbstractApiController extends AbstractController
{
    use PaginationTrait;
    use ResourceLinksTrait;

    /**
     * Get current API version from request.
     */
    protected function getApiVersion(Request $request): string
    {
        return $request->attributes->get('_api_version', 'v1');
    }

    /**
     * Create a success response with standardized format.
     *
     * @param mixed $data       Response data
     * @param int   $statusCode HTTP status code
     */
    protected function createApiResponse(mixed $data, int $statusCode = 200): JsonResponse
    {
        return new JsonResponse(
            [
                'data' => $data,
                'status' => 'success',
            ],
            $statusCode
        );
    }

    /**
     * Create an error response with standardized format.
     *
     * @param string               $message    Error message
     * @param int                  $statusCode HTTP status code
     * @param array<string, mixed> $errors     Additional error details
     */
    protected function createApiErrorResponse(
        string $message,
        int $statusCode = 400,
        array $errors = [],
    ): JsonResponse {
        return new JsonResponse(
            [
                'status' => 'error',
                'message' => $message,
                'errors' => $errors,
            ],
            $statusCode
        );
    }
}
