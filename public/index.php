<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// When Apache serves the project from /app and internally rewrites to public/,
// remove the deployment prefix before Laravel matches the route.
$deploymentPrefix = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')));
if ($deploymentPrefix !== '/' && str_starts_with($_SERVER['REQUEST_URI'] ?? '/', $deploymentPrefix)) {
    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], strlen($deploymentPrefix)) ?: '/';
}

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
