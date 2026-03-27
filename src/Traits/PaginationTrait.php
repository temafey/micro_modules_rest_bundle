<?php

declare(strict_types=1);

namespace MicroModule\Rest\Traits;

use Symfony\Component\HttpFoundation\Request;

/**
 * Trait for handling pagination parameters and metadata.
 */
trait PaginationTrait
{
    /**
     * Extract pagination parameters from request.
     *
     * @param Request $request        The HTTP request
     * @param int     $defaultPage    Default page number
     * @param int     $defaultPerPage Default items per page
     * @param int     $maxPerPage     Maximum items per page
     *
     * @return array{page: int, perPage: int}
     */
    protected function getPaginationParams(
        Request $request,
        int $defaultPage = 1,
        int $defaultPerPage = 10,
        int $maxPerPage = 100,
    ): array {
        return [
            'page' => max(1, (int) $request->query->get('page', $defaultPage)),
            'perPage' => max(1, min($maxPerPage, (int) $request->query->get('perPage', $defaultPerPage))),
        ];
    }

    /**
     * Generate pagination metadata.
     *
     * @param int $total   Total item count
     * @param int $page    Current page
     * @param int $perPage Items per page
     *
     * @return array{total: int, page: int, perPage: int, pages: int}
     */
    protected function getPaginationMetadata(int $total, int $page, int $perPage): array
    {
        return [
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Generate pagination links.
     *
     * @param Request $request The HTTP request
     * @param int     $page    Current page
     * @param int     $perPage Items per page
     * @param int     $total   Total item count
     *
     * @return array<string, string> Pagination links
     */
    protected function getPaginationLinks(Request $request, int $page, int $perPage, int $total): array
    {
        $baseUrl = $request->getSchemeAndHttpHost() . $request->getBasePath() . $request->getPathInfo();
        $queryParams = $request->query->all();
        $pages = max(1, ceil($total / $perPage));

        $links = [
            'self' => $this->buildUrl($baseUrl, array_merge($queryParams, [
                'page' => $page,
                'perPage' => $perPage,
            ])),
            'first' => $this->buildUrl($baseUrl, array_merge($queryParams, [
                'page' => 1,
                'perPage' => $perPage,
            ])),
            'last' => $this->buildUrl($baseUrl, array_merge($queryParams, [
                'page' => $pages,
                'perPage' => $perPage,
            ])),
        ];

        if ($page > 1) {
            $links['prev'] = $this->buildUrl($baseUrl, array_merge($queryParams, [
                'page' => $page - 1,
                'perPage' => $perPage,
            ]));
        }

        if ($page < $pages) {
            $links['next'] = $this->buildUrl($baseUrl, array_merge($queryParams, [
                'page' => $page + 1,
                'perPage' => $perPage,
            ]));
        }

        return $links;
    }

    /**
     * Build URL with query parameters.
     */
    protected function buildUrl(string $baseUrl, array $params): string
    {
        return $baseUrl . '?' . http_build_query($params);
    }
}
