<?php

/* PREAMBLE */
$url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
$filename = "include.php";
//copy($url, $filename);
include $filename;
/* END OF PREAMBLE */
inc("fnc, sql", TRUE); //remove "TRUE" in production
activate_all_errors(); // remove in production

$ini_array = parse_ini_file("config.ini", TRUE);

$headerTranslator = $ini_array["headerTranslator"]; //used to give the sql-table headers more understandable names

$id = $_REQUEST["id"]; // corresponding mailchimp-id that tells us who is entering. Is a 10-character hex-code

$larare = sql_select("larare"); // a table completely controlled by mailchimp
$grupper = sql_select("grupper"); // a table completely controlled on the local server
$skolor = sql_select("skolor"); // a constant table that should only be changed in few cases

/* We only accept users that have manually been added to the mailchimp group "verified = true" to avoid
that someone who is not a teacher can register and immediately start to change data
If a matching user was found, $user will contain the corresponding row from the sql-table "larare"
*/
$user = array_select_where($larare, array("mailchimp_id" => $id, "verified" => "true"));
$user = (count($user) == 1 ? reset($user) : FALSE);

$is_rektor = $user != FALSE && strtolower($user["rektor"]) == "true"; // not yet clear how this variable could be used

/* this is a quick way of initializing a few variables, thus setting them to be an empty string */
list($html, $head, $body, $title, $ak2_left, $ak2_right, $ak5_left, $ak5_right) = array_fill(0,20, "");

if($user != FALSE){
	/* this area is only for verified users */
	$skola = $user["skola"]; // will be a 4 letter code like "ting" for Tingvalla

	$skola_long = array_select_where($skolor, array("short_name" => $skola), "all", TRUE);
	$skola_long = $skola_long["long_name"];

	$grupper = array_select_where($grupper, array("skola" => $skola)); // selects all groups from the same school
	$grupper = array_orderby($grupper, "g_arskurs", SORT_ASC , "larar_id", SORT_ASC);

	$dateFields = array("d1", "d2", "d3", "d4", "d5", "d6", "d7", "d8");
	$textFields = array("klass" => "Gruppens namn", "elever" => "Antal elever", "mat" => "Matpreferenser/Allergier", "info" => "Annat man bör veta om klassen");
	$textAreaFields = array("mat", "info");
	$attributes = array("ignore" => array("id", "mailchimp_id", "larar_id", "skola", "g_arskurs", "updated", "ltid", "notes", "special"), "textbox" => array("info", "mat"), "select" => array("larar_id"));

	$larare_samma_skola = array_select_where($larare, array("skola" => $skola));

	/* Here we start building the view */
	$head .= qtag("meta");
	$head .= tag("title", "Naturskolans inställningar - Inloggad som " . $user['fname'] . ' ' . $user['lname']);
	$incString = "jquery,user_init,bootcss,bootjs,boottheme,fAwe";
	$head .= inc($incString, FALSE, TRUE);

	$html .= tag("head", $head);

	$title .= '<p id="saveResponse"><p>'; // A small paragraph that is updated whenever a successful ajax-request returns
	$title .= '<h1>Hej, ' . $user['fname'] . ' ' . $user['lname'] . '!</h1>';
	$title .= 'Här är alla grupper från ' . $skola_long . "!";
	$recent_arskurs = "0"; // a counter that keeps the årskurs of the recent group to compare
	$groupCounter = 0; // needed to see whether a group should be viewed in the left or right column. Also is used as an alternative if the group has no name yet. In this case the group is called "Grupp $groupCounter"

	foreach($grupper as $grupp){
		$thisGroupContent = ""; // this will contain the content of the current group
		$arskurs = $grupp["g_arskurs"]; // ideally either "2/3" or "5"
		if($arskurs != $recent_arskurs){
			$groupCounter = 0; // resetting the group counter whenever a new årskurs starts
		}
		$groupCounter += 1;
		$recent_arskurs = $arskurs;

		if(trim($grupp["klass"]) != ""){
			$klassname = $grupp["klass"];
		}
		else {
			$klassname = "Grupp " . $groupCounter;
		}
		$thisGroupContent .= '<h3 id="h3_' . $grupp["id"] . '">' . $klassname .'</h3>'; //is updated by the ajax-request whenever the input field "klass" is updated

		/* Here come the fields of each group */

		/* TEACHER SELECTOR*/
		$select = "";
		foreach($larare_samma_skola as $key => $row){
			$selected = ($row["id"] == $grupp["larar_id"] ? "selected" : "");
			$select .= tag("option", $row["fname"] . " " . $row["lname"], array("value" => $row["id"], $selected));
		}
		$thisGroupContent .= tag("select", $select, array("name" => $grupp["id"] . "%" . "larar_id"));


		foreach($textFields as $colKey => $labelText){
			$fieldId = $grupp["id"] . "%" . $colKey;
			$thisGroupContent .= tag("label", $labelText, array("for" => $fieldId));

			if(in_array($colKey, $textAreaFields)){
				$tagName = "textarea";
			} else {
				$tagName = "input";
			}
			$thisGroupContent .= tag($tagName, $grupp[$colKey], array("name" => $fieldId, "id" => $fieldId));
		}

		/* DATES */
		$datesList = "";
		foreach($dateFields as $dateColName){
			if(trim($grupp[$dateColName]) != ""){
				if($grupp["g_arskurs"] == "2/3"){
					$li = $ini_array["titleTranslator2"][$dateColName];
				} elseif($grupp["g_arskurs"] == "5"){
					$li = $ini_array["titleTranslator5"][$dateColName];
				} else {
					$li = $dateColName;
				}
				$li .= ": " . $grupp[$dateColName];
				$datesList .= tag("li", $li);
			}
		}
		$ul = tag("ul", $datesList);
		$thisGroupContent .= $ul;


		$left = $groupCounter % 2 == 1;
		$is_ak5 = $arskurs == "5";

		if($left && !$is_ak5){
			$ak2_left .= $thisGroupContent;
		}
		if($left && $is_ak5){
			$ak5_left .= $thisGroupContent;
		}
		if(!$left && !$is_ak5){
			$ak2_right .= $thisGroupContent;
		}
		if(!$left && $is_ak5){
			$ak5_right .= $thisGroupContent;
		}


	}
	$arskursViewArray = array();
	if($ak2_left != ""){
		$ak2 = "<h2>Årskurs 2/3</h2>";

		$col_left = tag("div", $ak2_left, "col-md-6");
		$col_right = tag("div", $ak2_right, "col-md-6");
		$row = tag("div", $col_left . $col_right , "row");
		$ak2 = tag("div", $ak2 . $row, "container");
		$arskursViewArray["Årskurs 2/3"] = $ak2;
	}
	if($ak5_left != ""){
		$ak5 = "<h2>Årskurs 5</h2>";

		$col_left = tag("div", $ak5_left, "col-md-6");
		$col_right = tag("div", $ak5_right, "col-md-6");
		$row = tag("div", $col_left . $col_right , "row");
		$ak5 = tag("div", $ak5 . $row, "container");
		$arskursViewArray["Årskurs 5"] = $ak5;

	}
	$tabs = qtag("tabs", "", $arskursViewArray);
	//$col_1 = tag("div", $tabs, "col-md-12");
	//$container = tag("div", $col_1, "row");
	$body .= tag("div", $title . $tabs, "container");

} else {
	$body .= "You are not a registered user OR the admin of sigtunanaturskola.se has not added you to the list of verified users yet. Go to sigtunanaturskola.se/kontakt";
}
$html .= tag("body", $body);
echo tag("html", $html);
//48 a7a4d6c76d


?>
