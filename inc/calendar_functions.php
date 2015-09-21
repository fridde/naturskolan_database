<?php
function update_calendar_db(){
	$ini_array = parse_ini_file("config.ini", TRUE);
	$heldagsArray = array("2/3" => $ini_array["dagar2"]["hel"], "5" => $ini_array["dagar5"]["hel"]);
	$lektionsArray = array("2/3" => $ini_array["dagar2"]["lektion"], "5" => $ini_array["dagar5"]["lektion"]);
	$titleTranslator = array("2/3" => $ini_array["titleTranslator2"], "5" => $ini_array["titleTranslator5"]);
	$locationTranslator = array("2/3" => $ini_array["locationTranslator2"], "5" => $ini_array["locationTranslator5"]);

	$groupTable = sql_select("grupper", array("status" => "active"));
	$teacherTable = sql_select("larare", array("status" => "subscribed"));
	$schoolTable = sql_select("skolor");

	/* Start of the process! Here we go!  */
	sql_delete("kalender"); /* Erases ALL entries */

	$insertArray = array();
	foreach($groupTable as $group){
		$arskurs = $group["g_arskurs"];

		$heldagar = $heldagsArray[$arskurs];
		$lektioner = $lektionsArray[$arskurs];
		$visitArray = array_merge($heldagar, $lektioner);

		foreach($group as $columnName => $cell){

			$larare = array_select_where($teacherTable, array("id" => $group["larar_id"]));
			if(count($larare) == 1){
				$larare = reset($larare);
			} else {
				$larare = array("fname" => "ERROR: Something is wrong in the database. Check this!", "lname" => "!!", "mobil" => "", "email" => "");
			}
			$skola = array_select_where($schoolTable, array("short_name" => $group["skola"]));
			if(count($skola) == 1){
				$skola = reset($skola);
			} else{
				$skola = array("long_name" => "ERROR: Non-existing school. Check database!");
			}

			if($cell != "" && in_array($columnName, $visitArray)){
				$isLektion = in_array($columnName, $lektioner);
				$isHeldag = in_array($columnName, $heldagar);

				/* creating a new event */
				$eR = array(); // eR = eventRow
				/* adding all parameters */

				/* ___dates___ */
				if($isLektion){
					$startdateArray = explode("kl", $cell);
					$eR["startdate"] = trim($startdateArray[0]);
					$enddateArray = explode("kl", $cell);
					$eR["enddate"] = trim($enddateArray[0]);
				}
				else{
					$eR["startdate"] = $cell;
					$eR["enddate"] = $cell;
				}

				/* ___title___ */
				$eR["title"] = $titleTranslator[$arskurs][$columnName] . " med " . $skola["long_name"] . " ("
				. $larare["fname"] . " " . substr($larare["lname"], 0, 1) . ")" ;

				/* ___starttime + endtime___ */
				/* given in UTC */
				$startime = "0000";
				$endtime = "2359";
				if($isHeldag){
					$starttime = "0815";
					$whichWeekday = date('D', strtotime($cell));
					$endtime = "1330";
					if(strtolower($whichWeekday) == "tue"){
						//$endtime = "1315"; // bussbolagets krav
						//uncomment if needed again!
					}
				}
				if($isLektion){
					$lektionsTidArray = explode("kl", $cell);
					$lastIndex = count($lektionsTidArray) - 1;
					if($lastIndex > 0){
						$starttime = trim($lektionsTidArray[$lastIndex]);
						$starttime = str_replace(array('\.', ':', ' '), "", $starttime);
						$starttime = substr($starttime, -4, 4);
						$endtime = $starttime + 100;
						if(strlen($endtime) == 3){
							$endtime = "0" . $endtime;
						}
					}
				}
				$eR["starttime"] = $starttime;
				$eR["endtime"] = $endtime;
				/* ___location ___ */
				$eR["location"] = $locationTranslator[$arskurs][$columnName];
				/* ___ description ___ */
				$description = array();
				$description[] = "Tid: " . $starttime . "-" . $endtime ;
				$description[] = "Lärare: " . $larare["fname"] . " " . $larare["lname"];
				$description[] = "Årskurs: " . $group["g_arskurs"];
				$description[] = "Mobil: " . $larare["mobil"];
				$description[] = "Mejl: " . $larare["email"];
				$description[] = "Klass " . $group["klass"] . " med " . $group["elever"] . " elever";
				$description[] = "Matpreferenser: " . $group["mat"];
				$description[] = "Annat: " . $group["info"];
				$eR["description"] = implode('\n', $description);
				/* final inclusion in the big Array*/
				$insertArray[] = $eR;
			}
		}
	}

	sql_insert_rows("kalender", $insertArray);
}

