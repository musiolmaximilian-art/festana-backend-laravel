<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

if (! file_exists(__DIR__.'/../vendor/autoload.php')) {
    echo "Please run 'composer install' to install the dependencies.".PHP_EOL;
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
