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

        // Rewrite REQUEST_URI in server bag
        $qs = $request->getQueryString();
        $newUri = $normalizedPath . ($qs !== null ? '?' . $qs : '');
        $request->server->set('REQUEST_URI', $newUri);

        // Force Symfony to recalculate pathInfo from the updated REQUEST_URI.
        // Request caches pathInfo/requestUri in private properties — initialize()
        // is the only public method that fully resets all cached URL properties.
        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent(),
        );
    }
}
