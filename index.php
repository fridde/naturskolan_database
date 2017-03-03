<?php

require __DIR__ . '/vendor/autoload.php';

use Fridde\{Essentials};
use League\Container\Container;
use League\Container\Argument\RawArgument;

Essentials::setBaseDir(__DIR__);
Essentials::setAppUrl();
Essentials::getSettings();
Essentials::activateDebug(["tracy"]);
Essentials::activateLogger();
setlocale(LC_TIME, 'Swedish');


$container = new Container();
$arg1 = new RawArgument(Essentials::getRoutes());
$args2 = new RawArgument('/'. basename(BASE_DIR));
$container->share('Naturskolan', 'Fridde\Naturskolan');
$container->share('Router', 'AltoRouter')
	->withArgument($arg1)->withArgument($args2);
$GLOBALS["CONTAINER"] = $container;

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
