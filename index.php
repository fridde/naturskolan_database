<?php
//// to test this, use http://localhost/naturskolan_database/index.php
require __DIR__ . '/vendor/autoload.php';

use Fridde\{Essentials, Utility as U, HTMLForTwig as H, Naturskolan};

Essentials::setAppDirectory("naturskolan_database");
Essentials::getSettings();
Essentials::activateDebug();

$N = new Naturskolan();

$H = new H();

$H->setTemplate("group_settings")->setTitle("Sigtuna Naturskolans databas");
$H->addCss(["css.jqueryUI", "css.bs", "css.natskol"]);
$H->addJs(["jquery", "js.bs", "js.jqueryUI", "moment", "moment_sv", "js.natskol"]);


U::extractRequest(["view"]);
$view = $view ?? "grupper";  // standard view is "grupper"

// TODO: Create a login using a password passed as GET-parameter to enable login via email

$hash = $_COOKIE["Hash"] ?? "";

//TODO: make the login-process work
//$hash = $N->ORM->getRepository("Password")->findByHash($hash);
//$school = empty($hash) ? null : $hash->getSchool();

$school =  $N->ORM->getRepository("School")->find("olof"); //for testing purposes

//Creating the navigation bar
//$nav_links = ["LEFT" => ["Grupper" => "index.php?view=grupper", "Lärare" => "index.php?view=larare"], "RIGHT" => ["Logga ut" => "update.php?updateType=deleteCookie"]];
//$navbar = $H->addBsNav($nav_links);


if(empty($school)){ // create pop-up window
	$modal_window = $H->addBsModal($H->body, ["title" => "Ange ditt lösenord", "button_texts" => ["Glömt lösenord?", "Logga in"]]);
	$H->addInput($modal_window["body"], "password", "text", ["placeholder" => "Lösenord"]);
}
else {
	//$H->add($H->body, "h1", "Du är inloggad som " . $school->getName());
	//$H->add($H->body, "p", "", [["save-time"]]);  // will be updated by JS if changes are made to anything
	$DATA = [];
	$DATA["teachers"] = array_map(function($u){
		return ["id" => $u->getId(), "full_name" => $u->getFullName()];
	}, $school->getUsers()->toArray());
	$DATA["student_limits"] = array_combine(["min", "max"], $SETTINGS["values"]["min_max_students"]);

	$groups = $school->getGroups();
	$grades_at_this_school = $school->getGradesAvailable(true);

	foreach($grades_at_this_school as $grade_val => $grade_label){
		$groups_current_grade = $groups->filter(function($g) use ($grade_val){
			return $g->isGrade($grade_val);
		});
		$tab = ["id" => $grade_val, "grade_label" => $grade_label];
		$extract_group_info = function($g){
			$r["id"] = $g->getId();
			$r["name"] = $g->getName();
			$r["teacher_id"] = $g->getUser()->getId();
			$r["nr_students"] = $g->getNumberStudents();
			$r["food"] = $g->getFood();
			$r["info"] = $g->getInfo();
			$r["visits"] = array_map(function($v){
				$r["id"] = $v->getId();
				$r["date"] = $v->getDate()->toDateString();
				$r["topic_short_name"] = $v->getTopic()->getShortName();
				$r["topic_url"] = $v->getTopic()->getUrl();
				$r["confirmed"] = $v->isConfirmed();
				return $r;
			}, $g->getVisits()->toArray());

			return $r;
		};
		$groups_current_grade_formatted = array_map($extract_group_info, $groups_current_grade->toArray());

		$group_columns = H::partition($groups_current_grade_formatted); // puts items in two equally large columns
		$tab["col_left"] = $group_columns[0] ?? [];
		$tab["col_right"] = $group_columns[1] ?? [];

		$DATA["tabs"][] = $tab;
	}
	$H->addVariable("DATA", $DATA);
}

$H->render();

// if($view == "larare"){
// 	$ops["ignore"] = ["id", "Mailchimp", "School", "Password", "IsRektor", "Status", "LastChange"]; // $ops = options
// 	$ops["table"] = "users";
// 	$ops["data_types"] = ["showOnly" => ["DateAdded"]];
// 	$table = $H->addEditableTable($row_parts[1], $school->getUsers(), $ops, []);
// 	$button_div = $H->addDiv($row_parts[1]);
// 	$button = $H->add($button_div, "button", "Lägg till lärare", ["id" => "add-row-btn"]);
// }
// elseif($view == "grupper"){
