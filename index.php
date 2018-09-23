<?php

use Fridde\Controller\BaseController;
use Fridde\Error\ExceptionHandler;
use Fridde\Error\NException;
use Fridde\Error\Error;
use Fridde\Essentials;

if(file_exists('debug_functions.php')){
    require 'debug_functions.php';
}

$controller_namespace = '\\Fridde\\Controller\\';

try {
    require 'bootstrap.php';

    $request_url = rtrim(rawurldecode($_SERVER['REQUEST_URI']), '/\\');

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
    $args = ['url' => $request_url];
    throw new NException(Error::PAGE_NOT_FOUND, $args);

} catch (\Exception $e) {

    $e_handler = new ExceptionHandler($e, Essentials::getLogger());

    $e_handler->handle();
    exit();
}



