<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PDO connections: one to the parent database, one per client database.
 *
 * Every connection uses the same host and credentials — clients live on the
 * same server. A client database name always comes from the `clients` row,
 * never from the request.
 */
final class Database
{
    /** @var array<string,PDO> keyed by database name */
    private static array $pool = [];

    /** @param array<string,string> $config The 'db' config array. */
    public static function parent(array $config): PDO
    {
        return self::connect($config, (string) $config['parent_name']);
    }

    /** @param array<string,string> $config The 'db' config array. */
    public static function client(array $config, string $dbName): PDO
    {
        return self::connect($config, $dbName);
    }

    /** @param array<string,string> $config */
    private static function connect(array $config, string $dbName): PDO
    {
        if (isset(self::$pool[$dbName])) {
            return self::$pool[$dbName];
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $dbName,
            $config['charset']
        );

        try {
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Database connection failed (' . $dbName . '): ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }

        return self::$pool[$dbName] = $pdo;
    }
}
