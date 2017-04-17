<?php

require __DIR__ . '/vendor/autoload.php';

use Fridde\{Essentials};


Essentials::setBaseDir(__DIR__);
Essentials::setAppUrl();
Essentials::getSettings();
Essentials::activateDebug(["no_tracy"]);
Essentials::activateGlobalFunctions();

$services[] = ['Naturskolan', 'Fridde\Naturskolan'];
$services[] = ['Router', 'AltoRouter', Essentials::getRoutes(), '/'. basename(BASE_DIR)];
$services[] = ['Logger', Essentials::getLogger()];
$container = Essentials::registerSharedServices($services);

$em = $container->get('Naturskolan')->ORM->EM;
$logger = $container->get('Logger');
Essentials::registerDBLogger($em, $logger);

setlocale(LC_TIME, 'Swedish');

$router = $container->get('Router');

$request_url = rawurldecode($_SERVER['REQUEST_URI']);
if(substr($request_url, -1) == '/'){
	$request_url = substr($request_url, 0, -1);
}

$match = $router->match($request_url);
if($match){

	list($class, $method) = explode('#', $match["target"]);
	$class = '\\Fridde\\Controller\\' . $class . "Controller";
	$object = new $class($match["params"]);
	$object->$method();
	exit();
} else {
	$e_string = 'The URL "' . $request_url . '" did not have a matching route.';
	throw new \Exception($e_string);
	exit();
}
