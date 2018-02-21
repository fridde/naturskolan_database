<?php

require __DIR__.'/vendor/autoload.php';

use Fridde\Essentials;
use Fridde\Settings;

/** START OF BOOTSTRAP  */
Essentials::setBaseDir(__DIR__);
Essentials::setAppUrl();
Settings::setSettings();
if(SETTINGS['environment'] === 'dev'){
    Essentials::activateDebug(['tracy']);
}
Essentials::activateGlobalFunctions();

$services[] = ['Naturskolan', \Fridde\Naturskolan::class];
$base_url = '/' . basename(APP_URL);
$services[] = ['Router', 'AltoRouter', Essentials::getRoutes(), $base_url];
$services[] = ['Logger', Essentials::getLogger()];
$container = Essentials::registerSharedServices($services);

$em = $container->get('Naturskolan')->ORM->EM;
Essentials::registerDBLogger($em, Essentials::getLogger());

setlocale(LC_TIME, 'swedish');
\Carbon\Carbon::setUtf8(true);

/** END OF BOOTSTRAP */
