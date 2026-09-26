<?php

declare(strict_types=1);

use App\Flash;
use App\Installer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Views\PhpRenderer;

/**
 * First-run setup: /setup. Only registered while Installer::needed() — every
 * other URL redirects here until the database is ready.
 *
 * @param array<string,mixed> $c basePath, installer, view
 */
return static function (App $app, array $c): void {
    /** @var Installer $installer */
    $installer = $c['installer'];
    /** @var PhpRenderer $view */
    $view = $c['view'];
    $basePath = (string) $c['basePath'];

    $app->map(['GET', 'POST'], '/setup', function (Request $req, Response $res) use ($installer, $view, $basePath) {
        $values = $installer->defaults();
        $errors = [];

        if ($req->getMethod() === 'POST') {
            $body = (array) $req->getParsedBody();
            $result = $installer->install($body);
            $errors = $result['errors'];
            if ($errors === []) {
                Flash::set('ok', $result['created'] === []
                    ? 'Database connected — the tables were already there. Now create the super admin.'
                    : 'Database ready — created ' . implode(', ', $result['created']) . '. Now create the super admin.');

                return $res->withHeader('Location', $basePath . '/admin/setup')->withStatus(302);
            }
            $values = array_intersect_key($body, $values) + $values;
        }

        return $view->render($errors === [] ? $res : $res->withStatus(422), 'install/setup.php', [
            'title' => 'Set up', 'values' => $values, 'errors' => $errors,
        ]);
    });

    $app->any('/{path:.*}', fn (Request $req, Response $res) => $res->withHeader('Location', $basePath . '/setup')->withStatus(302));
};
