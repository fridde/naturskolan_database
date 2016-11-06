<?php
	//// to test this, use http://localhost/naturskolan_database/index.php?XDEBUG_SESSION_START=test&trial=01
	require __DIR__ . '/vendor/autoload.php';

	use \Fridde\{Essentials, Utility as U, HTML as H, SQL};
	use Carbon\Carbon as C;
	use Tracy\Debugger;

	Essentials::getSettings();
	Essentials::activateDebug();

	//Debugger::fireLog(["me" => "you"]);

	$N = new \Fridde\Naturskolan();

	$H = new H("Sigtuna Naturskolas databas");
	$H->addJs(["jquery", "bs", "jqueryUI", "moment", "natskol"]);
	$H->addCss(["jqueryUI", "bs", "css/naturskolan.css"]);

	U::extractRequest(["view"]);
	$view = $view ?: "grupper";  // standard view is "grupper"



	// TODO: Create a login using a password passed as GET-parameter to enable login via email
	$logged_in_as = "anonymous";
	if(isset($_COOKIE["Hash"])){
		$school = $N->get("session/School", ["Hash", $_COOKIE["Hash"]]);
		if($school){
			$logged_in_as = $school;
		}
	}
	//Creating the navigation bar
	$nav_links = ["LEFT" => ["Grupper" => "index.php?view=grupper", "Lärare" => "index.php?view=larare"], "RIGHT" => ["Logga ut" => "update.php?updateType=deleteCookie"]];
	$navbar = $H->addBsNav($nav_links);


	if($logged_in_as == "anonymous"){ // create pop-up window
		$modal_window = $H->addBsModal($H->body, ["title" => "Ange ditt lösenord", "button_texts" => ["Glömt lösenord?", "Logga in"]]);
		$H->addInput($modal_window["body"], "password", "text", ["placeholder" => "Lösenord"]);
	}
	else {
		$school_name = $N->get("school/Name", ["id", $logged_in_as]); //contains the long name of the school
		$H->add($H->body, "h1", "Du är inloggad som ". $school_name);
		$H->add($H->body, "p", "", [["save-time"]]);  // will be updated by JS if changes are made to anything

		$groups = $N->get("groups", ["School", $logged_in_as]);
		$users = $N->get("users", ["School", $logged_in_as]);
		// convert all "DateAdded" to something humanly readable using Carbon.js
		array_walk($users, function(&$u){
			$newDate = new C($u["DateAdded"]);
			$u["DateAdded"] = $newDate->toDateString();
		});

		//building the central part of the page
		$container = $H->addDiv($H->body, "container-fluid");
		$row = $H->addDiv($container, "row");
		$row_parts[0] = $H->addDiv($row, "col-md-3");
		$row_parts[1] = $H->addDiv($row, "col-md-6"); // the center column
		$row_parts[2] = $H->addDiv($row, "col-md-3");


		if($view == "larare"){
			$ops["ignore"] = ["id", "Mailchimp", "School", "Password", "IsRektor", "Status", "LastChange"]; // $ops = options
			$ops["table"] = "users";
			$ops["data_types"] = ["showOnly" => ["DateAdded"]];
			$table = $H->addEditableTable($row_parts[1], $users, $ops, []);
			$button_div = $H->addDiv($row_parts[1]);
			$button = $H->add($button_div, "button", "Lägg till lärare", ["id" => "add-row-btn"]);
		}
		elseif($view == "grupper"){

			$grades_at_this_school = array_unique(array_map(function($i){return $i["Grade"];}, $groups));
			sort($grades_at_this_school);
			$grade_translator = ["2" => "Årskurs 2+3", "5" => "Årskurs 5","fbk16" => "FBK 1-6","fbk79" => "FBK 7-9"];
			$tab_label_array = array_intersect_key($grade_translator, array_flip($grades_at_this_school));

			$tabs = $H->addBsTabs($row_parts[1], $tab_label_array); //every tab stands for one Årskurs (grade/level)
			$prefix = array_shift($tabs);
			foreach($grades_at_this_school as $grade){
				$current_tab = $tabs[$prefix . $grade];
				$tab_row = $H->addDiv($current_tab, "row");
				// These are the two columns that contain the groups
				$two_cols = [$H->addDiv($tab_row, "col-md-6"), $H->addDiv($tab_row, "col-md-6")];
				$groups_current_grade = array_filter($groups, function($v) use ($grade){return $v["Grade"] == $grade;});
				$group_columns = $H->partition($groups_current_grade, 2, false); // puts items in two equally large columns
				//will contain "Temadagar" (topics) that matches this grade
				$topics = $N->get("topics", ["Grade", $grade]);
				$topic_titles = array_combine(array_column($topics, "id"), array_column($topics, "ShortName"));

				foreach($group_columns as $col_key => $col){
					foreach($col as $single_group){
						$s = $single_group; // to abbreviate
						$teachers = [];
						foreach($users as $user){
							$name = $user["FirstName"] . " " . $user["LastName"];
							$id = $user["id"];
							$atts = ($id == $s["User"]) ? ["selected"] : [] ; // will give the current chosen teacher (user) the attribute "selected"
							$teachers[] = [$name, $id, $atts];
						}
						$single_group_div = $H->add($two_cols[$col_key] , "div", "", [["group-container"], "data-group-id" => $s["id"], "data-table" => "groups"]);
						$H->add($single_group_div, "h1", $s["Name"], [["", "name_" . $s["id"]]]);
						$H->addSelect($single_group_div, ["User", "Ansvarig lärare"], $teachers, [["editable"]]);

						$groupSliderOptions = ["initial_value" => $s["NumberStudents"], "column" => "NumberStudents", "row_id" => $s["id"]];
						$groupSliderOptions["min_max"] = $SETTINGS["values"]["min_max_students"] ?? [5, 35];
						$groupSliderOptions["label"] = "Antal elever";
						$H->addSlider($single_group_div, [["number-student-slider"]], $groupSliderOptions);

						$H->addTextarea($single_group_div, $s["Food"] , ["Food", "Specialkost"], 4, 50, [["editable form-control"], "placeholder" => "Specialkost"]);
						$H->addTextarea($single_group_div, $s["Info"], ["Info", "Bra att veta om klassen"], 4, 50, [["editable form-control"], "placeholder" => "Information om gruppen"]);

						$two_weeks_ago = C::now()->subDays(14)->toDateString();
						// contains all the coming visits and those from the last 14 days
						$visits = $N->get("visits", [["Group", $s["id"]],["Date", ">", $two_weeks_ago]]);
						if(count($visits) > 0){
							array_multisort(array_column($visits, "Date"), $visits);
							$visitStringFunction = function($v) use ($topic_titles){
								$topic_text = $topic_titles[$v["Topic"]] ?? "Denna grupp har fått fel temadag tillordnat. Kontakta admin!";
								return $topic_text . ": " . $v["Date"];
							};
							$visits_list = array_map($visitStringFunction, $visits);

							$ul = $H->addList($single_group_div, $visits_list);
						}
						else {
							$H->add($single_group_div, "p", "För närvarande är inga besök inbokade.");
						}

					}
				}


			}
			$modal = $H->add($H->body, "div", "", ["id" => "group-change-modal"]);
			$H->addInput($modal, "Name", "text", ["class" => "name-field", "value" => ""]);
		}
	}

	$H->render();
