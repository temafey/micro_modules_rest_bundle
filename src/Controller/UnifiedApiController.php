<?php

declare(strict_types=1);

namespace MicroModule\Rest\Controller;

use MicroModule\Rest\Api\ApiVersion;
use MicroModule\Rest\Api\ApiVersionException;
use MicroModule\Rest\Api\ApiVersionManager;
use MicroModule\Rest\Api\VersionContext;
use MicroModule\Rest\Api\VersionedResponseBuilder;
use MicroModule\Rest\Traits\PaginationTrait;
use MicroModule\Rest\Traits\ResourceLinksTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[ApiVersion(['v1', 'v2'])]
abstract class UnifiedApiController extends AbstractController
{
    use PaginationTrait;
    use ResourceLinksTrait;

    private ?VersionContext $versionContext = null;

    private ?VersionedResponseBuilder $responseBuilder = null;

    /**
     * @param ApiVersionManager $versionManager Version manager service
     */
    public function __construct(
        private readonly ApiVersionManager $versionManager,
    ) {
    }

    /**
     * Get version context for current request.
     */
    protected function getVersionContext(Request $request): VersionContext
    {
        if (! $this->versionContext instanceof VersionContext) {
            try {
                $this->versionContext = $this->versionManager->resolveVersion($request);
            } catch (ApiVersionException $e) {
                throw new NotFoundHttpException($e->getMessage());
            }
        }

        return $this->versionContext;
    }

    /**
     * Get version-aware response builder.
     */
    protected function getResponseBuilder(Request $request): VersionedResponseBuilder
    {
        if (! $this->responseBuilder instanceof VersionedResponseBuilder) {
            $context = $this->getVersionContext($request);
            $this->responseBuilder = new VersionedResponseBuilder($context);
        }

        return $this->responseBuilder;
    }

    /**
     * Get current API version from request.
     */
    protected function getApiVersion(Request $request): string
    {
        return $this->getVersionContext($request)
            ->version;
    }

    /**
     * Check if current version supports a feature.
     */
    protected function supportsFeature(Request $request, string $feature): bool
    {
        return $this->getVersionContext($request)
            ->supportsFeature($feature);
    }

    /**
     * Check if current version is deprecated.
     */
    protected function isVersionDeprecated(Request $request): bool
    {
        return $this->getVersionContext($request)
            ->isDeprecated();
    }

    /**
     * Create a success response with version-appropriate formatting.
     *
     * @param Request               $request    Current request
     * @param mixed                 $data       Response data
     * @param int                   $statusCode HTTP status code
     * @param array<string, string> $headers    Additional headers
     */
    protected function createApiResponse(
        Request $request,
        mixed $data,
        int $statusCode = 200,
        array $headers = [],
    ): JsonResponse {
        return $this->getResponseBuilder($request)
            ->success($data, $statusCode, $headers);
    }

    /**
     * Create an error response with version-appropriate formatting.
     *
     * @param Request               $request    Current request
     * @param string                $message    Error message
     * @param int                   $statusCode HTTP status code
     * @param array<string, mixed>  $errors     Additional error details
     * @param array<string, string> $headers    Additional headers
     */
    protected function createApiErrorResponse(
        Request $request,
        string $message,
        int $statusCode = 400,
        array $errors = [],
        array $headers = [],
    ): JsonResponse {
        return $this->getResponseBuilder($request)
            ->error($message, $statusCode, $errors, $headers);
    }

    /**
     * Create a paginated response.
     *
     * @param Request               $request    Current request
     * @param mixed                 $data       Response data
     * @param array<string, mixed>  $pagination Pagination metadata
     * @param array<string, string> $links      Pagination links
     * @param array<string, string> $headers    Additional headers
     */
    protected function createPaginatedResponse(
        Request $request,
        mixed $data,
        array $pagination,
        array $links = [],
        array $headers = [],
    ): JsonResponse {
        // Auto-generate pagination links if not provided
        if ($links === [] && isset($pagination['page'], $pagination['perPage'], $pagination['total'])) {
            $links = $this->getPaginationLinks(
                $request,
                $pagination['page'],
                $pagination['perPage'],
                $pagination['total']
            );
        }

        return $this->getResponseBuilder($request)
            ->paginated($data, $pagination, $links, $headers);
    }

    /**
     * Create a resource response with HATEOAS links.
     *
     * @param Request               $request    Current request
     * @param mixed                 $data       Resource data
     * @param array<string, string> $links      Resource links
     * @param int                   $statusCode HTTP status code
     * @param array<string, string> $headers    Additional headers
     */
    protected function createResourceResponse(
        Request $request,
        mixed $data,
        array $links = [],
        int $statusCode = 200,
        array $headers = [],
    ): JsonResponse {
        return $this->getResponseBuilder($request)
            ->resource($data, $links, $statusCode, $headers);
    }

    /**
     * Create a collection response.
     *
     * @param Request               $request Current request
     * @param array<mixed>          $items   Collection items
     * @param array<string, mixed>  $meta    Collection metadata
     * @param array<string, string> $links   Collection links
     * @param array<string, string> $headers Additional headers
     */
    protected function createCollectionResponse(
        Request $request,
        array $items,
        array $meta = [],
        array $links = [],
        array $headers = [],
    ): JsonResponse {
        return $this->getResponseBuilder($request)
            ->collection($items, $meta, $links, $headers);
    }

    /**
     * Validate endpoint availability for current version.
     */
    protected function validateEndpointAvailability(Request $request, string $endpoint): void
    {
        $context = $this->getVersionContext($request);

        if ($context->isEndpointRemoved($endpoint)) {
            throw new NotFoundHttpException(sprintf(
                "Endpoint '%s' is not available in API version %s",
                $endpoint,
                $context->version
            ));
        }
    }

    /**
     * Get version-specific feature configuration.
     *
     * @param Request $request      Current request
     * @param string  $feature      Feature name
     * @param mixed   $defaultValue Default value if feature not supported
     */
    protected function getFeatureConfig(Request $request, string $feature, mixed $defaultValue = null): mixed
    {
        $context = $this->getVersionContext($request);

        return $context->supportsFeature($feature) ? true : $defaultValue;
    }
}
