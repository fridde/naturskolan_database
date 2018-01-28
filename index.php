<?php

require __DIR__.'/vendor/autoload.php';

use Fridde\Essentials;
use Fridde\Settings;

$controller_namespace = '\\Fridde\\Controller\\';
$default_controller_method = 'handleRequest';

/** START OF BOOTSTRAP  */
Essentials::setBaseDir(__DIR__);
Essentials::setAppUrl();
Settings::setSettings();
if(SETTINGS['environment'] === 'dev'){
    Essentials::activateDebug(["no_tracy"]);
}
Essentials::activateGlobalFunctions();

$services[] = ['Naturskolan', 'Fridde\Naturskolan'];
$base_url = "/" . basename(APP_URL);
$services[] = ['Router', 'AltoRouter', Essentials::getRoutes(), $base_url];
$services[] = ['Logger', Essentials::getLogger()];
$container = Essentials::registerSharedServices($services);

$em = $container->get('Naturskolan')->ORM->EM;
Essentials::registerDBLogger($em, Essentials::getLogger());

setlocale(LC_TIME, 'swedish');
\Carbon\Carbon::setUtf8(true);

/** END OF BOOTSTRAP */

$request_url = rawurldecode($_SERVER['REQUEST_URI']);
$request_url = rtrim($request_url, '/\\');

$match = $container->get('Router')->match($request_url);
if ($match) {
    $class_and_method = explode('#', $match["target"]);
    $controller_class = $controller_namespace . $class_and_method[0] . "Controller";
    $object = new $controller_class($match["params"]);
    $method = $class_and_method[1] ?? $default_controller_method;
    call_user_func([$object, $method]);
    exit();
} else {
    header($_SERVER["SERVER_PROTOCOL"].' 404 Not Found');
    echo "No match found. Requested url: ".PHP_EOL;
    var_dump($request_url);
    exit();
}
