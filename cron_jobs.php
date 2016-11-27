<?php
//// to test this, use http://localhost/naturskolan_database/cron_jobs.php?XDEBUG_SESSION_START=test&trial=01

require __DIR__ . '/vendor/autoload.php';

use \Fridde\{Essentials, Utility as U, Entities};
use \Fridde\Entities\Task as T;
use \Carbon\Carbon as C;


Essentials::getSettings();
Essentials::activateDebug();

$N = new \Fridde\Naturskolan();

$cron_jobs = $SETTINGS["cronjobs"];
$task_table = $N->getTable("tasks");

$task_status = array_column($task_table, "Value", "Name");

if(empty($_REQUEST["slot_counter"])){
	$slot_counter = 1 + $task_status["slot_counter"];
} else {
	$slot_counter = $_REQUEST["slot_counter"];
}

$updates = [];
foreach($cron_jobs["intervals"] as $task_type => $interval){
	if(($slot_counter - $cron_jobs["delay"]) % $interval == 0){		
		$task = new T($task_type);
		$task->execute();
		if($task->getStatus()){
			$updates = array_merge($updates, $task->getUpdates());
		}
	}
}

$slot_counter += 1;
//reset once every week
$is_first_day_of_week = $N->_NOW_->dayOfWeek == 0;
$counter_has_gone_one_day = $slot_counter * $cron_jobs["slot_duration"] > 24 * 60; // 24h/day * 60min/h
if($is_first_day_of_week && $counter_has_gone_one_day){
	$slot_counter = 0;
}
$updates[] = [["Value" => $slot_counter], ["Name", "slot_counter"]];
$N->batchUpdate("tasks", $updates);

/*
Cron jobs
INTERVAL: 12h
-If categories and interests from MC to settings not in sync, add warning to daily admin mail



INTERVAL: 24h
If more than 24h since last total rebuild of calendar
- rebuild calendar


INTERVAL: 24h
If user made group leader for at least one group since last mail
- send mail to group leader
*/

/*



$path = "mailchimp/lists/larare/categories";


$mc_categories = $M->getCategoriesAndInterests();
$mc_categories_ids = array_column($mc_categories, "id");

$only_in_mc = array_diff($mc_categories_ids, $settings_categories_ids);
$only_in_settings = array_diff($settings_categories_ids, $mc_categories_ids);

print_r($only_in_mc); //add to table
print_r($only_in_settings); //remove from table

*/
