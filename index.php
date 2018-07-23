<?php

use Fridde\Controller\BaseController;
use Tracy\BlueScreen;

$controller_namespace = '\\Fridde\\Controller\\';

try {
    require 'bootstrap.php';

    $request_url = rawurldecode($_SERVER['REQUEST_URI']);
    $request_url = rtrim($request_url, '/\\');

    $match = $container->get('Router')->match($request_url);
    if ($match) {
        $class_and_method = explode('#', $match['target']);
        $controller_class = $controller_namespace.$class_and_method[0].'Controller';
        $controller = new $controller_class($match['params']);
        if ($controller instanceof BaseController) {
            $controller->addAction($class_and_method[1] ?? null);
            $controller->handleRequest();
        }
        exit();
    }
} catch( Exception $e){
    $N = $container->get('Naturskolan')->log($e->getMessage(), $e->getFile() . ':' . $e->getLine());
    header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
    if(!empty(DEBUG)){
        (new BlueScreen())->render($e);
    }
    exit();
}

header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);
echo 'No match found. Requested url: '.PHP_EOL;
echo $request_url;
exit();

