<?php

declare(strict_types=1);

namespace MicroModule\Rest\Security;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Stateless CSRF Token Service for REST APIs.
 *
 * Provides a simple interface for generating and validating
 * stateless CSRF tokens in API contexts.
 */
final readonly class StatelessCsrfTokenService
{
    public const string TOKEN_ID_API_MUTATE = 'api_mutate';

    public const string TOKEN_ID_API_COMMAND = 'api_command';

    public const string TOKEN_ID_WEBHOOK = 'webhook';

    public const string HEADER_NAME = 'X-CSRF-Token';

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    /**
     * Generate a new CSRF token for the given token ID.
     */
    public function generateToken(string $tokenId = self::TOKEN_ID_API_MUTATE): string
    {
        return $this->csrfTokenManager->getToken($tokenId)
            ->getValue();
    }

    /**
     * Validate a CSRF token.
     */
    public function isValid(string $tokenId, string $tokenValue): bool
    {
        return $this->csrfTokenManager->isTokenValid(new CsrfToken($tokenId, $tokenValue));
    }

    /**
     * Validate a CSRF token from request header.
     *
     * @param array<string, string> $headers Request headers
     */
    public function validateFromHeaders(
        array $headers,
        string $tokenId = self::TOKEN_ID_API_MUTATE,
        string $headerName = self::HEADER_NAME,
    ): bool {
        $token = $headers[$headerName] ?? $headers[strtolower($headerName)] ?? null;

        if ($token === null) {
            return false;
        }

        return $this->isValid($tokenId, $token);
    }

    /**
     * Refresh and get a new token, invalidating the old one.
     */
    public function refreshToken(string $tokenId = self::TOKEN_ID_API_MUTATE): string
    {
        $this->csrfTokenManager->removeToken($tokenId);

        return $this->generateToken($tokenId);
    }
}
