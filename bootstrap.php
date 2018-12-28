<?php

require __DIR__.'/vendor/autoload.php';

use Doctrine\Common\Cache\FilesystemCache;
use Fridde\CacheFactory;
use Fridde\Essentials;
use Fridde\Naturskolan;
use Fridde\Router;
use Fridde\Settings;
use Carbon\Carbon;

if(file_exists('debug_functions.php')){
    require 'debug_functions.php';
}

if(empty($_SERVER['argv'][0])){
    session_start();
}

/** START OF BOOTSTRAP  */
Essentials::setBaseDir(__DIR__);
//Essentials::setAppUrl(__DIR__);
Essentials::setEnvironment();
Essentials::activateDebugIfNecessary(['tracy']);
$cache_factory = new CacheFactory(ENVIRONMENT, __DIR__);
$cache = $cache_factory->getCache();
Settings::setSettings(['cache' => $cache]);

define('APP_URL', '//' . SETTINGS['app_root']);

$services[] = ['Naturskolan', Naturskolan::class];

$controller_namespace = '\\Fridde\\Controller';

$base_url = rtrim(parse_url(APP_URL, PHP_URL_PATH), '/');
$services[] = ['Router', Router::class, $base_url, Essentials::getRoutes(), $controller_namespace];
$services[] = ['Logger', Essentials::getLogger()];
$services[] = ['Cache', $cache];
$container = Essentials::registerSharedServices($services);

$em = $container->get('Naturskolan')->ORM->EM;
Essentials::registerDBLogger($em, Essentials::getLogger());

setlocale(LC_TIME, 'swedish');
Carbon::setUtf8(true);
if(defined('ENVIRONMENT') && ENVIRONMENT === 'test'){
    /* @var Naturskolan $N  */
    $N = $container->get('Naturskolan');
    $test_time = $N->getStatus('test.datetime')
        ?? (Carbon::parse(SETTINGS['debug']['test_date'])
            ?? Carbon::now());
    Carbon::setTestNow($test_time);
}

/** END OF BOOTSTRAP */
