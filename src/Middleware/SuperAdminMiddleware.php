<?php

declare(strict_types=1);

namespace App\Middleware;

use App\SuperAdminAuth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Guards the super admin area; anonymous requests go to /admin/login.
 */
final class SuperAdminMiddleware implements MiddlewareInterface
{
    public function __construct(private SuperAdminAuth $auth, private string $basePath)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        if ($this->auth->check()) {
            return $handler->handle($request);
        }

        return (new SlimResponse())
            ->withHeader('Location', $this->basePath . '/admin/login')
            ->withStatus(302);
    }
}
