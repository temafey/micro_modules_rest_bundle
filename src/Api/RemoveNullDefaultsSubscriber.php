<?php

declare(strict_types=1);

namespace MicroModule\Rest\Api;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Removes "default": null from all OpenAPI schema properties in doc.json responses.
 *
 * NelmioApiDoc auto-discovers PHP reflection defaults (= null) from constructor
 * promoted properties and serializes them into the OpenAPI schema. This subscriber
 * strips those null defaults from the JSON response.
 */
final class RemoveNullDefaultsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -128],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (!str_contains($path, '/doc.json')) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();

        if (false === $content || '' === $content) {
            return;
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return;
        }

        $this->removeNullDefaults($data);

        $response->setContent((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function removeNullDefaults(array &$data): void
    {
        if (!isset($data['components']['schemas']) || !is_array($data['components']['schemas'])) {
            return;
        }

        foreach ($data['components']['schemas'] as &$schema) {
            if (!isset($schema['properties']) || !is_array($schema['properties'])) {
                continue;
            }

            foreach ($schema['properties'] as &$property) {
                if (is_array($property) && array_key_exists('default', $property) && null === $property['default']) {
                    unset($property['default']);
                }
            }
        }
    }
}
