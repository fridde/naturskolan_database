<?php

use Fridde\Controller\BaseController;

$controller_namespace = '\\Fridde\\Controller\\';

require 'bootstrap.php';

$request_url = rawurldecode($_SERVER['REQUEST_URI']);
$request_url = rtrim($request_url, '/\\');

$match = $container->get('Router')->match($request_url);
if ($match) {
    $class_and_method = explode('#', $match['target']);
    $controller_class = $controller_namespace . $class_and_method[0] . 'Controller';
    $controller = new $controller_class($match['params']);
    if($controller instanceof BaseController){
        $controller->addAction($class_and_method[1] ?? null);
        $controller->handleRequest();
    }
    exit();
}

header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
echo 'No match found. Requested url: '.PHP_EOL;
echo $request_url;
exit();

