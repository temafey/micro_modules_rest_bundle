<?php

declare(strict_types=1);

namespace MicroModule\Rest\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Normalizes API URLs for trailing slash consistency.
 *
 * Symfony's UrlMatcher returns a 301 redirect for trailing slash mismatches.
 * This works for GET but breaks POST/PUT/PATCH/DELETE because clients convert
 * the method to GET on 301 redirect.
 *
 * This listener intercepts the redirect result and rewrites the request
 * internally so the correct route is matched without any HTTP redirect.
 *
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

        if ($path === '/' || ! str_starts_with($path, '/api')) {
            return;
        }

        // Try matching the path — Symfony returns a redirect controller
        // for trailing slash mismatches instead of throwing an exception
        try {
            $match = $this->router->match($path);
        } catch (\Throwable) {
            return;
        }

        // Check if the match is a trailing slash redirect
        $controller = $match['_controller'] ?? '';
        if (! str_contains($controller, 'RedirectController')) {
            return; // Normal route match, no rewrite needed
        }

        $redirectPath = $match['path'] ?? null;
        if ($redirectPath === null) {
            return;
        }

        // Build the new URI with query string
        $qs = $request->getQueryString();
        $newUri = $redirectPath . ($qs !== null ? '?' . $qs : '');

        // Create a new sub-request that preserves method, headers, and body
        // but with the corrected path
        $subRequest = $request->duplicate(null, null, null, null, null, array_merge(
            $request->server->all(),
            ['REQUEST_URI' => $newUri],
        ));
        $subRequest->setMethod($request->getMethod());

        // Replace the event response with an internal forward
        $event->setResponse(
            $event->getKernel()->handle($subRequest, \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST),
        );
    }
}
