<?php
//// to test this, use http://localhost/naturskolan_database/cron_jobs.php?XDEBUG_SESSION_START=test&trial=01
include("autoload.php");
activateDebug();

use \Fridde\Utility as U;
use \Fridde\SQL as SQL;
//use \Fridde\ArrayTools as A;
use Carbon\Carbon as C;

$N = new \Fridde\Naturskolan();
$M = new \Fridde\NSDB_MailChimp();

$SETTINGS = getSettings();

$cron_jobs = $SETTINGS["cronjobs"];
$unordered_tasks = $N->get("tasks", ["status" => "waiting"]);
array_multisort(array_column($unordered_tasks, "ExecuteAt"), $unordered_tasks);
$now = C::now();

$tasks = [];
array_walk($unordered_tasks, function ($v) use (&$tasks) {
	$execute_at = C::parse($task["ExecuteAt"]);
	// check if we have passed "ExecuteAt"
	if ($now->gt($execute_at)) {
		$tasks[$v["Type"]][] = $v;
	}
});

$finished_tasks = [];
foreach ($tasks as $task_type => $task_group) {
	$success = $N->executeTask($task_type, $task_group);
	if($success){
		$finished_tasks = array_merge($finished_tasks, array_column($task_group, "id"));
	}
}



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
