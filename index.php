<?php

use Fridde\Controller\BaseController;
use Fridde\Error\ExceptionHandler;
use Fridde\Essentials;


try {
    require __DIR__ .'/bootstrap.php';

    $match = $container->get('Router')->match();

    [$controller_class, $action, $params] = $match;

    $controller = new $controller_class($params);

    if ($controller instanceof BaseController) {
       $controller->addAction($action);
       $controller->handleRequest();
    }
    exit();

} catch (\Exception $e) {

    $e_handler = new ExceptionHandler($e, Essentials::getLogger());

    $e_handler->handle();
    exit();
}



