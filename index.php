<?php
	
	/* PREAMBLE */
    $url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
    $filename = "include.php";
    copy($url, $filename);
    include $filename;
    /* END OF PREAMBLE */
	inc("fnc, sql, cal"); 
	
	$ini_array = parse_ini_file("config.ini", TRUE);
	
	$req_translator = array("a" => "arskurs", "p" => "code", "dl" => "download");
	extract(extract_request($req_translator));
	
	$arskurs = ($arskurs ? $arskurs : "2/3");
	
	$headerTranslation = $ini_array["headerTranslator"];
	
	$download = ($download == "true" ? TRUE : FALSE);
	
	$table_codes = array_flip($ini_array["table_codes"]);
	$translated_code = $table_codes[$code];
	
	if($translated_code != FALSE){
		
		switch($translated_code){
			case "rektorer":
			$tableName = "rektorer";
			$headers = array("fname", "lname", "skola");
			$criteria = "";
			break;
			
			case "aventyr_ak2":
			$tableName = "larare";
			$headers = array("fname", "lname", "skola", "d5", "d6", "d7", "d8", "email");
			$criteria = array("AND", array("g_arskurs" => "2/3", "d5" => "NOT:", "status" => "NOT:archived"));
			break;
			
			case "aventyr_ak5":
			$tableName = "larare";
			$headers = array("fname", "lname", "skola", "d1", "d2", "d3", "d4", "email");
			$criteria = array("AND", array("g_arskurs" => "5", "d1" => "NOT:", "status" => "NOT:archived"));
			break;
			
			case "aventyr_all":
			$tableName = "larare";
			$headers = "all";
			$criteria = array("status" => "NOT:archived");
			break;
			
			case "aventyr_mat":
			$tableName = "larare";
			$headers = array("fname", "lname", "skola", "a1d1", "mat", "email");
			$criteria = array("AND", array("a1d1" => "NOT:", "status" => "NOT:archived"));
			break;
			
			default:
			echo 'ERROR: The code "' . $code . '" was not defined. Contact the webmaster and let them check index.php. And get a coffee!<br><br>';
			break;
		}
		
		$tableContent = sql_select($tableName, $criteria ,$headers);
		$tableContent = array_change_col_names($tableContent, $headerTranslation);
		
		foreach($tableContent as $rowIndex => $row) {
			if(isset($row["email"])){
				$mail = $row["email"];
				if (strpos($mail,'alias') !== false) {
					$mail = "[har flera grupper, se annan rad]";
				} else {
					$mail = strtolower(blur_mail($mail));
				}
				$tableContent[$rowIndex]["email"] = $mail;
			}
		}
	} 
	list($html, $head, $body) = array_fill(0,20,""); 
	
	$head .= qtag("meta");
	$incString = "jquery,DTjQ,DTTT,DTfH,DTin,jqueryUIcss,DTcss,DTfHcss,DTTTcss,css";
	$head .= inc($incString, FALSE, TRUE);
	$html .= tag("head", $head);
	
	if($code != FALSE){
		$body .= create_htmltable_from_array($tableContent);
		if($download){
			array_to_csv_download($tableContent, "export.csv", "\t");
		}
	}
	else {
		$body .=  "You don't have the rights to see this page. Ask an admin for the right link!";
	}
	
	$html .= $body;
	
	echo $html;
	
