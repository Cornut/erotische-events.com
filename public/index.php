<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = preg_match('/public_html/', __DIR__) ? __DIR__.'/../laravel-app' : __DIR__.'/..';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = "$appPath/storage/framework/maintenance.php")) {
    require $maintenance;
}

// Register the Composer autoloader...
require "$appPath/vendor/autoload.php";

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once "$appPath/bootstrap/app.php";

// index.php always lives in the public directory. On shared hosting that is the
// web root (public_html) rather than the app's own public/, so point
// public_path() / @vite / asset() at it explicitly — otherwise @vite looks for
// the manifest under laravel-app/public/build and 500s with "manifest not found".
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
