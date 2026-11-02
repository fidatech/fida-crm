<?php
define('LARAVEL_START', microtime(true));
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) require $maintenance;
require __DIR__.'/../vendor/autoload.php';
(require __DIR__.'/../bootstrap/app.php')->handleRequest(Illuminate\Http\Request::capture());
