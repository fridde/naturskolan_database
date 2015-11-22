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

if(isset($_REQUEST["postdata"])){
  parse_str($_REQUEST["postdata"]);
} else {
  $variables = array("type", "fired_at", "data");
  foreach($variables as $var){
    if(isset($_REQUEST[$var])){
      $$var = $_REQUEST[$var];
    }
  }
}

$skolor = sql_select("skolor");
$skolor = col_to_index($skolor, "long_name");

if (in_array($type, array("subscribe", "unsubscribe", "profile", "upemail"))){

  $tableName = "larare";
  $headers = sql_get_headers($tableName);

  $user = array();
  $standardHeaders = array_keys($ini_array["headerTranslator"]);

  if($type == "upemail"){
    $old_email = $data["old_email"];
    $user = sql_select($tableName, array("email" => $old_email));
    $user = reset($user);
    $user["status"] = "archived";
    $old_larar_id = $user["id"];
    $newUser = sql_select($tableName, array("email" => $data["new_email"]));
    if(count($newUser) == 1){
      $newUser = reset($newUser);
      $new_larar_id = $newUser["id"];
    } else {
      /* We have a problem. The "email update" arrived BEFORE the new entry had arrived */
      $new_larar_id = sql_get_highest_id($tableName) + 1 ; // we expect the next entry
    }

    $matchande_grupper = sql_select("grupper", array("larar_id" => $old_larar_id));
    foreach($matchande_grupper as $grupp){
      sql_update_row($grupp["id"], "grupper", array("larar_id" => $new_larar_id));
    }

    //$insertArray = array("larar_id" => $user["id"], "alt_mc_id" => "mailchimp_id");
    //sql_insert_rows("alias_login", $insertArray);
  }
  else {
    /* will cycle through all headers of the sql-table "larare" */
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
            $v = "active";
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
    /* ensures that the school name is given as a 4 letter code */
    $user_skola = $user["skola"];
    $closest_match = find_most_similar($user_skola, array_keys($skolor));
    $user["skola"] = $skolor[$closest_match]["short_name"];

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
