<?php
namespace App\Core;

use App\Models\User;

class Auth
{
    private static ?array $cachedUser = null;

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) return self::$cachedUser;
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) return null;
        self::$cachedUser = (new User())->find((int)$id);
        return self::$cachedUser;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if (!$user) {
            redirect('/login');
        }
        return $user;
    }

    public static function isGlobalOperator(array $user): bool
    {
        return in_array($user['role'] ?? '', ['super_admin', 'store_manager'], true);
    }

    public static function canReadAllDivisions(array $user): bool
    {
        return self::isGlobalOperator($user)
            || (($user['role'] ?? '') === 'user' && empty($user['division_id']));
    }

    public static function canManageOperational(array $user): bool
    {
        return in_array($user['role'] ?? '', ['super_admin', 'store_manager', 'division_admin'], true);
    }

    public static function requireGlobalOperator(): array
    {
        $user = self::requireLogin();
        if (!self::isGlobalOperator($user)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            exit;
        }
        return $user;
    }

    public static function requireSuperAdmin(): array
    {
        $user = self::requireLogin();
        if ($user['role'] !== 'super_admin') {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            exit;
        }
        return $user;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        self::$cachedUser = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$cachedUser = null;
    }
}
