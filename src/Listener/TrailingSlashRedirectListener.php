<?php

declare(strict_types=1);

namespace MicroModule\Rest\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Normalizes API URLs to ensure trailing slash consistency with route definitions.
 *
 * When a request path doesn't match any route, this listener tries toggling
 * the trailing slash and rewrites the request if the alternative matches.
 * Runs before the router (priority 256 > RouterListener's 32).
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

        if ($path === '/') {
            return;
        }

        if (! str_starts_with($path, '/api')) {
            return;
        }

        // Try matching the path as-is
        try {
            $this->router->match($path);

            return;
        } catch (MethodNotAllowedException) {
            return;
        } catch (ResourceNotFoundException) {
            // Path doesn't match, try toggling trailing slash
        }

        $hasTrailingSlash = str_ends_with($path, '/');
        $normalizedPath = $hasTrailingSlash ? rtrim($path, '/') : $path . '/';

        try {
            $this->router->match($normalizedPath);
        } catch (ResourceNotFoundException|MethodNotAllowedException) {
            return;
        }

        // Rewrite REQUEST_URI and force pathInfo recalculation.
        // We must reset both the server bag AND the cached private properties
        // (requestUri, pathInfo, baseUrl, basePath) in the Request object.
        $qs = $request->getQueryString();
        $newUri = $normalizedPath . ($qs !== null ? '?' . $qs : '');
        $request->server->set('REQUEST_URI', $newUri);

        // Use Reflection to reset cached URL properties so getPathInfo()
        // returns the updated path. This avoids initialize() which would
        // destroy the parsed request body for POST/PUT requests.
        $this->resetRequestUriCache($request);
    }

    private function resetRequestUriCache(Request $request): void
    {
        $reflection = new \ReflectionClass($request);

        foreach (['requestUri', 'pathInfo', 'baseUrl', 'basePath'] as $property) {
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setValue($request, null);
            }
        }
    }
}
