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
	//U::logg($_REQUEST);
	
	$SQL = new \Fridde\SQL();
	$N = new \Fridde\Naturskolan();
	
	$updateType = "";
	U::extractRequest();
	
	$return = ["status" => "error"];
	
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
		$N->create("session", ["id" => "", "Hash" => $hash, "School" => $school]);
		$return["hash"] = $hash;
		$return["status"] = "success";
		break;
		
		default:
		
	}
	
	echo json_encode($return);
	