function convert_database_to_ics($sqlTable){
	$timediff = -100; // due to one hour forward in STHLM
	$calendar_array = sql_select($sqlTable);
	$iSA = array(); // iSA = icsStringArray
	$iSA[] = 'BEGIN:VCALENDAR';
	$iSA[] = 'X-WR-CALNAME:SigtunaNaturskola Schema';
	$iSA[] = 'VERSION:2.0';
	$iSA[] = 'PRODID:-//SigtunaNaturskola//EN';
	$iSA[] = 'CALSCALE:GREGORIAN';
	$iSA[] = 'METHOD:PUBLISH';
	$iSA = array_merge($iSA, timeZoneArray());
	foreach($calendar_array as $eventRow){
		$titlePrefix = "";
		$criteria = array("AND", array("dag" => $eventRow["startdate"], "aktivitet" => $eventRow["title"]));
		$matchingRows = sql_select("arbetsfordelning", $criteria);
		if(count($matchingRows) == 1){
			$matchingRow = reset($matchingRows);
			$titlePrefix = '[' . $matchingRow["personal"] . '] ';
		}
		$iSA[] = 'BEGIN:VEVENT';
		$iSA[] = 'UID:' . uniqid();
		$iSA[] = 'DTSTAMP:' . dateToCal(time());
		$iSA[] = 'CLASS:PUBLIC';
		$iSA[] = 'LOCATION:' . escapeString($eventRow["location"]);
		$iSA[] = 'DESCRIPTION:' . escapeString($eventRow["description"]);
		$iSA[] = 'SUMMARY:' . escapeString($titlePrefix . $eventRow["title"]);
		//$eventRow["starttime"] = $eventRow["starttime"] + $timediff;
		// $eventRow["endtime"] = $eventRow["endtime"] + $timediff;
		$startdateString = $eventRow["startdate"] . " " . substr($eventRow["starttime"], 0, 2) . ":" .
		substr($eventRow["starttime"], 2, 2) . ":00";
		$startdate = strtotime($startdateString);
		$enddateString = $eventRow["enddate"] . " " . substr($eventRow["endtime"], 0, 2) . ":" .
		substr($eventRow["endtime"], 2, 2) . ":00";
		$enddate = strtotime($enddateString);
		$iSA[] = 'DTSTART;TZID=Europe/Stockholm:' . dateToCal($startdate);
		$iSA[] = 'DTEND;TZID=Europe/Stockholm:' . dateToCal($enddate);
		$iSA[] = 'END:VEVENT';
	}
	$iSA[] = 'END:VCALENDAR';
	$icsString = implode("\r\n", $iSA);
	file_put_contents("aventyr_kalender.ics", $icsString);
}
function dateToCal($timestamp) {
	return date('Ymd\THis', $timestamp); // we don't use UTC here
}
function escapeString($string) {
	return preg_replace('/([\,;])/','\\\$1', $string);
}
function blur_mail($string){
	$string = str_replace("@edu.sigtuna.se", "", $string);
	$string = str_replace("@", " AT ", $string);
	return $string;
}
function timeZoneArray(){
	$str = array();
	$str[] = 'BEGIN:VTIMEZONE';
	$str[] = 'TZID:Europe/Stockholm';
	$str[] = 'X-LIC-LOCATION:Europe/Stockholm';
	$str[] = 'BEGIN:DAYLIGHT';
	$str[] = 'TZOFFSETFROM:+0100';
	$str[] = 'TZOFFSETTO:+0200';
	$str[] = 'TZNAME:CEST';
	$str[] = 'DTSTART:19700329T020000';
	$str[] = 'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3';
	$str[] = 'END:DAYLIGHT';
	$str[] = 'BEGIN:STANDARD';
	$str[] = 'TZOFFSETFROM:+0200';
	$str[] = 'TZOFFSETTO:+0100';
	$str[] = 'TZNAME:CET';
	$str[] = 'DTSTART:19701025T030000';
	$str[] = 'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10';
	$str[] = 'END:STANDARD';
	$str[] = 'END:VTIMEZONE';
	return $str;
}
