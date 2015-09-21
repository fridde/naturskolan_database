<?php
	/* This file is a webhook for Mailchimp to sync with the local sql-database. It accepts POST-data according to
		Mailchimps API: apidocs.mailchimp.com/webhooks/  */

	/* PREAMBLE */
    $url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
    $filename = "include.php";
    copy($url, $filename);
    include $filename;
    /* END OF PREAMBLE */
	$ini_array = parse_ini_file("config.ini", TRUE);
	inc("fnc, sql, cal");

	$type = $_REQUEST["type"];

	if (in_array($type, array("subscribe", "unsubscribe", "profile", "upemail"))){

		$tableName = "larare";
		$fired_at = $_REQUEST["fired_at"];

		$data = $_REQUEST["data"];
		$headers = sql_get_headers($tableName);

		$user = array();
		$standardHeaders = array_keys($ini_array["headerTranslator"]);

		if($type == "upemail"){
			$old_email = $data["old_email"];
			$user = sql_select($tableName, array("email" => $old_email));
			$user = reset($user);
			$user["status"] = "archived";
			$insertArray = array("larar_id" => $user["id"], "alt_mc_id" => "mailchimp_id");
			sql_insert_rows("alias_login", $insertArray);
		}
		else {

			foreach ($headers as $header) {
				$v = "";

				if(in_array($header, $standardHeaders)){
					$v = $data["merges"][strtoupper($header)];
				}
				else {
					switch ($header) {
						case 'id':
						break;

						case 'mailchimp_id':
						$v = $data["id"];
						break;

						case 'status':
						if ($type == "subscribe" || $type == "profile") {
							$v = "subscribed";
						}
						if ($type == "unsubscribe") {
							$v = "archived";
						}
						break;

						case 'g_arskurs':
						$v = $data['merges']['GROUPINGS']['0']['groups'];
						break;

						case 'rektor':
						$v = $data['merges']['GROUPINGS']['3']['groups'];
						break;

						case 'verified':
						$v = $data['merges']['GROUPINGS']['2']['groups'];
						break;

						case 'updated':
						$v = $fired_at;
						break;

					}
				}
				$user[$header] = $v;
			}
		}

		$existingUser = sql_select($tableName, array("email" => $user["email"]));

		if (count($existingUser) == 0) {
			sql_insert_rows($tableName, $user);
		}
		else {
			$idToChange = $existingUser[0]["id"];
			sql_update_row($idToChange, $tableName, $user);

		}
		$mailchimp_id_to_update = $user["mailchimp_id"];

		if(in_array($type, array("unsubscribe", "upemail"))){
			update_calendar_db($mailchimp_id_to_update, TRUE);
		}
		else {
			update_calendar_db($mailchimp_id_to_update);
		}
		convert_database_to_ics("kalender");
	}
