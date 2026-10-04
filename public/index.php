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
use App\Controllers\MarketReturnController;
use App\Controllers\MarketReturnMappingController;
use App\Controllers\AdminController;
use App\Controllers\EmailNotificationController;

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/email-notifications', [EmailNotificationController::class, 'index']);
$router->post('/admin/email-notifications/save', [EmailNotificationController::class, 'save']);
$router->post('/admin/email-notifications/test', [EmailNotificationController::class, 'test']);
$router->post('/admin/email-notifications/sample', [EmailNotificationController::class, 'sample']);
$router->post('/admin/email-notifications/run', [EmailNotificationController::class, 'runNow']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/stocks', [StockController::class, 'index']);
$router->get('/stocks/create', [StockController::class, 'create']);
$router->post('/stocks/store', [StockController::class, 'store']);
$router->get('/stocks/edit', [StockController::class, 'edit']);
$router->post('/stocks/update', [StockController::class, 'update']);
$router->post('/stocks/delete', [StockController::class, 'delete']);
$router->post('/stocks/delete-selected', [StockController::class, 'deleteSelected']);
$router->post('/stocks/bulk-delete', [StockController::class, 'bulkDelete']);
$router->get('/stocks/import', [StockController::class, 'importForm']);
$router->post('/stocks/import', [StockController::class, 'importCsv']);
$router->get('/stocks/import-template', [StockController::class, 'importTemplate']);


$router->get('/market-return', [MarketReturnController::class, 'index']);
$router->get('/market-return/create', [MarketReturnController::class, 'create']);
$router->post('/market-return/store', [MarketReturnController::class, 'store']);
$router->get('/market-return/edit', [MarketReturnController::class, 'edit']);
$router->post('/market-return/update', [MarketReturnController::class, 'update']);
$router->post('/market-return/delete', [MarketReturnController::class, 'delete']);
$router->post('/market-return/bulk-delete', [MarketReturnController::class, 'bulkDelete']);
$router->get('/market-return/import', [MarketReturnController::class, 'importForm']);
$router->post('/market-return/import', [MarketReturnController::class, 'importFile']);
$router->get('/market-return/import-template', [MarketReturnController::class, 'importTemplate']);
$router->get('/market-return/csv-template', [MarketReturnController::class, 'csvTemplate']);
$router->get('/market-return/mapping', [MarketReturnMappingController::class, 'index']);
$router->post('/market-return/mapping/store', [MarketReturnMappingController::class, 'store']);
$router->post('/market-return/mapping/delete', [MarketReturnMappingController::class, 'delete']);

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
