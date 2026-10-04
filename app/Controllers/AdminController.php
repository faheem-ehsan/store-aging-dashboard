<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

class AdminController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();

        View::render('admin/index', [
            'title' => 'Administration',
        ]);
    }
}
