<?php

declare(strict_types=1);

namespace App\Parent;

use PDO;

/**
 * Super admin accounts (parent DB `sa_admins`).
 */
final class AdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** True until the first super admin exists — gates /admin/setup. */
    public function isEmpty(): bool
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM sa_admins')->fetchColumn() === 0;
    }

    /** @return array<string,mixed>|null */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sa_admins WHERE username = :u LIMIT 1');
        $stmt->execute(['u' => $username]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(string $username, string $password): int
    {
        $this->pdo->prepare(
            'INSERT INTO sa_admins (username, password_hash, created_at) VALUES (:u, :h, NOW())'
        )->execute(['u' => $username, 'h' => password_hash($password, PASSWORD_DEFAULT)]);

        return (int) $this->pdo->lastInsertId();
    }
}
