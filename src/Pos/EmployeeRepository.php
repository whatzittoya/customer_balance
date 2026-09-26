<?php

declare(strict_types=1);

namespace App\Pos;

use PDO;

/**
 * A client's POS `tbl_employees`: login credentials (name/code + PIN) and the
 * staff list the super admin manages. Whether an employee may use this app at
 * all is decided separately, by a grant in the parent DB (client_users).
 */
final class EmployeeRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Find a working employee by (name OR code) + pin. Returns null on no match.
     * Resigned and deactivated staff are refused.
     *
     * @return array<string,mixed>|null
     */
    public function authenticate(string $username, string $pin): ?array
    {
        $sql = 'SELECT id, name, code, jobTitle
                FROM tbl_employees
                WHERE (name = :u OR code = :u2) AND pin = :pin AND pin <> \'\'
                  AND active = b\'1\' AND resigned = b\'0\'
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['u' => $username, 'u2' => $username, 'pin' => $pin]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT id, name, code, jobTitle, pin, phone1, email,
                    CAST(active AS UNSIGNED) AS active,
                    CAST(resigned AS UNSIGNED) AS resigned
             FROM tbl_employees ORDER BY name'
        )->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, code, jobTitle, pin, phone1, email,
                    CAST(active AS UNSIGNED) AS active,
                    CAST(resigned AS UNSIGNED) AS resigned
             FROM tbl_employees WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Is this name or code already used by someone else?
     *
     * Login matches `name = :u OR code = :u`, so a value that collides with
     * either column on another row would make the login ambiguous.
     */
    public function loginNameTaken(string $value, ?int $exceptId = null): bool
    {
        if ($value === '') {
            return false;
        }
        $sql = 'SELECT 1 FROM tbl_employees WHERE (name = :v1 OR code = :v2)';
        $params = ['v1' => $value, 'v2' => $value];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Add a member of staff.
     *
     * `active`, `driver` and `resigned` are NOT NULL with no default in the POS
     * schema, so they have to be written explicitly.
     *
     * @param array<string,mixed> $d name, code, jobTitle, pin, phone, email, active, resigned
     */
    public function create(array $d): int
    {
        // bit(1) columns must be written as b'1'/b'0' literals: PDO binds a PHP
        // int as the *string* "1", which MySQL reads as the character '1'
        // (0x31) and rejects with "Data too long for column". Both values are
        // booleans resolved here, so nothing user-supplied reaches the SQL.
        $active   = !empty($d['active']) ? "b'1'" : "b'0'";
        $resigned = !empty($d['resigned']) ? "b'1'" : "b'0'";

        $sql = "INSERT INTO tbl_employees
                    (active, driver, resigned, name, code, jobTitle, pin, phone1, email, joined)
                VALUES
                    ($active, b'0', $resigned, :name, :code, :jobTitle, :pin, :phone, :email, CURDATE())";
        $this->pdo->prepare($sql)->execute([
            'name'     => $d['name'],
            'code'     => $d['code'] ?? null,
            'jobTitle' => $d['jobTitle'] ?? null,
            'pin'      => $d['pin'] ?? null,
            'phone'    => $d['phone'] ?? null,
            'email'    => $d['email'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update a member of staff. A null `pin` leaves the existing one alone, so
     * the edit form can offer "leave blank to keep current".
     *
     * @param array<string,mixed> $d
     */
    public function update(int $id, array $d): void
    {
        // See create(): bit(1) needs literals, not bound parameters.
        $active   = !empty($d['active']) ? "b'1'" : "b'0'";
        $resigned = !empty($d['resigned']) ? "b'1'" : "b'0'";

        $sql = "UPDATE tbl_employees SET
                    name     = :name,
                    code     = :code,
                    jobTitle = :jobTitle,
                    phone1   = :phone,
                    email    = :email,
                    active   = $active,
                    resigned = $resigned";
        $params = [
            'id'       => $id,
            'name'     => $d['name'],
            'code'     => $d['code'] ?? null,
            'jobTitle' => $d['jobTitle'] ?? null,
            'phone'    => $d['phone'] ?? null,
            'email'    => $d['email'] ?? null,
        ];
        if (($d['pin'] ?? null) !== null) {
            $sql .= ', pin = :pin';
            $params['pin'] = $d['pin'];
        }
        $sql .= ' WHERE id = :id';

        $this->pdo->prepare($sql)->execute($params);
    }
}
