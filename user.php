<?php
	
	/* PREAMBLE */
    $url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
    $filename = "include.php";
    //copy($url, $filename);
    include $filename;
    /* END OF PREAMBLE */
	inc("fnc, sql, cal, jquery, user_init"); 
	activate_all_errors();
	
	$ini_array = parse_ini_file("config.ini", TRUE);
	
	list($html, $head, $body) = array_fill(0,20, "");
	
	$hiddenElements = array();
	
	$id = $_REQUEST["id"];
	
	$larare = sql_select("larare");
	$grupper = sql_select("grupper");
	$skolor = sql_select("skolor");
	$rektorer = sql_select("rektorer");
	
	$selected_larare = array_select_where($larare, array("mailchimp_id" => $id));
	$selected_rektor = array_select_where($larare, array("mailchimp_id" => $id));
	
	$is_larare = count($selected_larare) > 0;
	$is_rektor = count($selected_rektor) > 0;
	
	if($is_larare || $is_rektor){
		$user = ($is_larare ? reset($selected_larare) : reset($selected_rektor));
		$skola = $user["skola"]; // will be a 4 letter code like "ting" for Tingvalla
	}
	
	$grupper = array_select_where($grupper, array("skola" => $skola));
	$grupper = array_orderby($grupper, "g_arskurs", SORT_ASC , "larar_id", SORT_ASC);
	
	$attributes = array("dates" => array("d1", "d2", "d3", "d4", "d5", "d6", "d7", "d8"), "hidden" => array("id", "mailchimp_id", "larar_id", "skola", "g_arskurs", "updated"), "textbox" => array("info", "notes", "mat"), "select" => array("larar_id"));
	
	$larare_samma_skola = array_select_where($larare, array("skola" => $skola));
	
	$body .= '<p id="saveResponse"><p>';
	
	$recent_arskurs = "0";
	$groupCounter = 0;
	
	foreach($grupper as $grupp){
		$arskurs = $grupp["g_arskurs"];
		if($arskurs != $recent_arskurs){
			$body .= "<h1>Årskurs $arskurs</h1>";
			$groupcounter = 0;
		}
		$groupCounter += 1;
		$recent_arskurs = $arskurs;
		
		if(trim($grupp["klass"]) != ""){
			$klassname = $grupp["klass"];
		}
		else {
			$klassname = "Grupp " . $groupCounter;
		}
		$body .= "<h2>$klassname</h2>";
		foreach($grupp as $gruppField => $fieldValue){
			
			/* Determine what kind of field it is and how it should be shown*/
			$fieldType = "";
			foreach($attributes as $key => $values){
				if(in_array($gruppField, $values)){
					$fieldType = $key;
				}
			}
			$tag = "input";
			$fieldAttributes = array("name" => $grupp["id"] . "%" . $gruppField, "type" => "text", "value" => htmlspecialchars($fieldValue));
			$content = "";
			switch($fieldType){
				case "dates":
				$tag = "li";
				break;
				
				case "hidden":
				$fieldAttributes[] = "hidden";
				break;
				
				case "textbox":
				$tag = "textarea";
				$content = htmlspecialchars($fieldValue);
				break;
				
				case "select":
				$tag = "select";
				foreach($larare_samma_skola as $key => $row){
					$selected = ($row["id"] == $grupp["larar_id"] ? "selected" : "");
					$content .= tag("option", $row["fname"] . " " . $row["lname"], array("value" => $row["id"], $selected));
				}
				
				break;
				
				default: 
				break;
				
			}
			
			
			$body .= $gruppField;
			$body .= tag($tag, $content , $fieldAttributes) . "<br>";
			
			
		}
		
		//echop($grupp);
		
	}
	$html .= tag("body", $body);
	echo $html;
	//48 a7a4d6c76d
	
?>

