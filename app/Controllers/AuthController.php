<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Division;
use App\Models\User;
use App\Services\AuditLogger;

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
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $divisionRaw = $_POST['division_id'] ?? '';
        $divisionId = $divisionRaw === 'super' ? null : (int)$divisionRaw;

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
