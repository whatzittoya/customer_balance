<?php

declare(strict_types=1);

namespace App\Parent;

use PDO;
use Throwable;

/**
 * The client registry (parent DB `clients`): who each client is, the slug
 * their staff and customers reach them on, and which POS database is theirs.
 */
final class ClientRepository
{
    /** Slugs that would shadow the app's own paths. */
    public const RESERVED_SLUGS = ['admin', 'uploads', 'assets', 'vendor', 'config', 'src', 'templates', 'sql', 'setup', 'index.php'];

    /** Tables a client database must have to be paired. */
    public const REQUIRED_TABLES = ['tbl_customers', 'tbl_employees', 'tbl_point_transactions'];

    private const SYSTEM_DATABASES = ['information_schema', 'mysql', 'performance_schema', 'sys'];

    public function __construct(private PDO $pdo, private string $parentDbName)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT c.*, (SELECT COUNT(*) FROM client_users u WHERE u.client_id = c.id) AS user_count
             FROM clients c ORDER BY c.name'
        )->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<string,mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clients WHERE slug = :s LIMIT 1');
        $stmt->execute(['s' => $slug]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function slugTaken(string $slug, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM clients WHERE slug = :s';
        $params = ['s' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    /** @param array{name:string,slug:string,logo_path:?string,db_name:string,active:bool} $d */
    public function create(array $d): int
    {
        $this->pdo->prepare(
            'INSERT INTO clients (name, slug, logo_path, db_name, active, created_at, updated_at)
             VALUES (:name, :slug, :logo, :db, :active, NOW(), NOW())'
        )->execute([
            'name'   => $d['name'],
            'slug'   => $d['slug'],
            'logo'   => $d['logo_path'],
            'db'     => $d['db_name'],
            'active' => $d['active'] ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array{name:string,slug:string,logo_path:?string,db_name:string,active:bool} $d */
    public function update(int $id, array $d): void
    {
        $this->pdo->prepare(
            'UPDATE clients SET name = :name, slug = :slug, logo_path = :logo, db_name = :db,
                                active = :active, updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id'     => $id,
            'name'   => $d['name'],
            'slug'   => $d['slug'],
            'logo'   => $d['logo_path'],
            'db'     => $d['db_name'],
            'active' => $d['active'] ? 1 : 0,
        ]);
    }

    /**
     * Databases on this server a client could be paired with — everything the
     * app's MySQL user can see, minus MySQL's own schemas and the parent DB.
     *
     * @return array<int,string>
     */
    public function availableDatabases(): array
    {
        $skip = array_merge(self::SYSTEM_DATABASES, [$this->parentDbName]);

        return array_values(array_filter(
            $this->pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN),
            static fn ($db) => !in_array((string) $db, $skip, true)
        ));
    }

    /**
     * Why this database can't be paired, or null when it can. Checks it exists
     * and has the POS tables the app reads.
     */
    public function validateClientDb(string $dbName): ?string
    {
        if (!in_array($dbName, $this->availableDatabases(), true)) {
            return 'Database not found on this server.';
        }

        try {
            $in = implode(',', array_fill(0, count(self::REQUIRED_TABLES), '?'));
            $stmt = $this->pdo->prepare(
                "SELECT table_name FROM information_schema.tables
                 WHERE table_schema = ? AND table_name IN ($in)"
            );
            $stmt->execute(array_merge([$dbName], self::REQUIRED_TABLES));
            $found = array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $e) {
            return 'Could not inspect the database: ' . $e->getMessage();
        }

        $missing = array_diff(self::REQUIRED_TABLES, $found);

        return $missing === [] ? null : 'Missing table(s): ' . implode(', ', $missing) . '.';
    }
}
