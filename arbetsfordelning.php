<?php 
	/* PREAMBLE */
    $url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
    $filename = "include.php";
    copy($url, $filename);
    include $filename;
    /* END OF PREAMBLE */
	inc("fnc, sql, cal");
	$ini_array = parse_ini_file("config.ini", TRUE);
	$api_key = $ini_array["security"]["api_key"];
	
	$arbetsfordelning = sql_select("arbetsfordelning");
	$kalender = sql_select("kalender");
	
	/* first, check for orphanaged arbetsfordelnings-entries, i.e. days that have people assigned to them, but no actual activities due to changes in the calendar. These have to be checked manually by someone with knowledge about the affairs of Naturskolan 
	*/
	foreach($arbetsfordelning as $afIndex => $afRow){   // af = arbetsfordelning
		// has to match kalender, NOT arbetsfordelning
		$criteria = array("startdate" => $afRow["dag"], "title" => $afRow["aktivitet"]);
		$existingEntries = array_select_where($kalender, $criteria);
		
		if(count($existingEntries) == 0){
			$arbetsfordelning[$afIndex]["orphan"] = "true";
		}
	}
	/* now, check for all events that have no corresponding entry in the arbetsfordelning-table and add these
	*/
	$rowClassArray = array("0" => "rowClass0", "1" => "rowClass1", "2" => "rowClass2", "3" => "rowClass3");
	foreach($kalender as $kalenderIndex => $kalenderRow){
		// has to match arbetsfordelning, NOT kalender
		$criteria = array("dag" => $kalenderRow["startdate"], "aktivitet" => $kalenderRow["title"]);
		$existingEntries = array_select_where($arbetsfordelning, $criteria);
		
		if(count($existingEntries) == 0){
			// create new entry
			$newEntry = array();
			$newEntry["dag"] =  $kalenderRow["startdate"];
			$newEntry["aktivitet"] = $kalenderRow["title"];
			$newEntry["personal"] = "";
			$newEntry["orphan"] = "false";
			$arbetsfordelning[] = $newEntry;
		}
	}
	
	// resort the table
	$arbetsfordelning = array_orderby($arbetsfordelning, "dag");
	
	/* finally, print out the array as a fixable table */
	$headerArray = array("Delete?", "Veckodag", "Dag", "Aktivitet", "Peja", "Janne", "Ludvig", "Marta", "Friedrich");
	$formHtml = "";
	$formLink = "arbetsfordelning_submit.php?key=" . $api_key;
	$formHtml .= '<form action="' . $formLink . '" method="POST" target="_self"> ';
	$formHtml .= '<input type="submit" value="Spara">';
	$formHtml .= '<table>
	<thead><tr>';
	foreach($headerArray as $header){
		$formHtml .= '<th>' . $header . '</th>';
	}
	$formHtml .= '</tr></thead>';
	$formHtml .= '<tbody>';				
	foreach($arbetsfordelning as $afRow){
		$personalArray = explode("+", $afRow["personal"]);
		$date = strtotime($afRow["dag"]);
		
		if($afRow["orphan"] == "true"){
			$rowClass = "orphan_row";
		}
		else {
			$rowClass = $rowClassArray[date("W", $date) % 4]; // we have 4 different colours for the rows
		}
		
		
		$formHtml .= '<tr class="' . $rowClass . '">';
		foreach($headerArray as $header){
			$formHtml .= '<td>';
			switch ($header) {
				case "Delete?":
				$formHtml .= '<input type="checkbox" name="delete[' . $afRow["dag"] . '][' . $afRow["aktivitet"] . ']"';
				$formHtml .= ' value="true">';
				break;
				
				case "Veckodag":
				$formHtml .= date("D", $date);
				break;
				
				case "Dag": 
				$formHtml .= $afRow["dag"];
				break;
				case "Aktivitet":
				$formHtml .= $afRow["aktivitet"];
				break;
				
				case "Peja":
				case "Janne":
				case "Ludvig":
				case "Marta":
				case "Friedrich":
				$formHtml .= '<input type="checkbox" name="data[' 
				. $afRow["dag"] . '][' . $afRow["aktivitet"] . '][]"';
				$formHtml .= ' value="'. $header .'"';
				if(in_array(substr($header, 0, 1), $personalArray)){
					$formHtml .= ' checked';
				}
				$formHtml .= '>';
				break;
			}
			$formHtml .= '</td>';
		}
		
		
		$formHtml .= '</tr>';
	}
	$formHtml .= '';
	$formHtml .= '</tbody></table>';
	
?>

<!DOCTYPE html>
<html>
	<head>
        <meta http-equiv = "Content-Type" content = "text/html; charset=UTF-8">
        <link type = "text/css" rel = "stylesheet" href = "inc/stylesheet.css"/>
        
	</head>
	<body>
		<?php echo $formHtml; ?>
	</body>
</html>	