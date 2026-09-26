<?php

declare(strict_types=1);

namespace App;

use App\Parent\AdminRepository;

/**
 * Session login for super admins (parent DB `sa_admins`, password hashes).
 */
final class SuperAdminAuth
{
    private const KEY = 'sa';

    public function __construct(private AdminRepository $admins)
    {
    }

    public function attempt(string $username, string $password): bool
    {
        $admin = $this->admins->findByUsername(trim($username));
        if ($admin === null || !password_verify($password, (string) $admin['password_hash'])) {
            return false;
        }

        $this->login((int) $admin['id'], (string) $admin['username']);

        return true;
    }

    public function login(int $id, string $username): void
    {
        session_regenerate_id(true);
        $_SESSION[self::KEY] = ['id' => $id, 'username' => $username];
    }

    public function logout(): void
    {
        unset($_SESSION[self::KEY]);
    }

    public function check(): bool
    {
        return isset($_SESSION[self::KEY]);
    }

    /** @return array{id:int,username:string}|null */
    public function user(): ?array
    {
        return $_SESSION[self::KEY] ?? null;
    }
}
