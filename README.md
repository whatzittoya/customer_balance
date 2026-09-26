# Quinos Customer Balance

A multi-client customer balance viewer for Quinos POS databases. Built with Slim 4 and MySQL (PDO), and runs on ordinary cPanel hosting.

One install serves many clients:

* A **parent database** (`customer_balance_db`) holds the super admins, the client registry and login grants.
* Every **client** has its own Quinos POS database on the same MySQL server, using the same credentials. The app reads `tbl_customers` and `tbl_point_transactions` there, and manages `tbl_employees`.

## Who does what

| Who | Where | What |
| --- | --- | --- |
| Super admin | `/admin` | Create clients (name, URL slug, logo), pair each one with its POS database, and manage its staff in that client's `tbl_employees`: add/edit, PIN, active/resigned, and **app access** (none / cashier / admin). |
| Client staff (admin, cashier) | `/{slug}` | Sign in with their POS name/code and PIN. The dashboard lists active customers and their balance (`tbl_customers.balance`) with search. A customer page shows the full history from `tbl_point_transactions`. Staff can make one QR code, or many at once as a zip. |
| Customer | `/{slug}/c/{token}` | Scans their QR code and gets a mobile page with the client logo and name, a greeting, their code, their balance in large text, and their history (collapsed by default). |

Staff can only sign in when the super admin has granted them access. A valid POS PIN is not enough on its own. Sessions are per client, so signing in at one client's URL never opens another client's pages. Admins and cashiers can do the same things for now; the role is stored so it can be used to split permissions later.

### QR codes

A QR code opens `/{slug}/c/{token}`. The token holds the customer code plus an HMAC over (client id, code), signed with `app_secret`. Nobody can type in another customer's code, and a link only works for the client it was made for. The client logo sits in the middle of the code (error correction level H), with the customer code printed underneath.

* **Changing `app_secret`, or a client's slug, breaks every QR code already printed.**
* Customers without a code in the POS can't get a QR code.
* When several active customers share one code, the app marks it as **shared**. Their history is combined and the QR code opens the oldest customer with that code. Give each customer a unique code in the POS to fix this.

## Deploy on cPanel

1. Upload the folder to `public_html/` (the site root) or a subfolder such as `public_html/balance/`. `vendor/` is committed, so Composer isn't needed on the server. The base path is detected automatically.
2. In **MySQL Databases**, create `customer_balance_db` (or any name) and make sure the app's MySQL user has access to it **and** to every client POS database.
3. Make sure `config/` is writable (755 is usually enough on cPanel), then open `<your-url>/setup`. Enter the MySQL host, user, password and the main database name. The page connects, creates the tables from `sql/parent_schema.sql`, and saves the credentials plus a freshly generated `app_secret` to `config/local.php`. You can also do this by hand: import `sql/parent_schema.sql` in phpMyAdmin and copy `config/local.php.example` to `config/local.php`.
   * The setup page closes itself once the tables exist (and `config/installed.lock` keeps it closed if the database is ever down).
   * `app_secret` signs QR links; keep it safe. Optionally set `base_url` (e.g. `https://example.com/balance`) in `config/local.php` if QR links come out with the wrong scheme or host behind a proxy.
4. Next you land on `/admin/setup` to create the first super admin.
5. In **Select PHP Version**, use PHP 8.1 or newer and enable the **gd**, **zip**, **pdo_mysql** and **fileinfo** extensions. The Clients page warns if gd or zip is missing.
6. Make sure `uploads/logos/` is writable (755 is usually enough on cPanel).
7. Add a client, pick its database, upload a logo, then grant its staff access on the **Staff** page. Staff sign in at `<your-url>/<slug>`.

### Local dev

```
php -S 127.0.0.1:8080 index.php
```

## Configuration

`config/config.php` holds the defaults. `config/local.php` is gitignored and overrides any key (recursively). Environment variables work too:

| Key | Env var | Default |
| --- | --- | --- |
| App name (super admin header) | `APP_NAME` | `Quinos Balance` |
| DB host / port | `DB_HOST` / `DB_PORT` | `127.0.0.1` / `3306` |
| Parent DB name | `DB_PARENT` | `customer_balance_db` |
| DB user / password | `DB_USER` / `DB_PASS` | `root` / *(empty)* |
| QR signing secret (**required**) | `APP_SECRET` | *(empty: app refuses to start)* |
| Public base URL for QR links | `APP_URL` | *(taken from the request)* |
| Time zone | `APP_TIMEZONE` | `Asia/Makassar` |
| Currency symbol / decimals | `MONEY_SYMBOL` / `MONEY_DECIMALS` | `Rp` / `0` |
| Decimal / thousands mark | `MONEY_DEC_POINT` / `MONEY_THOUSANDS` | `,` / `.` |

## Layout

```
.htaccess          front controller; blocks config/src/templates/vendor/sql
index.php          bootstrap: config, parent PDO, services, renderers, routes
config/            config.php (+ gitignored local.php)
sql/parent_schema.sql   sa_admins, clients, client_users
uploads/logos/     client logos (scripts can't run here — see uploads/.htaccess)
src/
  Database.php               parent + per-client PDO connections
  Installer.php              first-run /setup: save credentials, create parent tables
  SuperAdminAuth.php         /admin login (password hashes)
  ClientAuth.php             /{slug} login: tbl_employees PIN + client_users grant
  QrService.php              signed tokens, QR PNG with logo, zip
  Flash.php, Money.php
  Parent/                    AdminRepository, ClientRepository, ClientUserRepository
  Pos/                       EmployeeRepository, CustomerRepository, PointTransactionRepository
  Middleware/                SuperAdminMiddleware, ClientResolver, ClientAuthMiddleware
  routes/install.php         /setup (only while not installed)
  routes/admin.php           /admin/…
  routes/client.php          /{slug}/… (staff pages + public customer page)
templates/
  _styles.php, _scripts.php  shared blue-violet UI
  install/  admin/  client/  public/
```

## Routes

| Method | Path | Who |
| --- | --- | --- |
| GET/POST | `/setup` | only while the parent tables are missing: MySQL credentials + create tables |
| GET/POST | `/admin/setup` | only while no super admin exists |
| GET/POST | `/admin/login`, GET `/admin/logout` | anyone |
| GET | `/admin` | super admin: client list |
| GET/POST | `/admin/clients/new`, `/admin/clients/{id}/edit` | super admin |
| GET | `/admin/clients/{id}/employees` | super admin: client staff |
| GET/POST | `/admin/clients/{id}/employees/new`, `…/{eid}/edit`, POST `…/{eid}/access` | super admin |
| GET/POST | `/{slug}/login`, GET `/{slug}/logout` | anyone |
| GET | `/{slug}/customers`, `/{slug}/customers/{id}` | client staff |
| GET | `/{slug}/customers/{id}/qr.png` (`?dl=1` to download) | client staff |
| GET | `/{slug}/qr`, POST `/{slug}/qr/zip` | client staff |
| GET | `/{slug}/c/{token}` | public (from a QR code) |

Slugs `admin`, `uploads`, `assets`, `vendor`, `config`, `src`, `templates`, `sql` and `setup` are reserved.
