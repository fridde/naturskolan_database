<?php
	/* use as localhost/naturskolan_database/sync_db.php?direction=export
		* or exchange "export" for "import"
	*/
	
	include("autoload.php");
	activateDebug();
	inc("vendor");
	$tables_exclude_data = ["settings"];
	
	use \Fridde\Dumper as D;
	
	$D = new D;
	
	if(!isset($_REQUEST["direction"])){
		echo "Error: direction parameter not set.";
		exit();
	}
	
	if($_REQUEST["direction"] == "export"){
		foreach($tables_exclude_data as $t){
			$D->tables[$t] = $D::DROP | $D::CREATE;
		}
		$D->export();
		echo "Database exported!";
	}
	else if($_REQUEST["direction"] == "import"){
		$D->import();
		echo "Database imported!";
	}
	else {
		echo $_REQUEST["direction"] . " is not a valid value for \"direction\"";
	}
