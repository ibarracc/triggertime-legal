<?php

declare(strict_types=1);

namespace App\Middleware;

use Cake\Core\Configure;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiKeyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $apiKey = $request->getHeaderLine('X-Api-Key');

        if (empty($apiKey)) {
            throw new UnauthorizedException('Missing X-Api-Key header');
        }

        $apiKeysConfig = Configure::read('ApiKeys.keys');
        $verifySignature = Configure::read('ApiKeys.verify_signature');

        if (!isset($apiKeysConfig[$apiKey])) {
            throw new UnauthorizedException('Invalid API Key');
        }

        $appConfig = $apiKeysConfig[$apiKey];
        $appInstance = $appConfig['app_instance'];

        // Attach the resolved app_instance to the request
        $request = $request->withAttribute('app_instance', $appInstance);

        if ($verifySignature) {
            $timestampHeader = $request->getHeaderLine('X-Api-Timestamp');
            $signatureHeader = $request->getHeaderLine('X-Api-Signature');

            if (empty($timestampHeader) || empty($signatureHeader)) {
                throw new UnauthorizedException('Missing timestamp or signature headers for HMAC verification');
            }

            $timestamp = (int)$timestampHeader;
            $currentTimestamp = time();

            // Allow +/- 5 minutes drift
            if (abs($currentTimestamp - $timestamp) > 300) {
                throw new UnauthorizedException('Request timestamp expired or too far in the future');
            }

            $body = (string)$request->getBody();
            // Expected format: "{timestamp}.{body}"
            $message = $timestamp . '.' . $body;
            $secret = $appConfig['secret'];

            $expectedSignature = hash_hmac('sha256', $message, $secret);

            if (!hash_equals($expectedSignature, $signatureHeader)) {
                Log::warning("HMAC signature mismatch. Expected: {$expectedSignature}, Received: {$signatureHeader}");
                throw new UnauthorizedException('Invalid API Signature');
            }
        }

        return $handler->handle($request);
    }
}
