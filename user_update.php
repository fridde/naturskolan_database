<?php
	/* PREAMBLE */
    $url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
    $filename = "include.php";
    //copy($url, $filename);
    include $filename;
    /* END OF PREAMBLE */
	inc("fnc, sql");
	
	logg($_REQUEST);
	
	$name = $_REQUEST["name"];
	$value = $_REQUEST["value"];
	
	$id_and_column = explode("%", $name);
	$id = $id_and_column[0];
	$column = $id_and_column[1];
	
	$row = array($column => $value);
	
	sql_update_row($id, "grupper", $row, "id", TRUE);
	
?>
