<?php

namespace App\Service;

use Cake\Core\Configure;

class JwtService
{
    /**
     * Generate a JSON Web Token
     */
    public function generateToken(array $payload, int $expirationSeconds = 86400 * 30): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $payload['iat'] = time();
        $payload['exp'] = time() + $expirationSeconds;

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->getSecretKey(), true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Decode and verify a JSON Web Token
     */
    public function verifyToken(string $token): ?array
    {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) != 3) {
            return null;
        }

        [$base64UrlHeader, $base64UrlPayload, $base64UrlSignature] = $tokenParts;

        $signature = $this->base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->getSecretKey(), true);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($base64UrlPayload), true);

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private function getSecretKey(): string
    {
        return Configure::read('Security.salt', 'triggertime_super_secret_fallback_key_2026!');
    }

    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
