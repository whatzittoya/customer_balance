<?php

declare(strict_types=1);

use App\Database;
use App\Flash;
use App\Middleware\SuperAdminMiddleware;
use App\Parent\AdminRepository;
use App\Parent\ClientRepository;
use App\Parent\ClientUserRepository;
use App\Pos\EmployeeRepository;
use App\SuperAdminAuth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\PhpRenderer;

/**
 * Super admin area: /admin — clients, their database, logo and staff.
 *
 * @param array<string,mixed> $c see index.php
 */
return static function (App $app, array $c): void {
    /** @var PhpRenderer $view */
    $view = $c['adminView'];
    /** @var SuperAdminAuth $auth */
    $auth = $c['saAuth'];
    /** @var AdminRepository $admins */
    $admins = $c['admins'];
    /** @var ClientRepository $clients */
    $clients = $c['clients'];
    /** @var ClientUserRepository $grants */
    $grants = $c['grants'];
    $notFound = $c['notFound'];
    $basePath = (string) $c['basePath'];
    $root     = (string) $c['root'];
    $dbConfig = (array) $c['config']['db'];

    $redirect = static fn (Response $res, string $path): Response
        => $res->withHeader('Location', $basePath . $path)->withStatus(302);

    /* ------------------------------------------------ Setup & login */

    // Creates the very first super admin; closed for good once one exists.
    $app->map(['GET', 'POST'], '/admin/setup', function (Request $req, Response $res) use ($view, $admins, $auth, $redirect) {
        if (!$admins->isEmpty()) {
            return $redirect($res, '/admin/login');
        }
        $errors = [];
        $username = '';
        if ($req->getMethod() === 'POST') {
            $body = (array) $req->getParsedBody();
            $username = trim((string) ($body['username'] ?? ''));
            $password = (string) ($body['password'] ?? '');
            if (!preg_match('/^[A-Za-z0-9._@-]{3,64}$/', $username)) {
                $errors['username'] = '3–64 letters, digits or . _ @ -';
            }
            if (strlen($password) < 8) {
                $errors['password'] = 'At least 8 characters.';
            } elseif ($password !== (string) ($body['password2'] ?? '')) {
                $errors['password2'] = 'Passwords do not match.';
            }
            if ($errors === []) {
                $auth->login($admins->create($username, $password), $username);
                Flash::set('ok', 'Super admin created. Add your first client.');

                return $redirect($res, '/admin');
            }
        }

        return $view->render($res, 'admin/setup.php', [
            'title' => 'Set up', 'errors' => $errors, 'username' => $username,
        ]);
    });

    $app->get('/admin/login', function (Request $req, Response $res) use ($view, $auth, $admins, $redirect) {
        if ($admins->isEmpty()) {
            return $redirect($res, '/admin/setup');
        }
        if ($auth->check()) {
            return $redirect($res, '/admin');
        }

        return $view->render($res, 'admin/login.php', ['title' => 'Super admin sign in']);
    });

    $app->post('/admin/login', function (Request $req, Response $res) use ($view, $auth, $redirect) {
        $body = (array) $req->getParsedBody();
        $username = (string) ($body['username'] ?? '');
        if ($auth->attempt($username, (string) ($body['password'] ?? ''))) {
            return $redirect($res, '/admin');
        }

        return $view->render($res->withStatus(401), 'admin/login.php', [
            'title' => 'Super admin sign in', 'error' => 'Wrong username or password.', 'username' => $username,
        ]);
    });

    $app->get('/admin/logout', function (Request $req, Response $res) use ($auth, $redirect) {
        $auth->logout();

        return $redirect($res, '/admin/login');
    });

    /* ------------------------------------------------ Signed-in area */

    $app->group('/admin', function (RouteCollectorProxy $g) use ($view, $clients, $grants, $notFound, $redirect, $root, $dbConfig) {

        /* ---------- Clients */

        $g->get('', function (Request $req, Response $res) use ($view, $clients) {
            return $view->render($res, 'admin/clients.php', [
                'title'   => 'Clients',
                'clients' => $clients->all(),
                'missing' => array_values(array_filter(
                    ['gd' => 'GD (QR images)', 'zip' => 'zip (bulk QR download)'],
                    static fn ($label, $ext) => !extension_loaded($ext),
                    ARRAY_FILTER_USE_BOTH
                )),
            ]);
        });

        /**
         * Validate the client form and store an uploaded logo.
         *
         * @return array{0:array<string,mixed>,1:array<string,string>} [data, errors]
         */
        $readClient = static function (Request $req, ?array $existing) use ($clients, $root): array {
            $body = (array) $req->getParsedBody();
            $d = [
                'name'      => trim((string) ($body['name'] ?? '')),
                'slug'      => strtolower(trim((string) ($body['slug'] ?? ''))),
                'db_name'   => trim((string) ($body['db_name'] ?? '')),
                'active'    => !empty($body['active']),
                'logo_path' => $existing['logo_path'] ?? null,
            ];
            $errors = [];

            if ($d['name'] === '' || mb_strlen($d['name']) > 120) {
                $errors['name'] = 'Required, up to 120 characters.';
            }
            if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,62}[a-z0-9])?$/', $d['slug'])) {
                $errors['slug'] = 'Lowercase letters, digits and dashes (not at the start or end).';
            } elseif (in_array($d['slug'], ClientRepository::RESERVED_SLUGS, true)) {
                $errors['slug'] = '"' . $d['slug'] . '" is reserved by the app.';
            } elseif ($clients->slugTaken($d['slug'], isset($existing['id']) ? (int) $existing['id'] : null)) {
                $errors['slug'] = 'Another client already uses this slug.';
            }
            if ($d['db_name'] === '') {
                $errors['db_name'] = 'Pick the client database.';
            } elseif (($why = $clients->validateClientDb($d['db_name'])) !== null) {
                $errors['db_name'] = $why;
            }

            if (!empty($body['remove_logo'])) {
                $d['logo_path'] = null;
            }

            /** @var UploadedFileInterface|null $file */
            $file = $req->getUploadedFiles()['logo'] ?? null;
            if ($file !== null && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                $types = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
                $tmp   = $file->getError() === UPLOAD_ERR_OK ? $file->getStream()->getMetadata('uri') : null;
                $info  = is_string($tmp) ? @getimagesize($tmp) : false;

                if ($file->getError() !== UPLOAD_ERR_OK) {
                    $errors['logo'] = 'Upload failed (code ' . $file->getError() . ').';
                } elseif ((int) $file->getSize() > 1024 * 1024) {
                    $errors['logo'] = 'Logo must be 1 MB or smaller.';
                } elseif ($info === false || !isset($types[$info[2]])) {
                    $errors['logo'] = 'Use a PNG, JPG, GIF or WebP image.';
                } elseif ($errors === []) {
                    $dir = $root . '/uploads/logos';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $rel = 'uploads/logos/' . $d['slug'] . '-' . bin2hex(random_bytes(4)) . '.' . $types[$info[2]];
                    $file->moveTo($root . '/' . $rel);
                    $d['logo_path'] = $rel;
                }
            }

            return [$d, $errors];
        };

        /** Delete a logo file this app stored, once nothing points at it. */
        $dropLogo = static function (?string $old, ?string $new) use ($root): void {
            if ($old !== null && $old !== '' && $old !== $new && str_starts_with($old, 'uploads/logos/')) {
                @unlink($root . '/' . $old);
            }
        };

        $g->get('/clients/new', function (Request $req, Response $res) use ($view, $clients) {
            return $view->render($res, 'admin/client_form.php', [
                'title' => 'New client', 'client' => null,
                'data' => ['active' => true], 'errors' => [],
                'databases' => $clients->availableDatabases(),
            ]);
        });

        $g->post('/clients', function (Request $req, Response $res) use ($view, $clients, $readClient, $redirect) {
            [$d, $errors] = $readClient($req, null);
            if ($errors !== []) {
                return $view->render($res->withStatus(422), 'admin/client_form.php', [
                    'title' => 'New client', 'client' => null, 'data' => $d, 'errors' => $errors,
                    'databases' => $clients->availableDatabases(),
                ]);
            }
            $id = $clients->create($d);
            Flash::set('ok', 'Client "' . $d['name'] . '" created. Now give their staff access.');

            return $redirect($res, '/admin/clients/' . $id . '/employees');
        });

        $g->get('/clients/{id:[0-9]+}/edit', function (Request $req, Response $res, array $args) use ($view, $clients, $notFound) {
            $client = $clients->find((int) $args['id']);
            if ($client === null) {
                return $notFound($req);
            }

            return $view->render($res, 'admin/client_form.php', [
                'title' => 'Edit ' . $client['name'], 'client' => $client,
                'data' => $client + ['active' => (int) $client['active'] === 1], 'errors' => [],
                'databases' => $clients->availableDatabases(),
            ]);
        });

        $g->post('/clients/{id:[0-9]+}', function (Request $req, Response $res, array $args) use ($view, $clients, $readClient, $dropLogo, $notFound, $redirect) {
            $client = $clients->find((int) $args['id']);
            if ($client === null) {
                return $notFound($req);
            }
            [$d, $errors] = $readClient($req, $client);
            if ($errors !== []) {
                return $view->render($res->withStatus(422), 'admin/client_form.php', [
                    'title' => 'Edit ' . $client['name'], 'client' => $client, 'data' => $d, 'errors' => $errors,
                    'databases' => $clients->availableDatabases(),
                ]);
            }
            $clients->update((int) $client['id'], $d);
            $dropLogo($client['logo_path'], $d['logo_path']);
            Flash::set('ok', 'Client "' . $d['name'] . '" saved.');

            return $redirect($res, '/admin');
        });

        /* ---------- A client's staff (stored in the client's own DB) */

        /** @return array{0:array<string,mixed>,1:EmployeeRepository}|null */
        $clientStaff = static function (int $id) use ($clients, $dbConfig): ?array {
            $client = $clients->find($id);
            if ($client === null) {
                return null;
            }

            return [$client, new EmployeeRepository(Database::client($dbConfig, (string) $client['db_name']))];
        };

        $g->get('/clients/{id:[0-9]+}/employees', function (Request $req, Response $res, array $args) use ($view, $grants, $clientStaff, $notFound) {
            $found = $clientStaff((int) $args['id']);
            if ($found === null) {
                return $notFound($req);
            }
            [$client, $employees] = $found;

            return $view->render($res, 'admin/employees.php', [
                'title'     => $client['name'] . ' — Staff',
                'client'    => $client,
                'employees' => $employees->all(),
                'roles'     => $grants->rolesForClient((int) $client['id']),
            ]);
        });

        /**
         * Validate the staff form. PIN is required for a new account that gets
         * access; blank on edit keeps the current one.
         *
         * @return array{0:array<string,mixed>,1:array<string,string>}
         */
        $readStaff = static function (array $body, EmployeeRepository $employees, ?int $exceptId): array {
            $d = [
                'name'     => trim((string) ($body['name'] ?? '')),
                'code'     => trim((string) ($body['code'] ?? '')),
                'jobTitle' => trim((string) ($body['jobTitle'] ?? '')),
                'pin'      => trim((string) ($body['pin'] ?? '')),
                'phone'    => trim((string) ($body['phone'] ?? '')),
                'email'    => trim((string) ($body['email'] ?? '')),
                'active'   => !empty($body['active']),
                'resigned' => !empty($body['resigned']),
                'access'   => in_array($body['access'] ?? '', ClientUserRepository::ROLES, true) ? (string) $body['access'] : '',
            ];
            $errors = [];
            if ($d['name'] === '') {
                $errors['name'] = 'Required.';
            } elseif ($employees->loginNameTaken($d['name'], $exceptId)) {
                $errors['name'] = 'Another employee already uses this as a name or code.';
            }
            if ($d['code'] !== '' && $employees->loginNameTaken($d['code'], $exceptId)) {
                $errors['code'] = 'Another employee already uses this as a name or code.';
            }
            if ($d['pin'] !== '' && !preg_match('/^[0-9A-Za-z]{3,20}$/', $d['pin'])) {
                $errors['pin'] = '3–20 letters or digits.';
            }
            if ($exceptId === null && $d['access'] !== '' && $d['pin'] === '') {
                $errors['pin'] = 'A PIN is needed to sign in.';
            }

            return [$d, $errors];
        };

        $saveStaff = static function (EmployeeRepository $employees, ?int $id, array $d): int {
            $row = [
                'name'     => $d['name'],
                'code'     => $d['code'] !== '' ? $d['code'] : null,
                'jobTitle' => $d['jobTitle'] !== '' ? $d['jobTitle'] : null,
                'pin'      => $d['pin'] !== '' ? $d['pin'] : null,
                'phone'    => $d['phone'] !== '' ? $d['phone'] : null,
                'email'    => $d['email'] !== '' ? $d['email'] : null,
                'active'   => $d['active'],
                'resigned' => $d['resigned'],
            ];
            if ($id === null) {
                return $employees->create($row);
            }
            $employees->update($id, $row);

            return $id;
        };

        $g->get('/clients/{id:[0-9]+}/employees/new', function (Request $req, Response $res, array $args) use ($view, $clientStaff, $notFound) {
            $found = $clientStaff((int) $args['id']);
            if ($found === null) {
                return $notFound($req);
            }

            return $view->render($res, 'admin/employee_form.php', [
                'title' => 'New staff — ' . $found[0]['name'], 'client' => $found[0], 'employee' => null,
                'data' => ['active' => true, 'access' => 'cashier'], 'errors' => [],
            ]);
        });

        $g->post('/clients/{id:[0-9]+}/employees', function (Request $req, Response $res, array $args) use ($view, $grants, $clientStaff, $readStaff, $saveStaff, $notFound, $redirect) {
            $found = $clientStaff((int) $args['id']);
            if ($found === null) {
                return $notFound($req);
            }
            [$client, $employees] = $found;
            [$d, $errors] = $readStaff((array) $req->getParsedBody(), $employees, null);
            if ($errors !== []) {
                return $view->render($res->withStatus(422), 'admin/employee_form.php', [
                    'title' => 'New staff — ' . $client['name'], 'client' => $client, 'employee' => null,
                    'data' => $d, 'errors' => $errors,
                ]);
            }
            $empId = $saveStaff($employees, null, $d);
            $grants->setRole((int) $client['id'], $empId, $d['access'] ?: null);
            Flash::set('ok', $d['name'] . ' added.');

            return $redirect($res, '/admin/clients/' . $client['id'] . '/employees');
        });

        $g->get('/clients/{id:[0-9]+}/employees/{eid:[0-9]+}/edit', function (Request $req, Response $res, array $args) use ($view, $grants, $clientStaff, $notFound) {
            $found = $clientStaff((int) $args['id']);
            $emp = $found === null ? null : $found[1]->find((int) $args['eid']);
            if ($emp === null) {
                return $notFound($req);
            }
            $client = $found[0];

            return $view->render($res, 'admin/employee_form.php', [
                'title' => 'Edit ' . $emp['name'], 'client' => $client, 'employee' => $emp,
                'data' => [
                    'name' => $emp['name'], 'code' => $emp['code'], 'jobTitle' => $emp['jobTitle'],
                    'phone' => $emp['phone1'], 'email' => $emp['email'],
                    'active' => (int) $emp['active'] === 1, 'resigned' => (int) $emp['resigned'] === 1,
                    'access' => $grants->roleFor((int) $client['id'], (int) $emp['id']) ?? '',
                ],
                'errors' => [],
            ]);
        });

        $g->post('/clients/{id:[0-9]+}/employees/{eid:[0-9]+}', function (Request $req, Response $res, array $args) use ($view, $grants, $clientStaff, $readStaff, $saveStaff, $notFound, $redirect) {
            $found = $clientStaff((int) $args['id']);
            $emp = $found === null ? null : $found[1]->find((int) $args['eid']);
            if ($emp === null) {
                return $notFound($req);
            }
            [$client, $employees] = $found;
            $empId = (int) $emp['id'];
            [$d, $errors] = $readStaff((array) $req->getParsedBody(), $employees, $empId);
            if ($d['access'] !== '' && $d['pin'] === '' && trim((string) $emp['pin']) === '') {
                $errors['pin'] = 'A PIN is needed to sign in.';
            }
            if ($errors !== []) {
                return $view->render($res->withStatus(422), 'admin/employee_form.php', [
                    'title' => 'Edit ' . $emp['name'], 'client' => $client, 'employee' => $emp,
                    'data' => $d, 'errors' => $errors,
                ]);
            }
            $saveStaff($employees, $empId, $d);
            $grants->setRole((int) $client['id'], $empId, $d['access'] ?: null);
            Flash::set('ok', $d['name'] . ' saved.');

            return $redirect($res, '/admin/clients/' . $client['id'] . '/employees');
        });

        /** Quick access change straight from the staff list. */
        $g->post('/clients/{id:[0-9]+}/employees/{eid:[0-9]+}/access', function (Request $req, Response $res, array $args) use ($grants, $clientStaff, $notFound, $redirect) {
            $found = $clientStaff((int) $args['id']);
            $emp = $found === null ? null : $found[1]->find((int) $args['eid']);
            if ($emp === null) {
                return $notFound($req);
            }
            $role = (string) (((array) $req->getParsedBody())['access'] ?? '');
            $role = in_array($role, ClientUserRepository::ROLES, true) ? $role : null;
            if ($role !== null && trim((string) $emp['pin']) === '') {
                Flash::set('err', $emp['name'] . ' has no PIN yet — edit them and set one first.');
            } else {
                $grants->setRole((int) $found[0]['id'], (int) $emp['id'], $role);
                Flash::set('ok', $emp['name'] . ': ' . ($role === null ? 'access removed.' : 'access set to ' . $role . '.'));
            }

            return $redirect($res, '/admin/clients/' . $found[0]['id'] . '/employees');
        });
    })->add(new SuperAdminMiddleware($auth, $basePath));
};
