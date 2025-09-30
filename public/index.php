<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization,x-access-key');

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

ini_set('max_execution_time', 180000);
ini_set('upload_max_filesize ', 180000);
ini_set('post_max_size ', 180000);

define('STDIN', fopen("php://stdin", "r"));
define('LARAVEL_START', microtime(true));


if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}


require __DIR__ . '/../vendor/autoload.php';


$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
