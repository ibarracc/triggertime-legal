<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Utility\Security;
use RuntimeException;

class EmailVerificationService
{
    private const EXPIRY_SECONDS = 7 * 24 * 3600; // 7 days

    /**
     * Generate a signed email verification URL.
     *
     * @param string $userId The user ID to generate the URL for.
     * @param string $lang The user's preferred language code.
     * @return string
     */
    public function generateSignedUrl(string $userId, string $lang = 'en'): string
    {
        $expiry = (string)(time() + self::EXPIRY_SECONDS);
        $sig = $this->sign($userId, $expiry);

        $baseUrl = Configure::read('App.fullBaseUrl', Router::fullBaseUrl());

        return $baseUrl . '/verify-email?' . http_build_query([
            'uid' => $userId,
            'exp' => $expiry,
            'sig' => $sig,
            'lang' => $lang,
        ]);
    }

    /**
     * Verify a signed URL. Returns user ID if valid, null if invalid/expired.
     *
     * @param string $uid The user ID from the URL.
     * @param string $exp The expiry timestamp from the URL.
     * @param string $sig The signature from the URL.
     * @return string|null
     */
    public function verifySignedUrl(string $uid, string $exp, string $sig): ?string
    {
        if ((int)$exp < time()) {
            return null;
        }

        try {
            $expectedSig = $this->sign($uid, $exp);
        } catch (RuntimeException) {
            return null;
        }

        if (!hash_equals($expectedSig, $sig)) {
            return null;
        }

        return $uid;
    }

    /**
     * Sign a user ID and expiry timestamp.
     *
     * @param string $userId The user ID.
     * @param string $expiry The expiry timestamp.
     * @return string
     */
    private function sign(string $userId, string $expiry): string
    {
        $salt = Security::getSalt();
        if (empty($salt)) {
            throw new RuntimeException('Security.salt is not configured');
        }

        return hash_hmac(
            'sha256',
            $userId . ':' . $expiry,
            $salt,
        );
    }
}
