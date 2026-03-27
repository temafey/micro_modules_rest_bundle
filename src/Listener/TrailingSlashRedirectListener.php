<?php

declare(strict_types=1);

namespace MicroModule\Rest\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Normalizes API URLs to ensure trailing slash consistency with route definitions.
 */
class TrailingSlashRedirectListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly RouterInterface $router,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            // High priority to run before routing
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Skip if root path
        if ($path === '/') {
            return;
        }

        // Only process API routes
        if (! str_starts_with($path, '/api')) {
            return;
        }

        // Try matching the path as-is first
        try {
            $this->router->match($path);

            return; // Path matches a route, no rewrite needed
        } catch (MethodNotAllowedException) {
            return; // Route exists but method differs, no rewrite needed
        } catch (ResourceNotFoundException) {
            // Path doesn't match, try toggling trailing slash
        }

        $hasTrailingSlash = str_ends_with($path, '/');
        $normalizedPath = $hasTrailingSlash ? rtrim($path, '/') : $path . '/';

        // Verify the alternative path actually matches a route
        try {
            $this->router->match($normalizedPath);
        } catch (ResourceNotFoundException|MethodNotAllowedException) {
            return; // Neither version matches, let Symfony handle it
        }

        $request->server->set(
            'REQUEST_URI',
            $normalizedPath . ($request->getQueryString() !== null ? '?' . $request->getQueryString() : '')
        );
        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );
    }
}
