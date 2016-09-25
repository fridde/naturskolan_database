<?php
//// to test this, use http://localhost/naturskolan_database/cron_jobs.php?XDEBUG_SESSION_START=test&trial=01
require __DIR__ . '/vendor/autoload.php';

use \Fridde\Essentials;
use \Fridde\Utility as U;
use \Carbon\Carbon as C;
use \Fridde\Task as T;

Essentials::getSettings();
Essentials::activateDebug();

$N = new \Fridde\Naturskolan();
//$M = new \Fridde\NSDB_MailChimp();

$cron_jobs = $SETTINGS["cronjobs"];
$task_table = $N->get("tasks");

$task_status = array_column($task_table, "Value", "Name");


$slot_counter = $task_status["slot_counter"];

foreach($cron_jobs["intervals"] as $task_type => $interval){
	if(($slot_counter - $cron_jobs["delay"]) % $interval == 0){
		$task = new T($task_type, $task_status);
		$task->execute();
		$changed_task_status = $task->getStatus();
	}

}

$now = C::now();

$finished_tasks = [];
foreach ($task_types as $task_type) {
	$matching_tasks = U::filterFor($waiting_tasks, ["Type", $task_type]);
	$success = $N->executeTask($task_type, $matching_tasks);
	if($success){
		$finished_tasks = array_merge($finished_tasks, array_column($matching_tasks, "id"));
	}
}
$N->update("tasks", ["Status" => "done", "Timestamp" => $now->toIso8601String()], ["id", "in", $finished_tasks]);

$slot_counter += 1;
//reset once every week
$is_first_day_of_week = $now->dayOfWeek() == 0;
$counter_has_gone_one_day = $slot_counter * $cron_jobs["slot_duration"] > 24 * 60; // 24h/day * 60min/h
if($is_first_day_of_week && $counter_has_gone_one_day){
	$slot_counter = 0;
}
//TODO: update slot_counter in sql-table

// if to few rebuild_calendar processes, fill in up to 10 days


/*
Cron jobs
INTERVAL: 12h
-If categories and interests from MC to settings not in sync, add warning to daily admin mail

INTERVAL: 15min
If more than 15min since last rebuild and certain tables have been changed
- adjust calendar

INTERVAL: 24h
If more than 24h since last total rebuild of calendar
- rebuild calendar

INTERVAL: 24h
If less than 2 weeks to visit, no mail been sent, visit not confirmed
- send mail to group leaders

INTERVAL: 24h
If less than 1 week to visit, no sms been sent, visit not confirmed
- send sms to group leaders

INTERVAL: 24
If less than 5 days to visit, visit not confirmed
- add warning to daily admin-update mail

INTERVAL: 12h
If food changed, less than one week to visit
- add warning to daily admin update mail

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
