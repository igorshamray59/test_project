<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\FilesController;
use App\Controllers\ReportsController;
use App\Controllers\LogsController;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'FilesController::index');

// Маршруты для взаимодействия с файлами
$routes->get('/upload', 'FilesController::upload');
$routes->post('/upload', 'FilesController::upload');
$routes->get('/download/(:num)', 'FilesController::download/$1');
$routes->get('/delete/(:num)', 'FilesController::delete/$1');
$routes->get('/view/(:num)', 'FilesController::view/$1');

// Маршруты для изменения строк
$routes->post('/add-row/(:num)', 'FilesController::addRow/$1');
$routes->post('/edit-row/(:num)', 'FilesController::editRow/$1');
$routes->post('/delete-row/(:num)', 'FilesController::deleteRow/$1');

// Маршруты для отчетов
$routes->get('/export-excel/(:num)', 'ReportsController::exportExcel/$1');
$routes->get('/generate-pdf/(:num)', 'ReportsController::generatePdf/$1');

// Маршруты для журнала
$routes->get('/logs', 'LogsController::logs');
$routes->get('/file-logs/(:num)', 'LogsController::fileLogs/$1');