<?php

namespace App\Middleware;

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AdminRoleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $payload = $request->getAttribute('jwt_payload');

        if (!$payload) {
            throw new UnauthorizedException('Authentication required');
        }

        if (!isset($payload['role']) || !in_array($payload['role'], ['admin', 'club_admin'])) {
            throw new ForbiddenException('Admin or Club Admin privileges required');
        }

        return $handler->handle($request);
    }
}
