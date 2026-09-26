<?php
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\StockController;
use App\Controllers\UserController;
use App\Controllers\DivisionController;
use App\Controllers\LogController;
use App\Controllers\GradeCriteriaController;

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/stocks', [StockController::class, 'index']);
$router->get('/stocks/create', [StockController::class, 'create']);
$router->post('/stocks/store', [StockController::class, 'store']);
$router->get('/stocks/edit', [StockController::class, 'edit']);
$router->post('/stocks/update', [StockController::class, 'update']);
$router->post('/stocks/delete', [StockController::class, 'delete']);
$router->get('/stocks/import', [StockController::class, 'importForm']);
$router->post('/stocks/import', [StockController::class, 'importCsv']);
$router->get('/stocks/import-template', [StockController::class, 'importTemplate']);

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/edit', [UserController::class, 'edit']);
$router->post('/users/store', [UserController::class, 'store']);
$router->post('/users/update', [UserController::class, 'update']);
$router->post('/users/toggle', [UserController::class, 'toggle']);

$router->get('/divisions', [DivisionController::class, 'index']);
$router->post('/divisions/store', [DivisionController::class, 'store']);
$router->post('/divisions/toggle', [DivisionController::class, 'toggle']);


$router->get('/grade-criteria', [GradeCriteriaController::class, 'index']);
$router->post('/grade-criteria/update', [GradeCriteriaController::class, 'update']);

$router->get('/logs', [LogController::class, 'index']);
$router->get('/logs/export', [LogController::class, 'export']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
