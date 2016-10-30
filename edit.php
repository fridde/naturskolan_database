<?php
	/* This page can be called with the parameter "resource" and the corresponding sql-table
	TODO: Make this more API-friendly 
	*/

	// test this page with localhost/naturskolan_database/edit.php?resource=groups&XDEBUG_SESSION_START=test&trial=01
	include("autoload.php");
	getSettings();
	activateDebug();

	//updateAllFromRepo();
	use \Fridde\Utility as U;
	use \Fridde\NSDB_MailChimp as M;
	use \Fridde\ArrayTools as A;
	use Carbon\Carbon as C;
	use \Fridde\HTML as H;
	$SQL = new \Fridde\SQL();
	$N = new \Fridde\Naturskolan();


	$H = new H("Sigtuna Naturskolas databas");
	$H->addCss(["jqueryUI", "bs", "natskol"]);
	$H->addJs(["jquery", "jqueryUI", "bs", "moment", "natskol"]);

	U::extractRequest();

	$table = $N->get($resource);

	$o["table"] = $resource;
	$o["ignore"] = ["id"];
	$o["extra_columns"] = ["left" => ["checkbox", "delete"]];

	// these are the default data types. they can still be changed in the switch($resource)-case later on
	$data_types["date"] = ["Date"];
	$data_types["select"] = ["School", "User", "Grade", "Location", "Group", "Topic"];
	$data_types["slider"] = [];
	$data_types["textarea"] = [];
	$data_types["showOnly"] = ["VisitOrder", "DateAdded", "LastChange"];
	$data_types["radio"] = ["IsActive", "IsRektor", "Confirmed"];
	$data_types["checkbox"] = ["Colleague"];
	$o["data_types"] = $data_types;

	$select_options["School"] = $N->get("schools/id");
	$select_options["User"] = $N->format($N->get("users"), "user");
	$select_options["Grade"] = array_unique($N->get("groups/Grade"));
	$select_options["Location"] = $N->format($N->get("locations"), "location");
	$select_options["Group"] = $N->format($N->get("groups"), "group");
	$select_options["Colleague"] = $N->format($N->get("users", ["School", "natu"]), "colleague");
	$select_options["Topic"] = $N->format($N->get("topics"), "topic");
	$o["select_options"] = $select_options;

	switch($resource){

		case "busstrips":
		break;

		case "events":
		break;

		case "groups":
		$o["data_types"]["textarea"] = array_merge($o["data_types"]["textarea"], ["Food", "Info", "Notes"]);
		$o["data_types"]["slider"][] = "NumberStudents";
		$o["select_options"]["NumberStudents"] = [5,35];
		break;

		case "locations":
		break;

		case "messages":
		break;

		case "passwords":
		break;

		case "schools":
		$grade_group = ["GroupsAk2", "GroupsAk5", "GroupsFbk16", "GroupsFbk79"];
		$o["data_types"]["slider"] = array_merge($o["data_types"]["slider"], $grade_group);
		$o["data_types"]["showOnly"][] = "VisitOrder";

		$grade_group_ranges = array_fill_keys($grade_group, ["0", "5"]);
		$o["select_options"] = array_merge($grade_group_ranges, $o["select_options"]);
		break;

		case "sessions":
		break;

		case "topics":
		break;

		case "users":
		break;

		case "visits":
		break;

		default:

	}

	$button_div = $H->addDiv($H->body);
	$button = $H->add($button_div, "button", "Lägg till rad", ["id" => "add-row-btn", "data-first" => 1]);
	$table_div = $H->addDiv($H->body);
	$H->addEditableTable($table_div, $table, $o, ["data-table-name" => $resource]);

	$H->render();
