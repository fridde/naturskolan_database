<?php

require __DIR__.'/vendor/autoload.php';

use Fridde\Essentials;
use Fridde\Settings;
use Carbon\Carbon;

/** START OF BOOTSTRAP  */
Essentials::setBaseDir(__DIR__);
Essentials::setAppUrl(__DIR__);
Settings::setSettings();
if(in_array(SETTINGS['environment'], ['dev','test'])){
    Essentials::activateDebug(['tracy']);
}

$services[] = ['Naturskolan', \Fridde\Naturskolan::class];

$base_url = rtrim(parse_url(APP_URL, PHP_URL_PATH), '/');
$services[] = ['Router', 'AltoRouter', Essentials::getRoutes(), $base_url];
$services[] = ['Logger', Essentials::getLogger()];
$container = Essentials::registerSharedServices($services);

$em = $container->get('Naturskolan')->ORM->EM;
Essentials::registerDBLogger($em, Essentials::getLogger());

setlocale(LC_TIME, 'swedish');
Carbon::setUtf8(true);
if(SETTINGS['environment'] === 'test'){
    Carbon::setTestNow(Carbon::parse(SETTINGS['debug']['test_date']));
}

/** END OF BOOTSTRAP */
