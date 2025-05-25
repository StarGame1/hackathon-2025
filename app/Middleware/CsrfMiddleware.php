<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $token = $data['csrf_token'] ?? '';
            
            if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
                return (new Response())->withStatus(403);
            }
        }
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        return $handler->handle($request);
    }
}