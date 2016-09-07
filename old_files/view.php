<?php
/* This page will view the calendar depending on the type, defined by the GET parameter "t".   */
/* PREAMBLE */
$url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
$filename = "include.php";
copy($url, $filename);
include $filename;
/* END OF PREAMBLE */
inc("fnc, sql, cal");
activate_all_errors();

$ini_array = parse_ini_file("config.ini", TRUE);

$columnsWithDates = array_keys($ini_array["titleTranslator2"]); //contains d1, d2, ..., d8
$locations_2 = $ini_array["locationTranslator2"];
$locations_5 = $ini_array["locationTranslator5"];

$bus_alwaysIncludedSchools = array("Skepptuna", "Råbergs");
$neverIncludedSchools = array("Annan skola");
$bus_alwaysIncludedLocations = array("Flottvik", "Näsudden");
$neverIncludedLocations = array("Skolan");

$req_translator = array("t" => "type");
extract(extract_request($req_translator));

$startDay = new DateTime(); 				//today
$endDay = new DateTime();
$endDay = $endDay->modify('+100 days');
$interval = new DateInterval('P1D'); 		// means 1 day
$date_vector = new DatePeriod($startDay, $interval, $endDay);

$groupTable = sql_select("grupper", array("status" => "active"));
$teacherTable = sql_select("larare", array("status" => "subscribed"));
$schoolTable = sql_select("skolor");

// Producing the view
list($html, $head, $body, $recentMonth) = array_fill(0,20,"");

$head .= qtag("meta");
$incString = "jquery,css";
$head .= inc($incString, FALSE, TRUE);
$html .= tag("head", $head);

if($type != FALSE){ // type is defined by the GET parameter "t"
  foreach($date_vector as $date){	//cycle through dates
    $p = "";
    $currentMonth = $date->format("m");  //should be 07 for july, 11 for november etc
    foreach($groupTable as $group){  //cycle through rows
      $skola = $group["skola"];
      $skola_long = array_select_where($schoolTable, array("short_name" => $skola));
      $skola_long = reset($skola_long);
      $skola_long = $skola_long["long_name"];
      $arskurs = $group["g_arskurs"];
      $antal_elever = $group["elever"];
      $larare = array_select_where($teacherTable, array("id" => $group["larar_id"]));
      if(count($larare) == 1){
        $larare = reset($larare);
      }


      foreach($group as $columnName => $cell){
        $isWantedColumn = in_array($columnName, $columnsWithDates);
        $formattedDate = $date->format("Y-m-d");

        if($isWantedColumn && $formattedDate == $cell){
          // Here comes the actual view

          if($arskurs == "2/3"){
            $till = $locations_2[$columnName];
          }
          elseif($arskurs == "5"){
            $till = $locations_5[$columnName];
          }

          switch($type){
            case "ellenius":
            if(in_array($skola_long, $neverIncludedSchools) || in_array($till, $neverIncludedLocations)){
              $includedTour = FALSE;
            }
            else{
              if(in_array($skola_long, $bus_alwaysIncludedSchools) || in_array($till, $bus_alwaysIncludedLocations)){
                $includedTour = TRUE;
              }
              else{
                logg("The check whether the bus journey should be included or not had a logical error, since it landed in an else-clause it shouldn't have landed in. Check the php-script! (Error was called from view.php)");
                $includedTour = FALSE;
              }
            }
            if($includedTour){
              $ul = "";
              for ($i = 1; $i <= 2; $i++) {
                $li = "";
                if($i == 1){
                  $li .= "Från " . $skola_long . " till " . $till . "<br>";
                  $li .= "Avfärd: kl 08.15 från skolan <br>";
                } else{
                  $li .= "Från " . $till . " till " . $skola_long . "<br>";
                  $li .= "Avfärd: kl 13.30 från " . $till . "<br>";
                }
                $li .= "Cirka " . $antal_elever . " elever plus 2 lärare.";
                $ul .= tag("li", $li);
              }
              $p .= tag("ul", $ul);
            }
            break;

            case "mat":
            if(in_array($skola_long, $neverIncludedSchools) || in_array($till, $neverIncludedLocations)){
              $included_food = FALSE;
            } else{
              $included_food = TRUE;
            }

            if($included_food){
              $li = "";
              if($arskurs == "2/3"){
                $food = $ini_array["foodTranslator2"][$columnName];
              } elseif ($arskurs == "5") {
                $food = $ini_array["foodTranslator5"][$columnName];
              }
              $li .= $food . " för " . $group["elever"] . " elever till " . $till . ".<br>";
              $matpreferenser = (trim($group["mat"]) == "" ? "Inga allergier" : $group["mat"]);
              $li .= tag("strong", "Matpreferenser: ") . $matpreferenser;
              $ul = tag("li", $li);
              $p .= tag("ul", $ul);
            }
            break;

            case "overview":
            $li = "";
            if($arskurs == "2/3"){
              $dayTitle = $ini_array["titleTranslator2"][$columnName];
            } elseif($arskurs == "5"){
              $dayTitle = $ini_array["titleTranslator5"][$columnName];
            }

            $li .= $dayTitle . " [Plats: " . $till . "] <br>";
            $li .= "Grupp \"" . $group["klass"] . "\" (åk " . $group["g_arskurs"] . ") ";
            $li .= "från " . $skola_long;
            $li .= "med " . $group["elever"] . " elever. <br>";
            $li .= "Medföljande lärare: " . $larare["fname"] . " " . $larare["lname"];

            $ul = tag("li", $li);
            $p .= tag("ul", $ul);
            break;
          }
        }
      }
    }
    if($p != ""){
      if($currentMonth != $recentMonth){
        $h2 = $date->format("F") . " " . $date->format("Y"); // creates "July", "November", etc
        $body .= tag("h2", $h2);
      }
      $recentMonth = $currentMonth;
      $body .= tag("h3", $date->format("d M"));
      $body .= tag("p", $p);
    }
  }

}
else {
  $body .=  "You don't have the rights to see this page. Ask an admin for the right link!";
}

$html .= tag("body",$body);

echo $html;

?>
