<?php
	// to enable debugging in plugin DBGp, add "?XDEBUG_SESSION_START=test" to your url
	// to test this, use http://localhost/naturskolan_database/sandbox.php?XDEBUG_SESSION_START=test&type=naturskolan&trial=01
	// and exchange the type-parameter for whatever you specified in the switch-case
	if(isset($_REQUEST["info"])){
		phpinfo();
		exit();
	}
	include("autoload.php");
	activateDebug();
	inc("vendor");
	//updateAllFromRepo();
	use \Fridde\Utility as U;
	use \Fridde\SMS as SMS;
	use \Fridde\NSDB_MailChimp as M;
	
	$N = new \Fridde\Naturskolan();
	
	$type = (isset($_REQUEST["type"])) ? strtolower($_REQUEST["type"]) : "" ;
	
	switch($type){
		case "naturskolan":
		 
		//$result = $N->get("visits", ["id", "in", ["2", "3"]]);
		//$N->update("event", ["name" => "Hans", "Date" => "2016-7-09"], 5);
		$N->delete("event", 6);
		$result = $N->get("events");
		//echo var_export($N->test("getStandardColumn", "school"));
		//$result = $N->getGroup(["isActive" => "false"], false);
		print_r2($result);
		//echo $result[0]["name"];
		break;
		
		case "alphabet":
			$alpha = range('a', 'z');
			array_walk($alpha, function($a){echo $a."<br>";});
		break;
		
		case "password":
			$password = "ekil_isgy";
			$school = $N->get("password/School", ["Password", $password]);
			var_export($school);
			
			/* 
			$schools = $N->get("schools/ShortName", );
			$pw_array = [["id", "School", "Password"],[]];
			foreach($schools as $school){
				$pw = $N->createPassword($school);
				$pw_array[1][] = ["", $school, $pw];
			}
			//print_r2($pw_array);
			//$N->create("password", $pw_array);
			*/
		break;
		
		case "update":
		updateAllFromRepo();
		break;
		
		case "mailchimp":
		$M = new M();
		print_r2($M->getMembers());
		//echo serialize($M->getCategoriesAndInterests());
		////$result = $M->get('lists/1ff7412fc8/members', $args);
		// $result = $M->get('lists/1ff7412fc8/interest-categories/8548b28556/interests', $args);
		//$result = $M->get('lists/1ff7412fc8/interest-categories/', $args);
		
		break;
		
		default:
		echo 'The type _' . $type . '_ was not found in the switch case.';
		
	}
	
		
	//echo print_r2($M->getMembers());
	//$interests = $M->updateInterests();
	
	
	/*
	
	
	/*
	$user = array("skola" => "berg");
	
	$find = "berg";
	$cand_1 = "Bergius";
	$cand_2 = "Råbergsskolan";
	similar_text($find, $cand_1, $first);
	similar_text($find, $cand_2, $second);
	
	//echo $first . "<br>" . $second;
	
	echo sql_get_highest_id("larare");
	
	$skolor = sql_select("skolor");
	$skolor = col_to_index($skolor, "long_name");
	
	$user_skola = $user["skola"];
	$closest_match = find_most_similar($user_skola, array_keys($skolor));
	$user["skola"] = $skolor[$closest_match]["short_name"];
	
	//echo $user["skola"];
	
	/*
	$string = "2012-07-13 + 2012-07-14 + 2012-07-16";
	$otherString = "2012-07-13";
	
	$array1 = explode("+", $string);
	$array2 = explode("+", $otherString);
	
	echo gettype($array1) . "<br>";
	echo gettype($array2) . "<br>";
	foreach($array2 as $test){echo $test;}
	'/
	
	//copy("https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php", "whatever.php");
	//echo file_get_contents("config.ini");
	update_calendar_db();
	convert_database_to_ics("kalender");
	*/
	
	?>
		