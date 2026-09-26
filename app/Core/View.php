<?php
namespace App\Core;

class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = APP_PATH . '/Views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        require APP_PATH . '/Views/layouts/header.php';
        require $viewFile;
        require APP_PATH . '/Views/layouts/footer.php';
    }
}
