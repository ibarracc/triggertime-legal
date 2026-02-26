<?php

namespace App\Middleware;

use App\Service\JwtService;
use Cake\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JwtMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');

        if (empty($header) || !preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            throw new UnauthorizedException('Missing or invalid Authorization header');
        }

        $token = $matches[1];

        $jwtService = new JwtService();
        $payload = $jwtService->verifyToken($token);

        if (!$payload) {
            throw new UnauthorizedException('Invalid or expired token');
        }

        // Attach parsed token claims to the request so controllers can use it easily
        $request = $request->withAttribute('jwt_payload', $payload);

        return $handler->handle($request);
    }
}
