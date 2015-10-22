<?php
    /* This file is called by ajax requests, most often from user.php.
    It updates a group row or user row and returns the group id and the value
    seperated by "~" IF (and only if) the groupname was updated */

   /* PREAMBLE */
    $url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
    $filename = "include.php";
    //copy($url, $filename);
    include $filename;
    /* END OF PREAMBLE */
	inc("fnc, sql");

	//logg($_REQUEST);

	$name = $_REQUEST["name"]; // will contain a string with the group-id and the column value, splitted by "%", e.g. "35%klass"
	$value = $_REQUEST["value"]; // will contain the new value
  $userId = $_REQUEST["user"];

	$id_and_column = explode("%", $name);
	$id = $id_and_column[0];
	$column = $id_and_column[1];
  if(substr($column, -2) == "[]"){
    $column = substr($column, 0, -2);
  }

	$row = array($column => $value);
  sql_update_row($id, "grupper", $row, "id");

  $updateRow = array();
  $updateRow["timestamp"] = date("c"); // UNIX timestamp
  $updateRow["user"] = $userId;
  $updateRow["group_id"] = $id;
  $updateRow["table_column"] = $column;
  sql_insert_rows("updates", $updateRow);

  if($column == "klass"){
		echo($id . "~" . $value);
	}


?>
