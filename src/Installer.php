<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use Throwable;

/**
 * First-run setup: stores the MySQL credentials in config/local.php and creates
 * the parent database's tables from sql/parent_schema.sql.
 *
 * The wizard is only offered while the parent tables are missing AND no
 * config/installed.lock exists. The lock is written when setup succeeds, so a
 * later database outage can't re-open the wizard to strangers.
 */
final class Installer
{
    /** Tables the parent schema creates; all present means "installed". */
    public const TABLES = ['sa_admins', 'clients', 'client_users'];

    /** @param array<string,mixed> $config */
    public function __construct(private array $config, private string $root)
    {
    }

    private function lockFile(): string
    {
        return $this->root . '/config/installed.lock';
    }

    private function localFile(): string
    {
        return $this->root . '/config/local.php';
    }

    /** True while the first-run wizard should take over the whole site. */
    public function needed(): bool
    {
        if (is_file($this->lockFile())) {
            return false;
        }
        $db = (array) $this->config['db'];
        try {
            $pdo = $this->connect($db, (string) $db['parent_name']);

            return array_diff(self::TABLES, $this->tables($pdo)) !== [];
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * Connection settings to prefill the form with (never the password).
     *
     * @return array{host:string,port:string,user:string,parent_name:string}
     */
    public function defaults(): array
    {
        $db = (array) $this->config['db'];

        return [
            'host'        => (string) $db['host'],
            'port'        => (string) $db['port'],
            'user'        => (string) $db['user'],
            'parent_name' => (string) $db['parent_name'],
        ];
    }

    /**
     * Test the credentials, create the database if allowed, create the tables
     * and save everything to config/local.php.
     *
     * @param array<string,mixed> $in host, port, user, pass, parent_name
     * @return array{errors:array<string,string>,created:array<int,string>}
     */
    public function install(array $in): array
    {
        $db = [
            'host'        => trim((string) ($in['host'] ?? '')),
            'port'        => trim((string) ($in['port'] ?? '')),
            'user'        => trim((string) ($in['user'] ?? '')),
            'pass'        => (string) ($in['pass'] ?? ''),
            'parent_name' => trim((string) ($in['parent_name'] ?? '')),
            'charset'     => 'utf8mb4',
        ];
        $errors = [];

        if (!preg_match('/^[A-Za-z0-9._:-]{1,255}$/', $db['host'])) {
            $errors['host'] = 'Enter a host name or IP address, e.g. 127.0.0.1 or localhost.';
        }
        if (!preg_match('/^[0-9]{1,5}$/', $db['port'])) {
            $errors['port'] = 'Digits only, usually 3306.';
        }
        if ($db['user'] === '' || strlen($db['user']) > 80) {
            $errors['user'] = 'Required.';
        }
        if (!preg_match('/^[A-Za-z0-9_$-]{1,64}$/', $db['parent_name'])) {
            $errors['parent_name'] = 'Letters, digits, _ $ - only (up to 64).';
        }
        if ($errors !== []) {
            return ['errors' => $errors, 'created' => []];
        }

        try {
            $server = $this->connect($db, null);
        } catch (PDOException $e) {
            return ['errors' => ['form' => 'Could not connect to MySQL: ' . $e->getMessage()], 'created' => []];
        }

        // Allowed on a local server, usually denied on shared hosting where
        // the database is made in cPanel first — so a refusal isn't an error.
        try {
            $server->exec('CREATE DATABASE IF NOT EXISTS `' . $db['parent_name'] . '` CHARACTER SET utf8mb4');
        } catch (PDOException) {
        }

        try {
            $pdo = $this->connect($db, $db['parent_name']);
        } catch (PDOException $e) {
            return ['errors' => [
                'parent_name' => 'Cannot open this database (' . $e->getMessage() . '). Create it first and grant this user access to it.',
            ], 'created' => []];
        }

        try {
            $before = $this->tables($pdo);
            foreach ($this->statements() as $sql) {
                $pdo->exec($sql);
            }
            $created = array_values(array_diff($this->tables($pdo), $before));
        } catch (PDOException $e) {
            return ['errors' => ['form' => 'Creating the tables failed: ' . $e->getMessage()], 'created' => []];
        }

        $written = $this->writeLocal($db);
        if ($written !== true) {
            return ['errors' => ['form' => $written], 'created' => $created];
        }
        @file_put_contents($this->lockFile(), gmdate('c') . "\n");

        return ['errors' => [], 'created' => $created];
    }

    /** @param array<string,mixed> $db */
    private function connect(array $db, ?string $name): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', $db['host'], $db['port'], $db['charset'] ?? 'utf8mb4');
        if ($name !== null) {
            $dsn .= ';dbname=' . $name;
        }

        return new PDO($dsn, (string) $db['user'], (string) $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
    }

    /** @return array<int,string> */
    private function tables(PDO $pdo): array
    {
        return $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return array<int,string> The schema file split into single statements. */
    private function statements(): array
    {
        $sql = (string) file_get_contents($this->root . '/sql/parent_schema.sql');
        $sql = implode("\n", array_filter(
            explode("\n", $sql),
            static fn (string $line): bool => !str_starts_with(ltrim($line), '--')
        ));

        return array_values(array_filter(array_map('trim', explode(';', $sql)), static fn (string $s): bool => $s !== ''));
    }

    /**
     * Merge the credentials into config/local.php, keeping any other keys.
     * An existing app_secret is kept (changing it breaks printed QR codes);
     * a missing one is generated.
     *
     * @param array<string,mixed> $db
     * @return true|string true, or an error message that includes the file to create by hand
     */
    private function writeLocal(array $db): true|string
    {
        $local = is_file($this->localFile()) ? (array) require $this->localFile() : [];
        $local['db'] = array_replace((array) ($local['db'] ?? []), [
            'host'        => $db['host'],
            'port'        => $db['port'],
            'parent_name' => $db['parent_name'],
            'user'        => $db['user'],
            'pass'        => $db['pass'],
        ]);
        if (strlen((string) $this->config['app_secret']) < 16) {
            $local['app_secret'] = bin2hex(random_bytes(32));
        }

        $php = "<?php\n\n"
            . "/** Written by the first-run setup. Overrides config/config.php; gitignored. */\n\n"
            . "declare(strict_types=1);\n\n"
            . 'return ' . var_export($local, true) . ";\n";

        $ok = is_writable(dirname($this->localFile())) || is_file($this->localFile()) && is_writable($this->localFile());
        if ($ok && @file_put_contents($this->localFile(), $php, LOCK_EX) !== false) {
            @chmod($this->localFile(), 0600);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($this->localFile(), true);
            }

            return true;
        }

        return "The tables were created, but config/local.php could not be written (is config/ writable?). Create it by hand with:\n\n" . $php;
    }
}
