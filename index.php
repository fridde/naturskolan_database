<?php
	//// to test this, use http://localhost/naturskolan_database/index.php?XDEBUG_SESSION_START=test&trial=01
	include("autoload.php");
	//activateDebug();
	inc("vendor");
	use \Fridde\Utility as U;
	use \Fridde\HTML as H;
	use \Fridde\SQL as SQL;
	use \Fridde\NSDB_MailChimp as M;
	use \Fridde\ArrayTools as A;
	use Carbon\Carbon as C;
	
	$N = new \Fridde\Naturskolan();
	
	$H = new H("Sigtuna Naturskolas databas");
	$H->addJs(["jquery", "jqueryUI", "bs", "moment", "natskol"]);
	$H->addCss(["jqueryUI", "bs", "css/naturskolan.css"]);
	
	U::extractRequest(["view"]);
	
	$logged_in_as = "anonymous";
	if(isset($_COOKIE["Hash"])){
		$school = $N->get("session/School", ["Hash", $_COOKIE["Hash"]]);
		if($school){
			$logged_in_as = $school;
		}
	}
	$nav_links = ["LEFT" => ["Grupper" => "index.php?view=grupper", "Lärare" => "index.php?view=larare"], "RIGHT" => ["Logga ut" => "update.php?updateType=deleteCookie"]];
	$navbar = $H->addBsNav($nav_links);
	
	
	if($logged_in_as == "anonymous"){
		$modal_window = $H->addBsModal($H->body, ["title" => "Ange ditt lösenord", "button_texts" => ["Glömt lösenord?", "Logga in"]]);
		$H->addInput($modal_window["body"], "password", "text", ["placeholder" => "Lösenord"]);
	}
	else {
		$school_name_long = $N->get("school/LongName", ["ShortName", $logged_in_as]);
		$H->add($H->body, "h1", "Du är inloggad som ". $school_name_long);
		$H->add($H->body, "p", "", [["save-time"]]);
		
		$groups = $N->get("groups", ["School", $logged_in_as]);
		$users = $N->get("users", ["School", $logged_in_as]);
		
		$container = $H->addDiv($H->body, "container-fluid");
		$row = $H->addDiv($container, "row");
		$row_parts[0] = $H->addDiv($row, "col-md-3");
		$row_parts[1] = $H->addDiv($row, "col-md-6"); // the center column
		$row_parts[2] = $H->addDiv($row, "col-md-3");
		
		
		if($view == "larare"){
			$ops["ignore"] = ["id", "Mailchimp", "School", "Password", "IsRektor"];
			$ops["table"] = "users";
			$table = $H->addEditableTable($row_parts[1], $users, $ops, []);
			$button_div = $H->addDiv($row_parts[1]);
			$button = $H->add($button_div, "button", "Lägg till lärare", ["id" => "add-row-btn"]);
		}
		elseif($view == "grupper"){
			$grades = array_unique(array_map(function($i){return $i["Grade"];}, $groups));
			sort($grades);
			
			$grade_translator = ["2" => "Årskurs 2+3", "5" => "Årskurs 5","fbk16" => "FBK 1-6","fbk79" => "FBK 7-9"];
			$tab_array = array_intersect_key($grade_translator, array_flip($grades));
			$tabs = $H->addBsTabs($row_parts[1], $tab_array);
			$prefix = array_shift($tabs);
			foreach($grades as $grade){
				$current_tab = $tabs[$prefix . $grade];
				$tab_row = $H->addDiv($current_tab, "row");
				$two_cols = [$H->addDiv($tab_row, "col-md-6"), $H->addDiv($tab_row, "col-md-6")];
				$groups_current_grade = array_filter($groups, function($v) use ($grade){return $v["Grade"] == $grade;});
				$group_columns = $H->partition($groups_current_grade, 2, false);
				$topics = $N->get("topics", ["Grade", $grade]);
				$topic_titles = array_combine(array_column($topics, "id"), array_column($topics, "ShortName"));
				foreach($group_columns as $col_key => $col){
					foreach($col as $single_group){
						$s = $single_group;
						$teachers = array();
						foreach($users as $user){
							$name = $user["FirstName"] . " " . $user["LastName"];
							$id = $user["id"];
							$atts = ($id == $s["User"]) ? ["selected"] : [] ;
							$teachers[] = [$name, $id, $atts];
						}
						$single_group_div = $H->add($two_cols[$col_key] , "div", "", [["group"], "data-group-id" => $s["id"]]);
						$H->add($single_group_div, "h1", $s["Name"], [["", "name_" . $s["id"]]]);
						$H->addSelect($single_group_div, ["User", "Ansvarig lärare"], $teachers, [["editable"]]);
						$H->addInput($single_group_div, ["NumberStudents", "Antal elever"], "text", [["editable form-control"], "placeholder" => "Antal elever", "value" => $s["NumberStudents"]]);
						$H->addTextarea($single_group_div, $s["Food"] , ["Food", "Specialkost"], 4, 50, [["editable form-control"], "placeholder" => "Specialkost"]);
						$H->addTextarea($single_group_div, $s["Info"], ["Info", "Bra att veta om klassen"], 4, 50, [["editable form-control"], "placeholder" => "Information om gruppen"]);
						
						$two_weeks_ago = C::now()->subDays(14)->toDateString();
						$visits = $N->get("visits", [["Group", $s["id"]],["Date", ">", $two_weeks_ago]]);
						if(count($visits) > 0){
							array_multisort(array_column($visits, "Date"), $visits);
							$visitString = function($v) use ($topic_titles){
								$string = $topic_titles[$v["Topic"]] . ": " . $v["Date"];
								return $string;
							};
							$visits_list = array_map($visitString, $visits);
							
							$ul = $H->addList($single_group_div, $visits_list);
						}
						else {
							$H->add($single_group_div, "p", "För närvarande är inga besök inbokade");
						}
						
					}
				}
				
				
			}
		}
	}
	
	$H->render();
	

		/*	
		/*	
		/*		
		/*		#####################################################################
		/*		$arskurs = ($arskurs ? $arskurs : "2/3");
		/*		
		/*		$headerTranslation = $ini_array["headerTranslator"];
		/*		
		/*		$download = ($download == "true" ? TRUE : FALSE);
		/*		
		/*		$table_codes = array_flip($ini_array["table_codes"]);
		/*		$translated_code = $table_codes[$code];
		/*		
		/*		if($translated_code != FALSE){
		/*			
		/*			switch($translated_code){
		/*				case "rektorer":
		/*				$tableName = "rektorer";
		/*				$headers = array("fname", "lname", "skola");
		/*				$criteria = "";
		/*				break;
		/*				
		/*				case "aventyr_ak2":
		/*				$tableName = "larare";
		/*				$headers = array("fname", "lname", "skola", "d5", "d6", "d7", "d8", "email");
		/*				$criteria = array("AND", array("g_arskurs" => "2/3", "d5" => "NOT:", "status" => "NOT:archived"));
		/*				break;
		/*				
		/*				case "aventyr_ak5":
		/*				$tableName = "larare";
		/*				$headers = array("fname", "lname", "skola", "d1", "d2", "d3", "d4", "email");
		/*				$criteria = array("AND", array("g_arskurs" => "5", "d1" => "NOT:", "status" => "NOT:archived"));
		/*				break;
		/*				
		/*				case "aventyr_all":
		/*				$tableName = "larare";
		/*				$headers = "all";
		/*				$criteria = array("status" => "NOT:archived");
		/*				break;
		/*				
		/*				case "aventyr_mat":
		/*				$tableName = "larare";
		/*				$headers = array("fname", "lname", "skola", "a1d1", "mat", "email");
		/*				$criteria = array("AND", array("a1d1" => "NOT:", "status" => "NOT:archived"));
		/*				break;
		/*				
		/*				default:
		/*				echo 'ERROR: The code "' . $code . '" was not defined. Contact the webmaster and let them check index.php. And get a coffee!<br><br>';
		/*				break;
		/*			}
		/*			
*/
