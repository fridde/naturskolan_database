<?php

/* PREAMBLE */
$url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
$filename = "include.php";
copy($url, $filename);
include $filename;
/* END OF PREAMBLE */
inc("fnc,sql,test");

$req_translator = array("t" => "type");
extract(extract_request($req_translator));

$ini_array = parse_ini_file("config.ini", TRUE);
$dayArray = array_keys($ini_array["titleTranslator2"]);

$groupTable = sql_select("grupper", array("status" => "active"));
$teacherTable = sql_select("larare", array("status" => "subscribed"));
$schoolTable = sql_select("skolor");
$schoolTable = col_to_index($schoolTable, "short_name");

$logg = array();
if($type == "text"){
  $eol = PHP_EOL;
} else {
  $eol = "<br>";
}

$cross = "++++++++++";

$logg[] = $cross ;
$logg[] = "LOGGREPORT";
$logg[] = date("c");
$logg[] = $cross ;
$logg[] = "";

/* DUBBLA DATUM */
$logg[] = $cross ;
$logg[] = "Dubbla datum";
$logg[] = "[Kollar om någon klass har samma datum på olika dagar]";
$logg[] = $cross ;

$doubleDateGroups = check_double_dates($groupTable);
if(count($doubleDateGroups) > 0){
  foreach($doubleDateGroups as $group){
    $logg[] = "[id=" . $group["id"] . "] Klass \"" . $group["klass"] . "\" (åk " . $group["g_arskurs"] . ") från " . $schoolTable[$group["skola"]]["long_name"];
  }
} else {
  $logg[] = "Inga klasser har dubbla datum.";
}
$logg[] = "";

/* FEL GRUPPSTORLEK */
$logg[] = $cross ;
$logg[] = "För stora grupper";
$logg[] = "[Kollar om någon klass har för få eller för många elever]";
$logg[] = $cross ;

$wrongSizeGroups = check_group_size($groupTable, $minSize = 4, $maxSize = 30);
if(count($wrongSizeGroups) > 0){
  foreach($wrongSizeGroups as $group){
    $text = "[id=" . $group["id"] . "] Klass \"" . $group["klass"] . "\" (åk " . $group["g_arskurs"] . ") från ";
    $text .= $schoolTable[$group["skola"]]["long_name"] . " har " . $group["elever"] . " elever.";
    $logg[] =  $text ;
  }
} else{
  $logg[] = "Alla klasser har ett rimligt antal elever.";
}
$logg[] = "";

/* MOBILNUMMER */
$logg[] = $cross ;
$logg[] = "Inget mobilnummer";
$logg[] = "[Kollar om någon lärare har ej angett sitt mobilnummer]";
$logg[] = $cross ;

$withoutMobile = check_mobile($teacherTable);
if(count($withoutMobile) > 0){
  foreach($withoutMobile as $teacher){
    $text =  $teacher["fname"] . " " . $teacher["lname"] . " från " . $schoolTable[$teacher["skola"]]["long_name"];
    $text .= " har nummer " . '"' . $teacher["mobil"] . '"';
    $logg[] = $text;
  }
} else {
  $logg[] = "Alla lärare har angett sina mobilnummer";
}
$logg[] = "";

/* FEL ORDNING PÅ DAGARNA */
$logg[] = $cross ;
$logg[] = "Fel ordning";
$logg[] = "[Kollar ifall någon grupp kommer i fel ordning, dvs dag 2 före dag 1]";
$logg[] = $cross ;

$felOrdning = check_day_order($groupTable);
if(count($felOrdning) > 0){
  foreach($felOrdning as $group){
    $text = "Grupp " . $group["klass"] . " [id=" . $group["id"] . "] från " . $schoolTable[$group["skola"]]["long_name"] ;
    $text .= " möter Naturskolan i fel ordning." ;
    $logg[] = $text;
  }
} else {
  $logg[] = "Alla grupper kommer i rätt ordning";
}
$logg[] = "";

/* FEL ANTAL GRUPPER PER SKOLA */
$logg[] = $cross ;
$logg[] = "Fel antal grupper anmälda";
$logg[] = "[Kollar om någon skola kommer med för få eller för många grupper]";
$logg[] = $cross ;

$wrongNumber = check_number_groups_per_school($groupTable, $schoolTable);
if(count($wrongNumber) > 0){
  foreach($wrongNumber as $schoolName => $school){
    if(isset($school["2/3"])){
      $logg[] = $schoolName . " årskurs 2/3 har " . $school["2/3"]["is"] . " grupper, men borde ha " . $school["2/3"]["should"] . " grupper.";
    }
    if(isset($school["5"])){
      $logg[] = $schoolName . " årskurs 5 har " . $school["5"]["is"] . " grupper, men borde ha " . $school["5"]["should"] . " grupper.";
    }
  }
} else {
  $logg[] = "Alla skolor har rätt antal grupper för varje årskurs.";
}
$logg[] = "";

/* GROUPS WITHOUT CONFIRMATION*/
$logg[] = $cross ;
$logg[] = "Utan bekräftelse";
$logg[] = "[Kollar om någon grupp inte har bekräftats]";
$logg[] = $cross ;

$notConfirmed = check_group_is_checked($groupTable, $teacherTable);
if(count($notConfirmed) > 0){
  foreach($notConfirmed as $group){
    $text = "Grupp " . $group["grupp"]["klass"] . " [id=" . $group["grupp"]["id"] . "] från " . $schoolTable[$group["grupp"]["skola"]]["long_name"] ;
    $text .= " med lärare " . $group["lärare"]["fname"] . " " . $group["lärare"]["lname"];
    $text .= " har ej bekräftats." ;
    $logg[] = $text;
  }
} else {
  $logg[] = "Alla grupper är bekräftade.";
}
$logg[] = "";

echo implode($eol, $logg);

?>
