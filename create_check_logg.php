<?php

/* PREAMBLE */
$url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
$filename = "include.php";
copy($url, $filename);
include $filename;
/* END OF PREAMBLE */
inc("fnc,sql,test");

$ini_array = parse_ini_file("config.ini", TRUE);
$dayArray = array_keys($ini_array["titleTranslator2"]);

$groupTable = sql_select("grupper", array("status" => "active"));
$teacherTable = sql_select("larare", array("status" => "subscribed"));
$schoolTable = sql_select("skolor");
$schoolTable = col_to_index($schoolTable, "short_name");

$loggText = array();
$eol = PHP_EOL;
$cross = "++++++++++";

$loggText[] = "LOGGREPORT";
$loggText[] = date("c");
$loggText[] = $cross ;

/* DUBBLA DATUM */
$loggText[] = "Dubbla datum";
$loggText[] = "[Kollar om någon klass har samma datum på olika dagar]";
$doubleDateGroups = check_double_dates($groupTable);
if(count($doubleDateGroups) > 0){
  foreach($doubleDateGroups as $group){
    $loggText[] = "[id=" . $group["id"] . "] Klass \"" . $group["klass"] . "\" (åk " . $group["g_arskurs"] . ") från " . $schoolTable[$group["skola"]]["long_name"];
  }
} else {
  $loggText[] = "Inga klasser har dubbla datum.";
}
$loggText[] = $cross ;

/* FÖR STORA GRUPPER */
$loggText[] = "För stora grupper";
$loggText[] = "[Kollar om någon klass har för många elever]";

$tooLargeGroups = check_group_too_large($groupTable, $maxSize = 30);
if(count($tooLargeGroups) > 0){
  foreach($tooLargeGroups as $group){
    $text = "[id=" . $group["id"] . "] Klass \"" . $group["klass"] . "\" (åk " . $group["g_arskurs"] . ") från ";
    $text .= $schoolTable[$group["skola"]]["long_name"] . " har " . $group["elever"] . " elever.";
    $loggText[] =  $text ;
  }
} else{
  $loggText[] = "Inga klasser har för många elever.";
}
$loggText[] = $cross ;

/* MOBILNUMMER */
$withoutMobile = check_mobile($teacherTable);
if(count($withoutMobile) > 0){
  foreach($withoutMobile as $teacher){
    $text =  $teacher["fname"] . " " . $teacher["lname"] . " från " . $schoolTable[$teacher["skola"]]["long_name"];
    $text .= " har nummer " . '"' . $teacher["mobil"] . '"';
    $loggText[] = $text;
  }
} else {
  $loggText[] = "Alla lärare har angett sina mobilnummer";
}

/*
check_day_order($groupTable)
check_number_groups_per_school($groupTable, $schoolTable)
check_group_is_checked($groupTable, $teacherTable)
 */
echo implode($eol, $loggText);

?>
