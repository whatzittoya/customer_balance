<?php

declare(strict_types=1);

namespace App\Parent;

use PDO;

/**
 * Login grants (parent DB `client_users`): which employees of a client may sign
 * in at that client's URL, and as admin or cashier. The employee itself lives
 * in the client's own `tbl_employees`; this only holds its id and role.
 */
final class ClientUserRepository
{
    public const ROLES = ['admin', 'cashier'];

    public function __construct(private PDO $pdo)
    {
    }

    public function roleFor(int $clientId, int $employeeId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT role FROM client_users WHERE client_id = :c AND employee_id = :e LIMIT 1'
        );
        $stmt->execute(['c' => $clientId, 'e' => $employeeId]);
        $role = $stmt->fetchColumn();

        return $role === false ? null : (string) $role;
    }

    /** @return array<int,string> employee_id => role */
    public function rolesForClient(int $clientId): array
    {
        $stmt = $this->pdo->prepare('SELECT employee_id, role FROM client_users WHERE client_id = :c');
        $stmt->execute(['c' => $clientId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['employee_id']] = (string) $row['role'];
        }

        return $out;
    }

    /** Grant, change or (with null) revoke an employee's access. */
    public function setRole(int $clientId, int $employeeId, ?string $role): void
    {
        if ($role === null || !in_array($role, self::ROLES, true)) {
            $this->pdo->prepare('DELETE FROM client_users WHERE client_id = :c AND employee_id = :e')
                ->execute(['c' => $clientId, 'e' => $employeeId]);

            return;
        }

        $this->pdo->prepare(
            'INSERT INTO client_users (client_id, employee_id, role, created_at)
             VALUES (:c, :e, :r, NOW())
             ON DUPLICATE KEY UPDATE role = VALUES(role)'
        )->execute(['c' => $clientId, 'e' => $employeeId, 'r' => $role]);
    }
}
