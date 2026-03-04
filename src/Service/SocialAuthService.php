<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Log\Log;
use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

class SocialAuthService
{
    private const GOOGLE_JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';
    private const APPLE_JWKS_URL = 'https://appleid.apple.com/auth/keys';

    private const GOOGLE_ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];
    private const APPLE_ISSUER = 'https://appleid.apple.com';

    /**
     * Provider config: JWKS URL, expected issuers, audience config key.
     *
     * @var array<string, array{jwks_url: string, issuers: array<string>, audience_key: string}>
     */
    private array $providers = [];

    /**
     * Constructor. Initializes supported provider configurations.
     */
    public function __construct()
    {
        $this->providers = [
            'google' => [
                'jwks_url' => self::GOOGLE_JWKS_URL,
                'issuers' => self::GOOGLE_ISSUERS,
                'audience_key' => 'SocialAuth.google.clientId',
            ],
            'apple' => [
                'jwks_url' => self::APPLE_JWKS_URL,
                'issuers' => [self::APPLE_ISSUER],
                'audience_key' => 'SocialAuth.apple.serviceId',
            ],
        ];
    }

    /**
     * Verify a provider ID token and return extracted claims.
     *
     * @param string $provider Provider name ('google' or 'apple')
     * @param string $idToken The raw JWT ID token from the provider
     * @return array{sub: string, email: string, first_name: string|null, last_name: string|null}|null
     */
    public function verifyIdToken(string $provider, string $idToken): ?array
    {
        if (!isset($this->providers[$provider])) {
            return null;
        }

        $config = $this->providers[$provider];
        $expectedAudience = Configure::read($config['audience_key'], '');

        if (empty($expectedAudience)) {
            Log::error("SocialAuth: Missing audience config for provider '{$provider}'");

            return null;
        }

        try {
            $keys = $this->fetchJwks($provider, $config['jwks_url']);
            if ($keys === null) {
                return null;
            }

            $decoded = JWT::decode($idToken, $keys);
            $payload = (array)$decoded;

            // Verify issuer
            if (!isset($payload['iss']) || !in_array($payload['iss'], $config['issuers'], true)) {
                Log::warning("SocialAuth: Invalid issuer for {$provider}: " . ($payload['iss'] ?? 'missing'));

                return null;
            }

            // Verify audience
            $aud = $payload['aud'] ?? null;
            if ($aud !== $expectedAudience) {
                Log::warning("SocialAuth: Invalid audience for {$provider}: {$aud}");

                return null;
            }

            // Verify email exists
            if (empty($payload['email'])) {
                Log::warning("SocialAuth: No email in token for {$provider}");

                return null;
            }

            // Extract name — Google uses 'given_name'/'family_name', Apple may not include name
            return [
                'sub' => $payload['sub'],
                'email' => $payload['email'],
                'first_name' => $payload['given_name'] ?? null,
                'last_name' => $payload['family_name'] ?? null,
            ];
        } catch (Exception $e) {
            Log::warning("SocialAuth: Token verification failed for {$provider}: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Fetch and cache JWKS keys for a provider.
     *
     * @param string $provider Provider name (used as cache key prefix)
     * @param string $url JWKS endpoint URL
     * @return array<\Firebase\JWT\Key>|null Array of Key objects or null on failure
     */
    private function fetchJwks(string $provider, string $url): ?array
    {
        $cacheKey = "jwks_{$provider}";
        $cached = Cache::read($cacheKey, 'social_auth');

        if ($cached !== null) {
            return JWK::parseKeySet($cached);
        }

        $context = stream_context_create([
            'http' => ['timeout' => 10],
            'ssl' => ['verify_peer' => true],
        ]);

        try {
            $response = file_get_contents($url, false, $context);
        } catch (Exception $e) {
            Log::error("SocialAuth: Failed to fetch JWKS from {$url}: " . $e->getMessage());

            return null;
        }
        if ($response === false) {
            Log::error("SocialAuth: Failed to fetch JWKS from {$url}");

            return null;
        }

        $jwks = json_decode($response, true);
        if (!is_array($jwks) || !isset($jwks['keys'])) {
            Log::error("SocialAuth: Invalid JWKS response from {$url}");

            return null;
        }

        Cache::write($cacheKey, $jwks, 'social_auth');

        return JWK::parseKeySet($jwks);
    }
}
