<?php
	// to enable debugging in plugin DBGp, add "?XDEBUG_SESSION_START=test" to your url
	
	if(isset($_REQUEST["info"])){
		phpinfo();
		exit();
	}
	include("autoload.php");
	activateDebug();
	inc("vendor");
	updateAllFromRepo();
	use \Fridde\Utility as U;
	use \Fridde\SMS as SMS;
	use \Fridde\NSDB_MailChimp as M;
	
	$test = (isset($_REQUEST["type"])) ? strtolower($_REQUEST["type"]) : "" ;
	
	switch($test){
		case "":
		break;
		
		default:
	
	}
	/*include("autoload.php");
	inc("vendor");
	activateDebug();
	
	
	
	$M = new M();

	//print_r2([[1,2,3],[4,5,6 => [7,8,9]]]);
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
