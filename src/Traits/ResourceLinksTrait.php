<?php

declare(strict_types=1);

namespace MicroModule\Rest\Traits;

use Symfony\Component\HttpFoundation\Request;

/**
 * Trait for generating resource links.
 */
trait ResourceLinksTrait
{
    /**
     * Generate task resource links.
     *
     * @param string $uuid    Task UUID
     * @param string $baseUrl Base API URL
     * @param string $version API version
     *
     * @return array<string, string> Resource links
     */
    protected function getTaskLinks(string $uuid, string $baseUrl, string $version = 'v2'): array
    {
        return [
            'self' => sprintf('%s/api/%s/tasks/%s', $baseUrl, $version, $uuid),
            'update' => sprintf('%s/api/%s/tasks/%s/status', $baseUrl, $version, $uuid),
            'assign' => sprintf('%s/api/%s/tasks/%s/assign', $baseUrl, $version, $uuid),
            'priority' => sprintf('%s/api/%s/tasks/%s/priority', $baseUrl, $version, $uuid),
            'dueDate' => sprintf('%s/api/%s/tasks/%s/due-date', $baseUrl, $version, $uuid),
        ];
    }

    /**
     * Generate user resource links.
     *
     * @param string $uuid    User UUID
     * @param string $baseUrl Base API URL
     * @param string $version API version
     *
     * @return array<string, string> Resource links
     */
    protected function getUserLinks(string $uuid, string $baseUrl, string $version = 'v2'): array
    {
        return [
            'self' => sprintf('%s/api/%s/users/%s', $baseUrl, $version, $uuid),
            'tasks' => sprintf('%s/api/%s/tasks?assigneeId=%s', $baseUrl, $version, $uuid),
            'update' => sprintf('%s/api/%s/users/%s', $baseUrl, $version, $uuid),
        ];
    }

    /**
     * Get base URL from request.
     */
    protected function getBaseUrl(Request $request): string
    {
        return $request->getSchemeAndHttpHost() . $request->getBasePath();
    }
}
