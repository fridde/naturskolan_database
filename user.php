<?php
	
	/* PREAMBLE */
    $url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
    $filename = "include.php";
    //copy($url, $filename);
    include $filename;
    /* END OF PREAMBLE */
	inc("fnc, sql"); 
	activate_all_errors(); // REMOVE in production
	
	$ini_array = parse_ini_file("config.ini", TRUE);
	
	$headerTranslator = $ini_array["headerTranslator"];
	
	$id = $_REQUEST["id"]; // corresponding mailchimp-id that tells us who is entering
	
	$larare = sql_select("larare");
	$grupper = sql_select("grupper");
	$skolor = sql_select("skolor");
	//$rektorer = sql_select("rektorer");
	
	$user = array_select_where($larare, array("mailchimp_id" => $id));
	$user = (count($user) == 1 ? reset($user) : FALSE);
	
	$is_rektor = $user != FALSE && strtolower($user["rektor"]) == "true";
	$skola = $user["skola"]; // will be a 4 letter code like "ting" for Tingvalla
	$skola_long = array_select_where($skolor, array("short_name" => $skola), "all", TRUE);
	$skola_long = $skola_long["long_name"];
	
	$grupper = array_select_where($grupper, array("skola" => $skola));
	$grupper = array_orderby($grupper, "g_arskurs", SORT_ASC , "larar_id", SORT_ASC);
	
	$attributes = array("dates" => array("d1", "d2", "d3", "d4", "d5", "d6", "d7", "d8"), "ignore" => array("id", "mailchimp_id", "larar_id", "skola", "g_arskurs", "updated", "ltid", "notes", "special"), "textbox" => array("info", "mat"), "select" => array("larar_id"));
	
	$larare_samma_skola = array_select_where($larare, array("skola" => $skola));
	
	list($html, $head, $body, $nav) = array_fill(0,20, ""); 
	$head .= qtag("meta");	
	$head .= tag("title", "Naturskolans inställningar - Inloggad som " . $user['fname'] . ' ' . $user['lname']);
	$incString = "jquery,user_init,bootcss,bootjs,boottheme,fAwe";
	$head .= inc($incString, FALSE, TRUE);
	
	$html .= tag("head", $head);
	
	$body .= '<p id="saveResponse"><p>';
	
	$body .= '<h1>Hej, ' . $user['fname'] . ' ' . $user['lname'] . '!</h1>';
	
	$body .= 'Här är alla grupper från ' . $skola_long . "!";
	$recent_arskurs = "0";
	$groupCounter = 0;
	
	foreach($grupper as $grupp){
		$arskurs = $grupp["g_arskurs"];
		if($arskurs != $recent_arskurs){
			$body .= "<h2>Årskurs $arskurs</h2>";
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
		$body .= '<h3 id="h3_' . $grupp["id"] . '">' . $klassname .'</h3>';
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
			if($fieldType != "ignore"){
				
				$content = "";
				switch($fieldType){
					case "dates":
					$tag = "li";
					if($grupp["g_arskurs"] == "2/3"){
						$gruppField = $ini_array["titleTranslator2"][$gruppField];
						} elseif($grupp["g_arskurs"] == "5"){
						$gruppField = $ini_array["titleTranslator5"][$gruppField];
						} else {
						$gruppField = $gruppField;
					}
					
					$content = $gruppField . ": " . $fieldValue;
					$gruppField = ""; 
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
				
				$gruppField = (isset($headerTranslator[$gruppField]) ? $headerTranslator[$gruppField] : $gruppField);
				
				$body .= $gruppField;
				$body .= tag($tag, $content , $fieldAttributes) . "<br>";
				
			}
		}
		
		//echop($grupp);
		
	}
	$html .= tag("body", $body);
	echo $html;
	//48 a7a4d6c76d
	
?>

