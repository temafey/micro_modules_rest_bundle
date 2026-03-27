<?php

declare(strict_types=1);

namespace MicroModule\Rest\Listener;

use MicroModule\Rest\Api\ApiVersionResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ApiVersionListener implements EventSubscriberInterface
{
    /**
     * @param ApiVersionResolver $versionResolver Version resolver service
     */
    public function __construct(
        private ApiVersionResolver $versionResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    /**
     * Process request to determine API version.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Skip non-API routes
        if (! str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Resolve version and add it to request attributes
        $version = $this->versionResolver->resolve($request);
        $request->attributes->set('_api_version', $version);

        // For query parameter-based versioning
        if ($requestedVersion = $request->query->get('version')) {
            if (! $this->versionResolver->isSupported($requestedVersion)) {
                throw new BadRequestHttpException(sprintf(
                    'Unsupported API version "%s". Supported versions: %s',
                    $requestedVersion,
                    implode(', ', $this->versionResolver->getSupportedVersions())
                ));
            }

            $request->attributes->set('_api_version', $requestedVersion);
        }
    }
}
