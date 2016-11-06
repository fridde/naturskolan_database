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

	$direction = $_REQUEST["direction"] ?? "export";

	if($direction == "export"){
		$D->export();
		echo "Database exported!";
	}
	else if($direction == "import"){
		$D->import();
		echo "Database imported!";
	}
	else {
		echo $direction . ' is not a valid value for "direction"';
	}
