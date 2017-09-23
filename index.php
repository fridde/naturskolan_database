<?php

require __DIR__.'/vendor/autoload.php';

use Fridde\Essentials;
use Fridde\Settings;

/** START OF BOOTSTRAP  */
Essentials::setBaseDir(__DIR__);
Essentials::setAppUrl();
Settings::setSettings();
if(SETTINGS['environment'] === 'dev'){
    Essentials::activateDebug(["no_tracy"]);
}
Essentials::activateGlobalFunctions();

$services[] = ['Naturskolan', 'Fridde\Naturskolan'];
$services[] = ['Router', 'AltoRouter', Essentials::getRoutes(), "/".basename(APP_URL)];
//$services[] = ['InfoLogger', Essentials::getLogger("Info")];
$services[] = ['Logger', Essentials::getLogger()];
$container = Essentials::registerSharedServices($services);

$em = $container->get('Naturskolan')->ORM->EM;
Essentials::registerDBLogger($em, Essentials::getLogger());

setlocale(LC_TIME, 'Swedish');
/** END OF BOOTSTRAP */

$request_url = rawurldecode($_SERVER['REQUEST_URI']);
$request_url = rtrim($request_url, '/\\');

$match = $container->get('Router')->match($request_url);
if ($match) {
    list($class, $method) = explode('#', $match["target"]);
    $controller_class = '\\Fridde\\Controller\\'.$class."Controller";
    $object = new $controller_class($match["params"]);
    $object->$method();
    exit();
} else {
    header($_SERVER["SERVER_PROTOCOL"].' 404 Not Found');
    echo "No match found. Requested url: ".PHP_EOL;
    var_dump($request_url);
    exit();
}
