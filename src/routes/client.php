<?php

declare(strict_types=1);

use App\ClientAuth;
use App\Flash;
use App\Middleware\ClientAuthMiddleware;
use App\Middleware\ClientResolver;
use App\Pos\CustomerRepository;
use App\Pos\EmployeeRepository;
use App\Pos\PointTransactionRepository;
use App\QrService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\PhpRenderer;

/**
 * Everything under /{slug}: the client's staff login and pages, and the public
 * customer page a QR code opens. ClientResolver has already turned the slug
 * into the `client` row and a `pdo` for that client's database.
 *
 * @param array<string,mixed> $c see index.php
 */
return static function (App $app, array $c): void {
    /** @var PhpRenderer $view */
    $view = $c['clientView'];
    /** @var PhpRenderer $publicView */
    $publicView = $c['publicView'];
    /** @var ClientAuth $auth */
    $auth = $c['clientAuth'];
    /** @var QrService $qr */
    $qr = $c['qr'];
    $notFound = $c['notFound'];
    $basePath = (string) $c['basePath'];
    $root     = (string) $c['root'];
    $baseUrl  = (string) $c['config']['base_url'];

    $client  = static fn (Request $req): array => (array) $req->getAttribute('client');
    $clientId = static fn (Request $req): int => (int) $req->getAttribute('client')['id'];
    $to = static fn (Request $req, string $path): string => $basePath . '/' . $req->getAttribute('client')['slug'] . $path;
    $redirect = static fn (Request $req, Response $res, string $path): Response
        => $res->withHeader('Location', $to($req, $path))->withStatus(302);

    /** Absolute link a customer's QR code opens. */
    $publicUrl = static function (Request $req, string $code) use ($qr, $to, $baseUrl, $basePath): string {
        $path = $to($req, '/c/' . $qr->token((int) $req->getAttribute('client')['id'], $code));
        if ($baseUrl !== '') {
            return $baseUrl . substr($path, strlen($basePath));
        }
        $uri = $req->getUri();

        return $uri->getScheme() . '://' . $uri->getAuthority() . $path;
    };

    /** QR PNG for one customer, with the client logo in the middle. */
    $qrPng = static function (Request $req, array $customer) use ($qr, $publicUrl, $root): string {
        $logo = (string) ($req->getAttribute('client')['logo_path'] ?? '');

        return $qr->png(
            $publicUrl($req, (string) $customer['code']),
            $logo !== '' ? $root . '/' . $logo : null,
            (string) $customer['code']
        );
    };

    $app->group('/{slug:[a-z0-9-]+}', function (RouteCollectorProxy $g) use (
        $view, $publicView, $auth, $qr, $notFound, $client, $clientId, $to, $redirect, $publicUrl, $qrPng, $basePath
    ) {
        /* ------------------------------------------------ Login */

        $g->get('/login', function (Request $req, Response $res) use ($view, $auth, $client, $clientId, $redirect) {
            if ($auth->user($clientId($req)) !== null) {
                return $redirect($req, $res, '/customers');
            }

            return $view->render($res, 'client/login.php', [
                'title' => $client($req)['name'] . ' — Sign in', 'client' => $client($req),
            ]);
        });

        $g->post('/login', function (Request $req, Response $res) use ($view, $auth, $client, $clientId, $redirect) {
            $body = (array) $req->getParsedBody();
            $username = (string) ($body['username'] ?? '');
            $error = $auth->attempt(
                new EmployeeRepository($req->getAttribute('pdo')),
                $clientId($req),
                $username,
                (string) ($body['pin'] ?? '')
            );
            if ($error === null) {
                return $redirect($req, $res, '/customers');
            }

            return $view->render($res->withStatus(401), 'client/login.php', [
                'title' => $client($req)['name'] . ' — Sign in', 'client' => $client($req),
                'error' => $error, 'username' => $username,
            ]);
        });

        $g->get('/logout', function (Request $req, Response $res) use ($auth, $clientId, $redirect) {
            $auth->logout($clientId($req));

            return $redirect($req, $res, '/login');
        });

        /* ------------------------------------------------ Public customer page */

        $g->get('/c/{token}', function (Request $req, Response $res, array $args) use ($publicView, $qr, $notFound, $client, $clientId) {
            $code = $qr->verify($clientId($req), (string) $args['token']);
            $pdo  = $req->getAttribute('pdo');
            $customer = $code === null ? null : (new CustomerRepository($pdo))->findActiveByCode($code);
            if ($customer === null) {
                return $notFound($req);
            }
            $greetings = ['Hi', 'Hello', 'Greetings', 'Welcome back', 'Hey there', 'Good to see you', 'Nice to see you'];

            return $publicView->render(
                $res->withHeader('Cache-Control', 'no-store')->withHeader('X-Robots-Tag', 'noindex, nofollow'),
                'public/customer.php',
                [
                    'title'    => $client($req)['name'],
                    'client'   => $client($req),
                    'customer' => $customer,
                    'greeting' => $greetings[random_int(0, count($greetings) - 1)],
                    'history'  => (new PointTransactionRepository($pdo))->historyForCode($code),
                ]
            );
        });

        /* ------------------------------------------------ Staff pages */

        $g->group('', function (RouteCollectorProxy $s) use ($view, $qr, $notFound, $client, $to, $redirect, $publicUrl, $qrPng) {

            $s->get('[/]', fn (Request $req, Response $res) => $redirect($req, $res, '/customers'));

            $s->get('/customers', function (Request $req, Response $res) use ($view, $client) {
                $customers = new CustomerRepository($req->getAttribute('pdo'));
                $params = $req->getQueryParams();
                $q = trim((string) ($params['q'] ?? ''));

                return $view->render($res, 'client/customers.php', [
                    'title'      => 'Customers',
                    'client'     => $client($req),
                    'user'       => $req->getAttribute('user'),
                    'q'          => $q,
                    'list'       => $customers->activeList($q, (int) ($params['page'] ?? 1)),
                    'total'      => $customers->totalBalance(),
                    'duplicates' => $customers->duplicateCodes(),
                ]);
            });

            $s->get('/customers/{id:[0-9]+}', function (Request $req, Response $res, array $args) use ($view, $notFound, $client, $publicUrl) {
                $pdo = $req->getAttribute('pdo');
                $customers = new CustomerRepository($pdo);
                $customer = $customers->find((int) $args['id']);
                if ($customer === null) {
                    return $notFound($req);
                }
                $code = trim((string) $customer['code']);

                return $view->render($res, 'client/customer_detail.php', [
                    'title'      => $customer['name'],
                    'client'     => $client($req),
                    'user'       => $req->getAttribute('user'),
                    'customer'   => $customer,
                    'history'    => (new PointTransactionRepository($pdo))->historyForCode($code),
                    'publicUrl'  => $code !== '' ? $publicUrl($req, $code) : null,
                    'sharedWith' => $code !== '' ? (int) ($customers->duplicateCodes()[$code] ?? 0) : 0,
                ]);
            });

            $s->get('/customers/{id:[0-9]+}/qr.png', function (Request $req, Response $res, array $args) use ($notFound, $qrPng) {
                $customer = (new CustomerRepository($req->getAttribute('pdo')))->find((int) $args['id']);
                if ($customer === null || trim((string) $customer['code']) === '') {
                    return $notFound($req);
                }
                $res->getBody()->write($qrPng($req, $customer));
                $res = $res->withHeader('Content-Type', 'image/png')->withHeader('Cache-Control', 'private, max-age=300');
                if (!empty($req->getQueryParams()['dl'])) {
                    $res = $res->withHeader(
                        'Content-Disposition',
                        'attachment; filename="' . QrService::filename((string) $customer['code'], (string) $customer['name']) . '"'
                    );
                }

                return $res;
            });

            $s->get('/qr', function (Request $req, Response $res) use ($view, $client) {
                $customers = new CustomerRepository($req->getAttribute('pdo'));

                return $view->render($res, 'client/qr_bulk.php', [
                    'title'      => 'QR codes',
                    'client'     => $client($req),
                    'user'       => $req->getAttribute('user'),
                    'customers'  => $customers->activeList('', 1, null)['rows'],
                    'duplicates' => $customers->duplicateCodes(),
                    'max'        => 500,
                ]);
            });

            $s->post('/qr/zip', function (Request $req, Response $res) use ($qr, $qrPng, $client, $redirect) {
                $ids = (array) (((array) $req->getParsedBody())['ids'] ?? []);
                $rows = (new CustomerRepository($req->getAttribute('pdo')))->findManyWithCode($ids);
                if ($rows === []) {
                    Flash::set('err', 'Select at least one customer that has a code.');

                    return $redirect($req, $res, '/qr');
                }
                if (count($rows) > 500) {
                    Flash::set('err', 'Up to 500 QR codes per download — select fewer.');

                    return $redirect($req, $res, '/qr');
                }

                @set_time_limit(300);
                $items = [];
                foreach ($rows as $row) {
                    $items[] = [
                        'filename' => QrService::filename((string) $row['code'], (string) $row['name']),
                        'png'      => $qrPng($req, $row),
                    ];
                }
                $path = $qr->zip($items);
                $res->getBody()->write((string) file_get_contents($path));
                @unlink($path);

                $name = $client($req)['slug'] . '-qr-' . date('Ymd-His') . '.zip';

                return $res->withHeader('Content-Type', 'application/zip')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $name . '"');
            });
        })->add(new ClientAuthMiddleware($auth, $basePath));
    })->add(new ClientResolver($c['clients'], (array) $c['config']['db'], $notFound));
};
