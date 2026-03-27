<?php

declare(strict_types=1);

namespace MicroModule\Rest\Listener;

use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * ProcessUuidListener ensures every HTTP request has a valid process_uuid.
 *
 * This listener:
 * - Checks query parameters, POST data, and JSON body for process_uuid or processUuid
 * - Validates UUID format if present
 * - Generates a new UUID v4 if not present
 * - Adds the UUID to request attributes for downstream access
 *
 * Priority: 90 (executes before ApiVersionListener at 100)
 */
final readonly class ProcessUuidListener implements EventSubscriberInterface
{
    private const string ATTRIBUTE_NAME = 'process_uuid';

    private const string PARAM_SNAKE_CASE = 'process_uuid';

    private const string PARAM_CAMEL_CASE = 'processUuid';

    /**
     * @param LoggerInterface|null $logger Optional logger for debugging
     */
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 90],
        ];
    }

    /**
     * Process HTTP request to ensure process_uuid exists and is valid.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // Only process main requests, skip sub-requests
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Try to extract process_uuid from various sources
        $processUuid = $this->extractProcessUuid($request);

        // If found, validate it; otherwise generate new one
        if ($processUuid !== null) {
            $validatedUuid = $this->validateAndNormalizeUuid($processUuid);
        } else {
            $validatedUuid = $this->generateUuid();

            $this->logger?->debug('Generated new process_uuid', [
                'process_uuid' => $validatedUuid,
                'uri' => $request->getRequestUri(),
                'method' => $request->getMethod(),
            ]);
        }

        // Store in request attributes for downstream use
        $request->attributes->set(self::ATTRIBUTE_NAME, $validatedUuid);

        // For JSON requests, merge process_uuid into request data so MapRequestPayload can deserialize it
        if ($this->isJsonRequest($request)) {
            $this->mergeIntoJsonRequest($request, $validatedUuid);
        } else {
            // For form data requests, simply add to request ParameterBag
            $request->request->set(self::ATTRIBUTE_NAME, $validatedUuid);
        }
    }

    /**
     * Extract process_uuid from request (query params, POST data, or JSON body).
     *
     * Supports both snake_case (process_uuid) and camelCase (processUuid).
     */
    private function extractProcessUuid(Request $request): ?string
    {
        // 1. Check GET query parameters
        $queryUuid = $request->query->get(self::PARAM_SNAKE_CASE)
            ?? $request->query->get(self::PARAM_CAMEL_CASE);

        if ($queryUuid !== null) {
            $this->logger?->debug('Found process_uuid in query parameters', [
                'process_uuid' => $queryUuid,
            ]);

            return (string) $queryUuid;
        }

        // 2. Check POST parameters (form data)
        $postUuid = $request->request->get(self::PARAM_SNAKE_CASE)
            ?? $request->request->get(self::PARAM_CAMEL_CASE);

        if ($postUuid !== null) {
            $this->logger?->debug('Found process_uuid in POST parameters', [
                'process_uuid' => $postUuid,
            ]);

            return (string) $postUuid;
        }

        // 3. Check JSON body for Content-Type: application/json
        if ($this->isJsonRequest($request)) {
            $jsonUuid = $this->extractFromJsonBody($request);

            if ($jsonUuid !== null) {
                $this->logger?->debug('Found process_uuid in JSON body', [
                    'process_uuid' => $jsonUuid,
                ]);

                return $jsonUuid;
            }
        }

        return null;
    }

    /**
     * Check if request has JSON content type.
     */
    private function isJsonRequest($request): bool
    {
        $contentType = $request->headers->get('Content-Type', '');

        return str_contains((string) $contentType, 'application/json')
            || str_contains((string) $contentType, 'application/vnd.api+json');
    }

    /**
     * Extract process_uuid from JSON request body.
     */
    private function extractFromJsonBody($request): ?string
    {
        $content = $request->getContent();

        if (empty($content)) {
            return null;
        }

        try {
            $data = json_decode((string) $content, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($data)) {
                return null;
            }

            // Check both snake_case and camelCase variants
            return $data[self::PARAM_SNAKE_CASE]
                ?? $data[self::PARAM_CAMEL_CASE]
                ?? null;
        } catch (\JsonException $jsonException) {
            $this->logger?->warning('Failed to decode JSON body', [
                'error' => $jsonException->getMessage(),
                'uri' => $request->getRequestUri(),
            ]);

            return null;
        }
    }

    /**
     * Validate UUID format and normalize to lowercase string.
     */
    private function validateAndNormalizeUuid(string $uuid): string
    {
        try {
            $uuidObject = ProcessUuid::fromNative($uuid);

            $this->logger?->debug('Validated existing process_uuid', [
                'process_uuid' => $uuidObject->toString(),
            ]);

            return $uuidObject->toString();
        } catch (InvalidUuidStringException $invalidUuidStringException) {
            $this->logger?->error('Invalid process_uuid format', [
                'provided_uuid' => $uuid,
                'error' => $invalidUuidStringException->getMessage(),
            ]);

            throw new BadRequestHttpException(sprintf(
                'Invalid process_uuid format: "%s". Expected valid UUID.',
                $uuid
            ), $invalidUuidStringException);
        }
    }

    /**
     * Generate a new UUID.
     */
    private function generateUuid(): string
    {
        return ProcessUuid::generateAsString();
    }

    /**
     * Merge process_uuid into JSON request data.
     */
    private function mergeIntoJsonRequest(Request $request, string $validatedUuid): void
    {
        $content = $request->getContent();

        // Decode existing JSON body or start with empty array
        $data = [];
        if (! empty($content)) {
            try {
                $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            } catch (\JsonException $e) {
                $this->logger?->warning('Failed to decode JSON body for merging process_uuid', [
                    'error' => $e->getMessage(),
                    'uri' => $request->getRequestUri(),
                ]);
            }
        }

        // Add or update process_uuid in the data array
        $data[self::PARAM_CAMEL_CASE] = $validatedUuid;

        // Replace the request ParameterBag with merged data
        $request->request->replace($data);

        $this->logger?->debug('Merged process_uuid into JSON request data', [
            'process_uuid' => $validatedUuid,
            'keys' => array_keys($data),
        ]);
    }
}
