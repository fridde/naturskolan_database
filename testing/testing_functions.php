<?php

/* Will check whether any class is coming to two different locations at the same date
Returns an array with bad groups
*/
$ini_array = parse_ini_file("config.ini", TRUE);

function check_double_dates($groupTable){
  global $ini_array;
  $errorArray = array();

  $dayArray = array_keys($ini_array["titleTranslator2"]);

  foreach($groupTable as $group){
    $rowDays = array();
    foreach($group as $columnName => $cell){
      if(in_array($columnName, $dayArray) && trim($cell) != ""){
        $rowDays[] = $cell;
      }
    }
    $distinctDays = array_unique($rowDays);
    if(count($distinctDays) != count($rowDays)){
      $errorArray[] = $group;
    }
  }

  return $errorArray;
}

function check_group_too_large($groupTable, $maxSize = 30){
  $errorArray = array();

  foreach($groupTable as $group){
    if($group["elever"] > $maxSize){
      $errorArray[] = $group;
    }
  }

  return $errorArray;
}

function check_mobile($teacherTable){
  $errorArray = array();

  foreach ($teacherTable as $key => $teacher) {
    $numberToCheck = trim($teacher["mobil"]);
    if($numberToCheck == "" || substr($numberToCheck, 0, 2) == "08"){
      $errorArray[] = $teacher;
    }
  }

  return $errorArray;
}

function check_day_order($groupTable){
  $errorArray = array();
  global $ini_array;
  $dayArray = array_keys($ini_array["titleTranslator2"]);

  foreach($groupTable as $group){
    $rowDays = array();
    foreach($group as $columnName => $cell){
      if(in_array($columnName, $dayArray)){
        $rowDays[] = $cell;
      }
    }
    $inOrder = TRUE;
    $recentDay = array_shift($rowDays);
    foreach($rowDays as $day){
      if($day < $recentDay){
        $inOrder = FALSE;
      }
    }
    if(!$inOrder){
      $errorArray[] = $group;
    }
  }

  return $errorArray;
}

function check_number_groups_per_school($groupTable, $schoolTable){
  $errorArray = array();

  foreach($schoolTable as $school){
    $school_short_name = $school["short_name"];
    $school_long_name = $school["long_name"];
    $counter = array("2/3" => 0, "5" => 0);

    foreach($groupTable as $group){
      $arskurs = $group["g_arskurs"];
      if($group["skola"] == $school_short_name){
        $counter[$arskurs] += 1;
      }
    }
    $is_2 = $counter["2/3"];
    $should_2 = $school["grupper_ak2"];
    $is_5 = $counter["5"];
    $should_5 = $school["grupper_ak5"];


    if($is_2 != $should_2){
      $errorArray[$school_long_name]["2/3"] = array("is" => $is_2, "should" => $should_2);
    }
    if($is_5 != $should_5){
      $errorArray[$school_long_name]["5"] = array("is" => $is_5, "should" => $should_5);
    }
  }

  return $errorArray;
}

/* Will check if the column "checked" is actually checked */

function check_group_is_checked($groupTable, $teacherTable){
  $errorArray = array();
  foreach($groupTable as $group){

    $matchingTeacher = array();
    foreach($teacherTable as $teacher){
      if($teacher["id"] == $group["larar_id"]){
        $matchingTeacher = $teacher;
      }
    }

    if($group["checked"] != "yes"){
      $errorArray[] = array("grupp" => $group, "lärare" => $teacher);
    }
  }

  return $errorArray;
}

?>
