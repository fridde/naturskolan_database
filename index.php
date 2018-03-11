<?php

$controller_namespace = '\\Fridde\\Controller\\';
$default_controller_method = 'handleRequest';

require 'bootstrap.php';

$request_url = rawurldecode($_SERVER['REQUEST_URI']);
$request_url = rtrim($request_url, '/\\');

$match = $container->get('Router')->match($request_url);
if ($match) {
    $class_and_method = explode('#', $match['target']);
    $controller_class = $controller_namespace . $class_and_method[0] . 'Controller';
    $object = new $controller_class($match['params']);
    $method = $class_and_method[1] ?? $default_controller_method;
    call_user_func([$object, $method]);
    exit();
}

header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
echo 'No match found. Requested url: '.PHP_EOL;
echo $request_url;
exit();

