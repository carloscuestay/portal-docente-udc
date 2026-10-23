<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Primera instalación: si el .env existe y APP_KEY está vacía, se genera automáticamente.
if (is_file($env = __DIR__.'/../.env') && ! is_file(__DIR__.'/../bootstrap/cache/config.php')) {
    $contents = (string) file_get_contents($env);
    if (preg_match('/^APP_KEY=\s*$/m', $contents) && is_writable($env)) {
        file_put_contents($env, preg_replace('/^APP_KEY=\s*$/m', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), $contents));
    }
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
