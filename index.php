<?php

require __DIR__ . '/vendor/autoload.php';

use Fridde\Essentials;
use Fridde\Settings;

/** START OF BOOTSTRAP  */
Essentials::setBaseDir(__DIR__);
Essentials::setAppUrl();
Settings::setSettings();
Essentials::activateDebug(["tracy"]);
Essentials::activateGlobalFunctions();
var_dump(basename(APP_URL));
$services[] = ['Naturskolan', 'Fridde\Naturskolan'];
$services[] = ['Router', 'AltoRouter', Essentials::getRoutes(), "/" . basename(APP_URL)];
$services[] = ['InfoLogger', Essentials::getLogger("Info")];
$services[] = ['ErrorLogger', Essentials::getLogger("Error")];
$container = Essentials::registerSharedServices($services);

$em = $container->get('Naturskolan')->ORM->EM;
Essentials::registerDBLogger($em, Essentials::getLogger("Error"));

setlocale(LC_TIME, 'Swedish');

$router = $container->get('Router');

/** END OF BOOTSTRAP */

$request_url = rawurldecode($_SERVER['REQUEST_URI']);
$request_url = rtrim($request_url, '/\\');
var_dump($request_url);
$match = $router->match($request_url);
if($match){
	list($class, $method) = explode('#', $match["target"]);
	$controller_class = '\\Fridde\\Controller\\' . $class . "Controller";
	$object = new $controller_class($match["params"]);
	$object->$method();
	exit();
} else {
    header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    echo "No match found. Requested url: ". PHP_EOL;
    var_dump($request_url);
    exit();
}
