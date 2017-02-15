<?php
//// to test this, use http://localhost/naturskolan_database/index.php
require __DIR__ . '/vendor/autoload.php';

use Fridde\{Essentials};


Essentials::setBaseDir(__DIR__);
Essentials::setAppUrl();
Essentials::getSettings();
Essentials::activateDebug();

$router = new AltoRouter(Essentials::getRoutes(), '/'. basename($GLOBALS["BASE_DIR"]));

$request_url = $_SERVER['REQUEST_URI'];
if(substr($request_url, -1) == '/'){
	$request_url = substr($request_url, 0, -1);
}

$match = $router->match($request_url);
if($match){

	list($class, $method) = explode('#', $match["target"]);
	$class = '\\Fridde\\Controller\\' . $class . "Controller";
	$object = new $class();
	$object->$method($match["params"]);
	exit();
} else {
	echo 'The URL "' . $request_url . '" did not have a matching route.';
	exit();
}

// TODO: Create a login using a password passed as parameter to enable login via email



// //for testing purposes

//Creating the navigation bar
//$nav_links = ["LEFT" => ["Grupper" => "index.php?view=grupper", "Lärare" => "index.php?view=larare"], "RIGHT" => ["Logga ut" => "update.php?updateType=deleteCookie"]];
//$navbar = $H->addBsNav($nav_links);

// if($view == "larare"){
// 	$ops["ignore"] = ["id", "Mailchimp", "School", "Password", "IsRektor", "Status", "LastChange"]; // $ops = options
// 	$ops["table"] = "users";
// 	$ops["data_types"] = ["showOnly" => ["DateAdded"]];
// 	$table = $H->addEditableTable($row_parts[1], $school->getUsers(), $ops, []);
// 	$button_div = $H->addDiv($row_parts[1]);
// 	$button = $H->add($button_div, "button", "Lägg till lärare", ["id" => "add-row-btn"]);
// }
// elseif($view == "grupper"){
