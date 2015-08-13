<?php
	
	/* PREAMBLE */
    $url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
    $filename = "include.php";
    copy($url, $filename);
    include $filename;
    /* END OF PREAMBLE */
	inc("fnc, sql, cal"); 
	activate_all_errors();
	
	$ini_array = parse_ini_file("config.ini", TRUE);
	
	list($html) = array_fill(0,20, "");
	
	// CONSTANTS
	$hiddenElements = array();
	
	$id = $_REQUEST["id"];
	
	$larare = sql_select("larare");
	$grupper = sql_select("grupper");
	$skolor = sql_select("skolor");
	
	$selected_larare = array_select_where($larare, array("mailchimp_id" => $id));
	$selected_larare = reset($selected_larare);
	$skol_id = array_select_where($skolor, array("id" => $selected_larare["skola"]));
	$skol_id = reset($skol_id);
	
	$grupper = array_select_where($grupper, array("skola" => $skol_id["id"]));
	$grupper = array_orderby($grupper, "g_arskurs", SORT_ASC , "larar_id", SORT_ASC);
	
	$attributes = array("readOnly" => array("d1", "d2", "d3", "d4", "d5", "d6", "d7", "d8"), "hidden" => array("id", "mailchimp_id", "larar_id", "skola", "g_arskurs", "updated"), "textbox" => array("info", "notes", "mat"), "select" => array("larar_id"));
	
	$larare_samma_skola = array_select_where($larare, array("skola" => $skol_id["id"]));
	//echop($larare_samma_skola);
	
	foreach($grupper as $grupp){
		$html .= "<h1>Ny grupp</h1>";
		foreach($grupp as $gruppField => $fieldValue){
			
			/* Determine what kind of field it is and how it should be shown*/
			$fieldType = "";
			foreach($attributes as $key => $values){
				if(in_array($gruppField, $values)){
					$fieldType = $key;
				}
			}
			$tag = "input";
			$fieldAttributes = array("name" => $grupp["id"] . "_" . $gruppField, "type" => "text", "value" => htmlspecialchars($fieldValue));
			$content = "";
			switch($fieldType){
				case "readOnly":
				$fieldAttributes[] = "readonly";
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
					$content .= tag("option", $row["fname"] . " " . $row["lname"], array("value" => $key, $selected));
				}
				
				break;
				
				default: 
				break;
				
			}
			
			
			$html .= $gruppField;
			$html .= tag($tag, $content , $fieldAttributes) . "<br>";
			
			
		}
		
		//echop($grupp);
		
	}
	echo $html;
	//48 a7a4d6c76d
	
?>

