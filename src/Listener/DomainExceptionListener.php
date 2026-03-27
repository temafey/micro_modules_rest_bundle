<?php

declare(strict_types=1);

namespace MicroModule\Rest\Listener;

use MicroModule\Base\Domain\Exception\ErrorException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event listener that catches domain exceptions and converts them to appropriate HTTP responses.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
class DomainExceptionListener
{
    /**
     * Handle exception event.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Only handle ErrorException instances (domain/business rule exceptions)
        if (! $exception instanceof ErrorException) {
            return;
        }

        // Get the HTTP status code from the exception code, default to 400 for invalid codes
        $statusCode = $exception->getCode();
        if ($statusCode < 400 || $statusCode >= 600) {
            $statusCode = Response::HTTP_BAD_REQUEST;
        }

        // Create JSON response with error details
        $response = new JsonResponse(
            [
                'error' => [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'type' => new \ReflectionClass($exception)
                        ->getShortName(),
                ],
            ],
            $statusCode
        );

        $event->setResponse($response);
    }
}
