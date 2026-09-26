<?php

declare(strict_types=1);

namespace App\Middleware;

use App\ClientAuth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Staff-only client pages: redirects to /{slug}/login unless signed in to
 * *this* client. Runs inside ClientResolver, so the `client` attribute is set.
 */
final class ClientAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private ClientAuth $auth, private string $basePath)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $client = (array) $request->getAttribute('client');
        $user   = $this->auth->user((int) $client['id']);

        if ($user !== null) {
            return $handler->handle($request->withAttribute('user', $user));
        }

        return (new SlimResponse())
            ->withHeader('Location', $this->basePath . '/' . $client['slug'] . '/login')
            ->withStatus(302);
    }
}
