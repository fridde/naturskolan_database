<?php
	/* use as localhost/naturskolan_database/sync_db.php?direction=export
		* or exchange "export" for "import"
	*/

	require __DIR__ . '/vendor/autoload.php';

	use \Fridde\Essentials;
	Essentials::getSettings();
	Essentials::activateDebug();

	use \Fridde\Dumper as D;

	$D = new D;

	if(!isset($_REQUEST["direction"])){
		echo "Error: direction parameter not set.";
		exit();
	}

	if($_REQUEST["direction"] == "export"){
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
