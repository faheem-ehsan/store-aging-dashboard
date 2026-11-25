<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LoginThrottle;

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) redirect('/');
        View::render('auth/login', [
            'title' => 'Login',
            'divisions' => (new Division())->all(true),
        ]);
    }

    public function login(): void
    {
        verify_csrf();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

        if ((new LoginThrottle())->isBlocked($email, $ip)) {
            AuditLogger::log(
                'LOGIN_BLOCKED',
                'Blocked login attempt for ' . ($email ?: 'blank email') . ' (too many failed attempts).',
                'auth',
                null,
                [],
                ['id' => null, 'name' => 'Login Attempt', 'email' => $email, 'role' => 'guest', 'division_id' => null]
            );
            flash('error', 'Too many failed sign-in attempts. Please wait ' . LoginThrottle::WINDOW_MINUTES . ' minutes and try again.');
            redirect('/login');
        }

        $divisionRaw = $_POST['division_id'] ?? '';
        if ($divisionRaw === 'super') {
            $divisionId = null;
        } elseif ($divisionRaw === 'manager') {
            $divisionId = -1;
        } elseif ($divisionRaw === 'all') {
            $divisionId = 0;
        } else {
            $divisionId = (int)$divisionRaw;
        }

        $user = (new User())->authenticate($email, $password, $divisionId);
        if (!$user) {
            AuditLogger::log(
                'LOGIN_FAILED',
                'Failed login attempt for ' . ($email ?: 'blank email') . '.',
                'auth',
                null,
                ['selected_division_id' => $divisionId],
                ['id' => null, 'name' => 'Login Attempt', 'email' => $email, 'role' => 'guest', 'division_id' => $divisionId]
            );
            flash('error', 'Invalid email, password, or division selection.');
            redirect('/login');
        }
        Auth::login((int)$user['id']);
        $loggedIn = Auth::user() ?? $user;
        AuditLogger::log('LOGIN_SUCCESS', 'User signed in successfully.', 'auth', (int)$user['id'], [], $loggedIn);
        redirect('/');
    }

    public function logout(): void
    {
        verify_csrf();
        $user = Auth::user();
        if ($user) {
            AuditLogger::log('LOGOUT', 'User signed out.', 'auth', (int)$user['id'], [], $user);
        }
        Auth::logout();
        redirect('/login');
    }
}
