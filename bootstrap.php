<?php

require __DIR__.'/vendor/autoload.php';

use Doctrine\Common\Cache\FilesystemCache;
use Fridde\Essentials;
use Fridde\Naturskolan;
use Fridde\Settings;
use Carbon\Carbon;

if(empty($_SERVER['argv'][0])){
    session_start();
}

/** START OF BOOTSTRAP  */
Essentials::setBaseDir(__DIR__);
Essentials::setAppUrl(__DIR__);
Essentials::setEnvironment();
Essentials::activateDebugIfNecessary(['tracy']);
$cache = new FilesystemCache(__DIR__ . '/temp/cache');
Settings::setSettings(['cache' => $cache]);

$services[] = ['Naturskolan', Naturskolan::class];

$base_url = rtrim(parse_url(APP_URL, PHP_URL_PATH), '/');
$services[] = ['Router', 'AltoRouter', Essentials::getRoutes(), $base_url];
$services[] = ['Logger', Essentials::getLogger()];
$services[] = ['Cache', new FilesystemCache(__DIR__ . '/temp/cache')];
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
