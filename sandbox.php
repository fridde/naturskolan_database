<?php
	/* PREAMBLE */
    $url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
    $filename = "include.php";
    copy($url, $filename);
    include $filename;
    /* END OF PREAMBLE */
	 inc("fnc, sql, cal");
	// activate_all_errors();
	//echo "Hello!";
  //update_calendar_db();
  //convert_database_to_ics("kalender");
	//logg("Bla");
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
