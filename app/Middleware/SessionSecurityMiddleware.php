<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class SessionSecurityMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (isset($_SESSION['user_id'])) {
            $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            
            if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUserAgent) {
                session_destroy();
                return (new Response())
                    ->withHeader('Location', '/login')
                    ->withStatus(302);
            }
            
            if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIp) {
                session_destroy();
                return (new Response())
                    ->withHeader('Location', '/login')
                    ->withStatus(302);
            }
        }
        
        return $handler->handle($request);
    }
}