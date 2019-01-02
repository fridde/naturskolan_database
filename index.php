<?php

use Fridde\Controller\BaseController;
use Fridde\Error\ExceptionHandler;
use Fridde\Essentials;

try {
    require __DIR__ .'/bootstrap.php';

    //$request_url = rtrim(rawurldecode($_SERVER['REQUEST_URI']), '/\\');

    $match = $container->get('Router')->match();

    $controller_class = $match[0];
    $controller = new $controller_class($match[2]);

    if ($controller instanceof BaseController) {
       $controller->addAction($match[1]);
       $controller->handleRequest();
    }
    exit();

} catch (\Exception $e) {

    $e_handler = new ExceptionHandler($e, Essentials::getLogger());

    $e_handler->handle();
    exit();
}



