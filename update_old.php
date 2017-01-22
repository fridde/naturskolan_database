<?php
	/* This file will contain all the methods for ajax calls, database updates and calendar updates.
		AJAX requests should always return a json-object containing at least a "status"-member.
		råbg_lcni
	*/

	require __DIR__ . '/vendor/autoload.php';

	Essentials::getSettings();
	Essentials::activateDebug();

	use Fridde\{Utility as U, Naturskolan, Update};
	use Carbon\Carbon;

	$N = new Naturskolan();

	$updateType = "";
	U::extractRequest();
	$row = $values ?? [];
	$rowId = $values["id"] ?? false;
	$entity = $entity ?? false;
	$oldId = $oldId ?? false;

	$return = ["status" => "error"];
	$lastChange = new Carbon("now");
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
		$hash = Naturskolan::createHash();
		$expiration_date = Carbon::now()->addDays(90)->toIso8601String();
		$entity = "Password";
		$values = ["Hash" => $hash, "School" => $school, "ExpirationDate" => $expiration_date];
		$return["hash"] = $hash;
		break;

		case "deleteCookie":
		setcookie("Hash", "", time() - 24 * 3600);
		U::redirect("index.php?view=grupper");
		break;

		case "updateGroupName":
			$return["groupId"] = $rowId;
			$return["newName"] = $values["Name"];
		break;

		case "sort":

		break;

		case "deleteRow":
		break;

		default:
		if($oldId){
			$values = $values + $N->getStandardValues($table, $oldId);
			$return["oldId"] = $oldId;
		}
	}

	if($table){
		if($rowId && $updateType == "deleteRow"){
			$N->delete($table, ["id", $rowId]);
			$return["oldId"] = $rowId;
		}
		elseif($rowId){
			$N->update($table, $values, ["id", $rowId]);
		}
		else {
			$return["newId"] = $N->create($table, $values);
		}
		$return["LastChange"] = Carbon::now()->toIso8601String();
		$return["status"] = "success";
	}
	echo json_encode($return);
