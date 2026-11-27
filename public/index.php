<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Publicación en un subdirectorio sin "/public" en la URL (ver .htaccess de la raíz del proyecto):
// si la URL pedida no incluye "/public", la ruta base de la aplicación se calcula sin esa carpeta.
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$uri = $_SERVER['REQUEST_URI'] ?? '';

if (str_ends_with($script, '/public/index.php') && ! str_starts_with($uri, substr($script, 0, -strlen('/index.php')))) {
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = substr($script, 0, -strlen('/public/index.php')).'/index.php';
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
