<?php

declare(strict_types=1);

namespace MicroModule\Rest\Listener;

use MicroModule\Rest\Security\StatelessCsrfTokenService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CSRF Token Validation Listener for API Endpoints.
 *
 * Validates CSRF tokens on state-changing requests (POST, PUT, PATCH, DELETE)
 * for routes marked with the 'csrf_protected' attribute.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final readonly class CsrfTokenValidationListener
{
    private const array STATE_CHANGING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private StatelessCsrfTokenService $csrfTokenService,
        private bool $enabled = false, // Disabled by default for API-only services
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (! $this->enabled) {
            return;
        }

        $request = $event->getRequest();

        // Only validate state-changing methods
        if (! in_array($request->getMethod(), self::STATE_CHANGING_METHODS, true)) {
            return;
        }

        // Check if route requires CSRF protection
        $csrfProtected = $request->attributes->getBoolean('_csrf_protected');
        if (! $csrfProtected) {
            return;
        }

        // Get token from header
        $token = $request->headers->get(StatelessCsrfTokenService::HEADER_NAME);

        if ($token === null) {
            $event->setResponse($this->createErrorResponse('CSRF token missing', 'csrf_token_missing'));

            return;
        }

        // Determine token ID based on route
        $tokenId = $request->attributes->get('_csrf_token_id', StatelessCsrfTokenService::TOKEN_ID_API_MUTATE);

        if (! $this->csrfTokenService->isValid($tokenId, $token)) {
            $event->setResponse($this->createErrorResponse('Invalid CSRF token', 'csrf_token_invalid'));
        }
    }

    private function createErrorResponse(string $message, string $code): JsonResponse
    {
        return new JsonResponse(
            [
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ],
            Response::HTTP_FORBIDDEN
        );
    }
}
