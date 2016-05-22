<?php
	
	include("autoload.php");
	//activateDebug();
	inc("vendor");
	use \Fridde\Utility as U;
	use \Fridde\HTML as H;
	use \Fridde\SQL as SQL;
	use \Fridde\NSDB_MailChimp as M;
	use \Fridde\ArrayTools as A;
	
	$N = new \Fridde\Naturskolan();
	
	$H = new H("Sigtuna Naturskolas databas");
	$H->addJs(["jquery", "jqueryUI", "bs", "natskol"]);
	$H->addCss(["jqueryUI", "bs"]);
	
	$logged_in_as = "anonymous";
	if(isset($_COOKIE["Hash"])){
		$school = $N->get("session/School", ["Hash", $_COOKIE["Hash"]]);
		if($school){
			$logged_in_as = $school;
		}
	}
	if($logged_in_as == "anonymous"){
		$modal_window = $H->addBsModal($H->body, ["title" => "Ange ditt lösenord", "button_texts" => ["Glömt lösenord?", "Logga in"]]);
		$H->addInput($modal_window["body"], "password", "text", ["placeholder" => "Lösenord"]);
	}
	else {
		$school_name_long = $N->get("school/LongName", ["ShortName", $logged_in_as]);
		$groups = $N->get("groups", ["School", $logged_in_as]);
		$grades = array_unique(array_map(function($i){return $i["Grade"];}, $groups));
		sort($grades);
		$H->add($H->body, "h1", "Du är inloggad som ". $school_name_long);
		$grade_translator = ["2" => "Årskurs 2+3", "5" => "Årskurs 5","fbk16" => "FBK 1-6","fbk79" => "FBK 7-9"];
		$tab_array = array_intersect_key($grade_translator, array_flip($grades));
		$container = $H->addDiv($H->body, "container");
		$row = $H->addDiv($container, "row");
		//$columns[0] = 
		$tabs = $H->addBsTabs($H->body, $tab_array);
		$prefix = array_shift($tabs);
		foreach($grades as $grade){
			$groups_current_grade = array_filter($groups, function($v) use ($grade){return $v["Grade"] == $grade;});
			foreach($groups_current_grade as $current_group){
				
			}
			
		}
		
		
		//standard view
	}
	
	$H->render();
	
	/*	 this is a quick way of initializing a few variables, thus setting them to be an empty string */
	/*	list($html, $head, $body, $title, $ak2_left, $ak2_right, $ak5_left, $ak5_right) = array_fill(0,20, "");
		/*	
		/*	if($user != FALSE){
	/*		/* this area is only for verified users */
	/*		$skola = $user["skola"]; // will be a 4 letter code like "ting" for Tingvalla
		/*	
		/*		$skola_long = array_select_where($skolor, array("short_name" => $skola), "all", TRUE);
		/*		$skola_long = $skola_long["long_name"];
		/*	
		/*		$grupper = array_select_where($grupper, array("skola" => $skola)); // selects all groups from the same school
		/*		$grupper = array_orderby($grupper, "g_arskurs", SORT_ASC , "larar_id", SORT_ASC);
		/*	
		/*		$dateFields = array("d1", "d2", "d3", "d4", "d5", "d6", "d7", "d8");
		/*		$textFields = array("klass" => "Gruppens namn", "elever" => "Antal elever", "mat" => "Matpreferenser/Allergier", "info" => "Annat man bör veta om klassen");
		/*		$textAreaFields = array("mat", "info");
		/*		$attributes = array("ignore" => array("id", "mailchimp_id", "larar_id", "skola", "g_arskurs", "updated", "ltid", "notes", "special"), "textbox" => array("info", "mat"), "select" => array("larar_id"));
		/*	
		/*		$larare_samma_skola = array_select_where($larare, array("skola" => $skola, "status" => "active"));
		/*	
	/*		/* Here we start building the view */
	/*		$head .= qtag("meta");
		/*		$head .= tag("title", "Naturskolans inställningar - Inloggad som " . $user['fname'] . ' ' . $user['lname']);
		/*		$incString = "jquery,user_init,bootcss,bootjs,boottheme,fAwe,css";
		/*		//$incString = "jquery,user_init,bootcss,bootjs,boottheme,fAwe"; // ONLY FOR DEBUG!
		/*		$head .= inc($incString, FALSE, TRUE);
		/*	
		/*		$html .= tag("head", $head);
		/*	
		/*		$title .= '<h1>Hej, ' . $user['fname'] . ' ' . $user['lname'] . '!</h1>';
		/*		$title .= 'Här är alla grupper från ' . $skola_long . "!";
		/*		$title .= '<p id="saveResponse"><p>'; // A small paragraph that is updated whenever a successful ajax-request returns
		/*		$title .= tag("p", $id , array("id" => "user", "hidden"));
		/*		$recent_arskurs = "0"; // a counter that keeps the årskurs of the recent group to compare
		/*		$groupCounter = 0; // needed to see whether a group should be viewed in the left or right column. Also is used as an alternative if the group has no name yet. In this case the group is called "Grupp $groupCounter"
		/*	
		/*		foreach($grupper as $grupp){
		/*			$thisGroupContent = ""; // this will contain the content of the current group
		/*			$arskurs = $grupp["g_arskurs"]; // ideally either "2/3" or "5"
		/*			if($arskurs != $recent_arskurs){
		/*				$groupCounter = 0; // resetting the group counter whenever a new årskurs starts
		/*			}
		/*			$groupCounter += 1;
		/*			$recent_arskurs = $arskurs;
		/*	
		/*			if(trim($grupp["klass"]) != ""){
		/*				$klassname = $grupp["klass"];
		/*			}
		/*			else {
		/*				$klassname = "Grupp " . $groupCounter;
		/*			}
		/*			$groupTitleArray = array("id" => "h3_" . $grupp["id"], "class" => "groupTitle");
		/*			$thisGroupContent .= tag("h3", $klassname, $groupTitleArray); //is updated by the ajax-request whenever the input field "klass" is updated
		/*	
	/*			/* Here come the fields of each group */
	/*	
	/*			/* TEACHER SELECTOR*/
	/*			$select = "";
		/*			$selectorId = $grupp["id"] . "%" . "larar_id";
		/*			$thisGroupContent .= tag("label", "Medföljande lärare", array("for" => $selectorId));
		/*			foreach($larare_samma_skola as $key => $row){
		/*				$selected = ($row["id"] == $grupp["larar_id"] ? "selected" : "");
		/*				$select .= tag("option", $row["fname"] . " " . $row["lname"], array("value" => $row["id"], $selected));
		/*			}
		/*			$thisGroupContent .= tag("select", $select, array("name" => $selectorId));
		/*	
	/*			/* all the textinputs*/
	/*			foreach($textFields as $colKey => $labelText){
		/*				$fieldId = $grupp["id"] . "%" . $colKey;
		/*				$thisGroupContent .= tag("label", $labelText, array("for" => $fieldId));
		/*	
		/*				$atts =array("name" => $fieldId, "id" => $fieldId);
		/*				if(in_array($colKey, $textAreaFields)){
		/*					$tagName = "textarea";
		/*					$atts["rows"] = "4";
		/*				} else {
		/*					$atts["value"] = $grupp[$colKey];
		/*					$tagName = "input";
		/*				}
		/*				$thisGroupContent .= tag($tagName, $grupp[$colKey], $atts);
		/*			}
		/*	
	/*			/* DATES */
	/*			$datesList = "";
		/*			foreach($dateFields as $dateColName){
		/*				if(trim($grupp[$dateColName]) != ""){
		/*					if($grupp["g_arskurs"] == "2/3"){
		/*						$li = $ini_array["titleTranslator2"][$dateColName];
		/*					} elseif($grupp["g_arskurs"] == "5"){
		/*						$li = $ini_array["titleTranslator5"][$dateColName];
		/*					} else {
		/*						$li = $dateColName;
		/*					}
		/*					$li .= ": " . $grupp[$dateColName];
		/*					$datesList .= tag("li", $li);
		/*				}
		/*			}
		/*			$ul = tag("ul", $datesList);
		/*			$datesFieldSet =  "<legend>Datum</legend>" . $ul;
		/*			$thisGroupContent .= tag("fieldset", 	$datesFieldSet);
	/*			/* End of DATES */
	/*	
	/*			/* Compliance statement */
	/*			$is_checked = $grupp["checked"] == "yes";
		/*			$complianceId = $grupp["id"] . "%" . "checked";
		/*			$compliance = qtag("checkbox", $complianceId, "yes", $is_checked);
		/*			$complianceText = "Uppgifterna för denna klass är någorlunda korrekta och datumen är antecknade i medföljande lärares kalender.";
		/*			$complianceFieldSet = "<legend>Bekräftelse</legend>" . $compliance . $complianceText;
		/*			$thisGroupContent .= tag("fieldset", $complianceFieldSet);
		/*	
	/*			/* FINAL finish of the Group-part */
	/*			$thisGroup = tag("div", $thisGroupContent, "group");
		/*	
	/*			/* Deciding where the groups content should go */
	/*			$left = $groupCounter % 2 == 1;
		/*			$is_ak5 = $arskurs == "5";
		/*	
		/*			if($left && !$is_ak5){
		/*				$ak2_left .= $thisGroup;
		/*			}
		/*			if($left && $is_ak5){
		/*				$ak5_left .= $thisGroup;
		/*			}
		/*			if(!$left && !$is_ak5){
		/*				$ak2_right .= $thisGroup;
		/*			}
		/*			if(!$left && $is_ak5){
		/*				$ak5_right .= $thisGroup;
		/*			}
		/*		}
		/*	
	/*		/* Creating the final view */
	/*		$instructionText = file_get_contents("inc/instructions.html");
		/*		$instructionColumn = tag("div", $instructionText, "col-md-4");
		/*		$arskursViewArray = array();
		/*		if($ak2_left != ""){
		/*			$ak2_pretext = "<h2>Årskurs 2/3</h2>";
		/*	
		/*			$col_left = tag("div", $ak2_left, "col-md-4");
		/*			$col_right = tag("div", $ak2_right, "col-md-4");
		/*			$row = tag("div", $instructionColumn . $col_left . $col_right , "row");
		/*			$ak2 = tag("div", $ak2_pretext . $row, "container");
		/*			$arskursViewArray["Årskurs 2/3"] = $ak2;
		/*		}
		/*		if($ak5_left != ""){
		/*			$ak5_pretext = "<h2>Årskurs 5</h2>";
		/*	
		/*			$col_left = tag("div", $ak5_left, "col-md-4");
		/*			$col_right = tag("div", $ak5_right, "col-md-4");
	/*			/* Here we put the row together */
	/*			$row = tag("div", $instructionColumn . $col_left . $col_right , "row");
		/*			$ak5 = tag("div", $ak5_pretext . $row, "container");
		/*			$arskursViewArray["Årskurs 5"] = $ak5;
		/*		}
		/*		$tabs = qtag("tabs", "", $arskursViewArray) ;
		/*		//$col_1 = tag("div", $tabs, "col-md-12");
		/*		//$container = tag("div", $col_1, "row");
		/*		$body .= tag("div", $title . $tabs, "container");
		/*	
		/*	} else {
		/*		$body .= "You are not a registered user OR the admin of sigtunanaturskola.se 
		/*		has not added you to the list of verified users yet. Go to sigtunanaturskola.se/kontakt";
		/*	}
		/*	$html .= tag("body", $body);
		/*	echo tag("html", $html);
		/*	
		/*	
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
		/*			$tableContent = sql_select($tableName, $criteria ,$headers);
		/*			$tableContent = array_change_col_names($tableContent, $headerTranslation);
		/*			
		/*			foreach($tableContent as $rowIndex => $row) {
		/*				if(isset($row["email"])){
		/*					$mail = $row["email"];
		/*					if (strpos($mail,'alias') !== false) {
		/*						$mail = "[har flera grupper, se annan rad]";
		/*					} else {
		/*						$mail = strtolower(blur_mail($mail));
		/*					}
		/*					$tableContent[$rowIndex]["email"] = $mail;
		/*				}
		/*			}
		/*		} 
		/*		list($html, $head, $body) = array_fill(0,20,""); 
		/*		
		/*		$head .= qtag("meta");
		/*		$incString = "jquery,DTjQ,DTTT,DTfH,DTin,jqueryUIcss,DTcss,DTfHcss,DTTTcss,css";
		/*		$head .= inc($incString, FALSE, TRUE);
		/*		$html .= tag("head", $head);
		/*		
		/*		if($code != FALSE){
		/*			$body .= create_htmltable_from_array($tableContent);
		/*			if($download){
		/*				array_to_csv_download($tableContent, "export.csv", "\t");
		/*			}
		/*		}
		/*		else {
		/*			$body .=  "You don't have the rights to see this page. Ask an admin for the right link!";
		/*		}
		/*		
		/*		$html .= $body;
		/*		
		/*		echo $html;
	/*		*/
