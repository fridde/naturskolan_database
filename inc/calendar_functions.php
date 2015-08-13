<?php
	
	function update_calendar_db($singleUserId = "", $removeOnly = FALSE){
		
		$ini_array = parse_ini_file("config.ini", TRUE);
		
		$heldagsArray = array("2/3" => $ini_array["dagar2"]["hel"], "5" => $ini_array["dagar5"]["hel"]);
		$lektionsArray = array("2/3" => $ini_array["dagar2"]["lektion"], "5" => $ini_array["dagar5"]["lektion"]);
		
		$titleTranslator = array("2/3" => $ini_array["titleTranslator2"], "5" => $ini_array["titleTranslator5"]);
		
		$locationTranslator = array("2/3" => $ini_array["locationTranslator2"], "5" => $ini_array["locationTranslator5"]);
		
		$userArray = array(); 
		if($singleUserId != ""){
			$userArray[] = $singleUserId;
		}
		else {
			$criteria = array("status", "subscribed");
			$larare = sql_select("larare", $criteria);
			foreach($larare as $row){
				$userArray[] = $row["mailchimp_id"];
			}
		}
		
		foreach($userArray as $userId){
			$criteria = array("mailchimp_id", $userId);
			sql_delete("kalender",  $criteria);
			
			if(!$removeOnly){
				
				$lararRow = sql_select("larare", $criteria);
				$lararRow = $lararRow[0];
				$arskurs = $lararRow["g_arskurs"];
				$heldagar = array();
				$lektioner = array();
				
				if($arskurs != ""){
					$heldagar = $heldagsArray[$arskurs];
					$lektioner = $lektionsArray[$arskurs];
				} 
				$visitArray = array_merge($heldagar, $lektioner);
				
				$insertArray = array();
				foreach($lararRow as $column => $cell){
					if($cell != "" && in_array($column, $visitArray)){
						
						$isLektion = in_array($column, $lektioner);
						$isHeldag = in_array($column, $heldagar);
						
						/* the date might be given as several dates*/
						$severalDayArray = explode("+", $cell);
						$severalDayArray = array_walk_values($severalDayArray, "trim");
						foreach($severalDayArray as $groupCount => $day){
							
							
							/* creating a new event */
							$eR = array(); // eR = eventRow
							/* adding all parameters */
							/* ___mailchimp_id___ */
							$eR["mailchimp_id"] = $lararRow["mailchimp_id"];
							/* ___startdate + enddate ___ */
							if($isLektion){
								$startdateArray = explode("kl", $day);
								$eR["startdate"] = trim($startdateArray[0]);
								$enddateArray = explode("kl", $day);
								$eR["enddate"] = trim($enddateArray[0]);
							}
							else{
								$eR["startdate"] = $day;
								$eR["enddate"] = $day;
							}
							
							
							/* ___title___ */
							
							
							$eR["title"] = $titleTranslator[$arskurs][$column] . " med " . $lararRow["skola"] . " (" 
							. $lararRow["fname"] . " " . substr($lararRow["lname"], 0, 1) . ")" ;
							
							if(count($severalDayArray) > 1){
								$eR["title"] .= "[Grupp " . ($groupCount + 1) . "]";
							}
							
							/* ___starttime + endtime___ */
							/* given in UTC */
							$startime = "0000";
							$endtime = "2359";
							if($isHeldag){
								$starttime = "0815";
								$whichWeekday = date('D', strtotime($day));
								$endtime = "1330";
								if(strtolower($whichWeekday) == "tue"){
									//$endtime = "1315"; // bussbolagets krav 
									//uncomment if needed again!
								}
								
							}
							if($isLektion){
								$lektionsTidArray = explode("kl", $lararRow[$column]);
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
							$eR["location"] = $locationTranslator[$arskurs][$column];
							
							/* ___ description ___ */
							$description = array();
							$description[] = "Tid: " . $starttime . "-" . $endtime ;
							$description[] = "Lärare: " . $lararRow["fname"] . " " . $lararRow["lname"];
							$description[] = "Årskurs: " . $lararRow["g_arskurs"]; 
							$description[] = "Mobil: " . $lararRow["mobil"];
							$description[] = "Mejl: " . $lararRow["email"];
							$description[] = "Klass " . $lararRow["klass"] . " med " . $lararRow["elever"] . " elever";
							$description[] = "Matpreferenser: " . $lararRow["mat"];
							$description[] = "Annat: " . $lararRow["info"];
							
							$eR["description"] = implode('\n', $description);
							
							/* final inclusion in the big Array*/
							$insertArray[] = $eR;
						}
					}
				}
				sql_insert_rows("kalender", $insertArray);
			}
			}
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