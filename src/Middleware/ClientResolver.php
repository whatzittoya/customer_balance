<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Database;
use App\Parent\ClientRepository;
use Closure;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Routing\RouteContext;
use Throwable;

/**
 * Turns the {slug} route segment into its client: the `clients` row and a PDO
 * connection to that client's own database, set as the request attributes
 * `client` and `pdo`. Unknown, inactive or unreachable clients get a 404 —
 * the same page either way, so slugs can't be probed.
 */
final class ClientResolver implements MiddlewareInterface
{
    /**
     * @param array<string,string> $dbConfig
     * @param Closure(Request):Response $notFound
     */
    public function __construct(
        private ClientRepository $clients,
        private array $dbConfig,
        private Closure $notFound
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $route = RouteContext::fromRequest($request)->getRoute();
        $slug  = (string) ($route?->getArgument('slug') ?? '');

        $client = $slug === '' ? null : $this->clients->findBySlug($slug);
        if ($client === null || (int) $client['active'] !== 1) {
            return ($this->notFound)($request);
        }

        try {
            $pdo = Database::client($this->dbConfig, (string) $client['db_name']);
        } catch (Throwable) {
            return ($this->notFound)($request);
        }

        return $handler->handle(
            $request->withAttribute('client', $client)->withAttribute('pdo', $pdo)
        );
    }
}
