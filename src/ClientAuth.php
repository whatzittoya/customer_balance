<?php

declare(strict_types=1);

namespace App;

use App\Parent\ClientUserRepository;
use App\Pos\EmployeeRepository;

/**
 * Session login for a client's staff.
 *
 * Credentials are the client's own `tbl_employees` (name/code + PIN), but a
 * matching employee only gets in with a grant in the parent DB
 * (client_users), which also carries the role. Sessions are kept per client,
 * so signing in at one client's URL never opens another's.
 */
final class ClientAuth
{
    private const KEY = 'client_users';

    public function __construct(private ClientUserRepository $grants)
    {
    }

    /**
     * @return string|null null on success, otherwise the reason to show.
     */
    public function attempt(EmployeeRepository $employees, int $clientId, string $username, string $pin): ?string
    {
        $emp = $employees->authenticate(trim($username), trim($pin));
        if ($emp === null) {
            return 'Wrong name/code or PIN.';
        }

        $role = $this->grants->roleFor($clientId, (int) $emp['id']);
        if ($role === null) {
            return 'This account has no access to the balance app. Ask your administrator.';
        }

        session_regenerate_id(true);
        $_SESSION[self::KEY][$clientId] = [
            'id'   => (int) $emp['id'],
            'name' => (string) ($emp['name'] ?: $emp['code'] ?: 'Staff'),
            'role' => $role,
        ];

        return null;
    }

    public function logout(int $clientId): void
    {
        unset($_SESSION[self::KEY][$clientId]);
    }

    /** @return array{id:int,name:string,role:string}|null */
    public function user(int $clientId): ?array
    {
        return $_SESSION[self::KEY][$clientId] ?? null;
    }
}
