<?php

declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ApiErrorMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            $path = $request->getUri()->getPath();

            // If not an API request, re-throw so ErrorHandlerMiddleware handles it
            if (!str_contains($path, '/api/')) {
                throw $e;
            }

            $res = [
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => (int)$e->getCode() ?: 500,
                ]
            ];

            if (\Cake\Core\Configure::read('debug')) {
                $res['error']['exception'] = get_class($e);
                $res['error']['file'] = $e->getFile();
                $res['error']['line'] = $e->getLine();
            }

            $response = new Response();
            return $response
                ->withStatus($res['error']['code'] >= 400 && $res['error']['code'] < 600 ? $res['error']['code'] : 500)
                ->withType('application/json')
                ->withStringBody(json_encode($res));
        }
    }
}
