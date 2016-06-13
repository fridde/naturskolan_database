<?php
	/* This file will contain all the methods for ajax calls, database updates and calendar updates
		AJAX requests should always return a json-object containing at least a "status"-member
		råbg_lcni
	*/
	
	
	include("autoload.php");
	activateDebug();
	inc("vendor");
	//updateAllFromRepo();
	use \Fridde\Utility as U;
	U::logg($_REQUEST);
	
	$SQL = new \Fridde\SQL();
	$N = new \Fridde\Naturskolan();
	
	$updateType = "";
	U::extractRequest();
	//U::logg($_REQUEST); //remove in production
	$return = ["status" => "error"];
	$lastChange = date("c");
	$tables_with_last_change = ["groups", "users"];
	
	
	switch($updateType){
		case "password":
		$school = $N->get("password/School", ["Password", $password]);
		if($school){
			$return["school"] = $school;
			$return["status"] = "success";
		}
		break;
		
		case "setCookie":
		$hash = $N->createHash();
		$expiration_date = date("c", strtotime('+ 90 days'));
		$N->create("session", ["Hash" => $hash, "School" => $school, "ExpirationDate" => $expiration_date]);
		$return["hash"] = $hash;
		$return["status"] = "success";
		break;
		
		case "deleteCookie":
		setcookie("Hash", "", time() - 24 * 3600);
		U::redirect("index.php?view=grupper");
		break;
		
		case "sort":
		
		break;
		
		case "group":
		$N->update("group", [$column => $value, "LastChange" => $lastChange], ["id", $groupId]);
		$return["status"] = "success";
		$return["LastChange"] = $lastChange;
		break;
		
		case "row":
		$new_prefix = "new_";
		if(strpos($rowId, $new_prefix) === 0){
			$old_id = substr($rowId, strlen($new_prefix));
			$standard_values = $N->getStandardValues($table, $old_id);
			$newId = $N->create($table, [$column => $value] + $standard_values);
			$return["newId"] = $newId;
			$return["oldId"] = $rowId;
		}
		else {
			$update_values = [$column => $value];
			if(in_array($table, $tables_with_last_change)){
				$update_values["LastChange"] = $lastChange;
			}
			$N->update($table, $update_values, ["id", $rowId]);
		}
		$return["status"] = "success";
		$return["LastChange"] = $lastChange;
		break;
		
	}
	echo json_encode($return);
	
