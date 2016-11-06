<?php
// to enable debugging in plugin DBGp, add "?XDEBUG_SESSION_START=test" to your url
// to test this, use http://localhost/naturskolan_database/sandbox.php?XDEBUG_SESSION_START=test&type=naturskolan&trial=01
// and exchange the type-parameter for whatever you specified in the switch-case

require __DIR__ . '/vendor/autoload.php';
include("temp/test_arrays.php");

use \Fridde\Essentials;
use \Fridde\Utility as U;
use \Carbon\Carbon as C;
use \Fridde\SMS as SMS;
use \Fridde\NSDB_MailChimp as M;
use \Fridde\HTML as H;
use \Fridde\Calendar as Cal;

Essentials::getSettings();
Essentials::activateDebug();

$N = new \Fridde\Naturskolan();

$type = (isset($_REQUEST["type"])) ? strtolower($_REQUEST["type"]) : "" ;

switch ($type) {
    case "naturskolan":
    $N->delete("event", 6);
    $result = $N->get("events");
    Essentials::prePrint($result);
    break;

    case "calendar":
    $N->executeTask("rebuild_calendar");
    break;

    case "password":
    $password = "ekil_isgy";
    $school = $N->get("password/School", ["Password", $password]);
    var_export($school);
    break;

    case "carbon":
    $now = C::now();
    $execute_at = C::parse("2016-09-07T12:10:59+02:00");
    echo $now->toRfc850String() . "<br>";
    echo $execute_at->toRfc850String() . "<br>";
    var_dump($now->gt($execute_at));
    break;


    case "mailchimp":
    $M = new M();
    print_r2($M->getMembers());
    //echo json_encode($M->getCategoriesAndInterests(), JSON_PRETTY_PRINT);
    //$result = $M->get('lists/1ff7412fc8/members');
    // $result = $M->get('lists/1ff7412fc8/interest-categories/8548b28556/interests', $args);
    //$result = $M->get('lists/1ff7412fc8/interest-categories/', $args);
    //echo json_encode($result);
    break;

    case "nested":
    $H = new H();
    $H->addNestedList($H->body, $list, $atts = [["someClass"]]);
    $H->render();
    break;

    default:
    echo 'The type _' . $type . '_ was not found in the switch case.';

}
