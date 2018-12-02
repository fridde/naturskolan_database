<?php

use Fridde\Controller\CronController;

require 'bootstrap.php';

$cron_controller = new CronController([]);

$cron_controller->run();
