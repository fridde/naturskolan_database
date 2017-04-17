<?php
namespace Fridde;

use Carbon\Carbon;

use Fridde\Calendar;
use Fridde\Utility as U;
use Fridde\Update;
use Fridde\Entities\Group;

/**
* This class deals with tasks that are executed on a regular basis. The interval
* of each task is defined in cronjobs.intervals in the settings and the actual
* execution is called from Fridde\Controller\CronController.
* @package naturskolan_database
*/

class Task
{
    /** @var Fridde\Naturskolan shortcut for the Naturskolan object in the global container */
    private $N;
    /** @var string Type of task to perform. Corresponds to index in $task_to_function_map */
    private $type;
    /** @var array Contains elements of the mail to be sent to the admin */
    private $admin_mail;
    /** @var boolean[] Defines whether a certain task is off or on. Defined in SystemStatus
    * in the entry for the id *cron_tasks.activation* */
    private $task_activation;
    /** @var string[] Matches task names as they are used outside this class to
    methods used inside this class */
    private $task_to_function_map = [
        "calendar_rebuild" => "rebuildCalendar",
        "backup" => "backupDatabase",
        "visit_confirmation_message" => "sendVisitConfirmationMessage",
        "admin_summary" => "sendAdminSummaryMail",
        "changed_group_leader_mail" => "sendChangedGroupLeaderMail",
        "new_user_mail" => "sendNewUserMail",
        "profile_update_reminder" => "sendUpdateProfileReminder",
        "table_cleanup" => "cleanSQLDatabase"
    ];

    /**
     * The constructor.
     *
     * @param string $task_type Type of task to perform. Corresponds to index in $task_to_function_map
     */
    public function __construct ($task_type = null)
    {
        $this->type = $task_type;
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
    }

    /**
    * The main method that executes a certain task by calling its corresponding method
    * defined in $task_to_function_map, but only if its corresponding entry in
    * $task_activation is not set to a falsy value
    *
    * @return void
    */
    public function execute()
    {
        if(empty($this->task_activation)){
            $this->task_activation = $this->N->getCronTasks();
        }
        $is_active = boolval($this->task_activation[$this->type] ?? true);
        if($is_active){
            $function_name = $this->task_to_function_map[$this->type];
            $this->$function_name();
        }
    }

    /**
    * Checks if the ical-calendar has been changed since its last build or if it's older
    * than the time defined by cron_jobs.max_calendar_age in SETTINGS and builds a new
    * calendar file from scratch if necessary.
    *
    * @return void
    */
    private function rebuildCalendar()
    {
        $last_rebuild = $this->N->getLastRebuild();
        $max_age = SETTINGS["cron_jobs"]["max_calendar_age"];
        $too_old = U::subTime($max_age)->gt($last_rebuild);

        $is_dirty = $this->N->calendarIsDirty();
        if($is_dirty || $too_old){
            $cal = new Calendar();
            $cal->save();
            $this->N->setCalendarToClean();
        }
    }

    /**
    * Creates a new backup of the database and cleans older versions that are not
    * protected.
    *
    * @return void
    */
    private function backupDatabase()
    {
        $DBM = new DatabaseMaintainer();
        $DBM->backup();
        $DBM->cleanOldBackups();
    }

    /**
    * Gets the time value for either immunity time or annoyance interval from the settings
    * and subtracts this value from today.
    *
    * @param  string $type Defines whether the method should use SETTINGS.user_message.immunity_time
    *                      or SETTINGS.user_message.annoyance_interval
    *
    * @return Carbon\Carbon The date calculated by the subtraction.
    */
    private function getStartDate($type)
    {
        $translator = ["immunity" => "immunity_time", "annoyance" => "annoyance_interval"];
        $setting = $translator[strtolower($type)];
        $t = $this->N->getSettings("user_message", $setting);
        $days = U::convertDuration($t, "d");
        return Carbon::today()->subDays($days);
    }
    /**
    * Sends an email or sms to a user leading a group that will soon visit. The message
    * urges the user to follow an included link that confirms the visit. The method also logs the message.
    *
    * @return void
    */
    private function sendVisitConfirmationMessage()
    {
        $annoyance_start = $this->getStartDate("annoyance");
        $search_props["Status"] = ["sent", "received"];
        $search_props["Subject"] = "confirmation";

        $time = $this->N->getSettings("user_message", "visit_confirmation_time");
        $days = U::convertDuration($time, "d");

        $unconfirmed_visits = $this->getRepo("Visit")->findUnconfirmedVisitsUntil($days);
        $unconfirmed_visits = array_values($unconfirmed_visits->toArray());

        foreach($unconfirmed_visits as $index => $v){
            $user = $v->getGroup()->getUser();
            $last_msg = $user->getlastMessage($search_props);

            if(empty($last_msg) || !$user->hasMobil()){
                $msg_carrier = "mail";
                $response = $this->sendVisitConfirmationMail($v);
            } elseif(!$last_msg->wasSentAfter($annoyance_start)){
                $msg_carrier = "sms";
                $response = $this->sendVisitConfirmationSMS($v);
            }

            $this->logMessage($response, $msg_carrier, $user);
        }
    }


/**
 * Logs a sent mail or sms to the database for later retrieval.
 *
 * @param  Guzzle\Http\Message\Response $response    The response object returned by the request
 * @param  string $msg_carrier How was the message sent? "sms" or "mail" are currently implemented
 * @param  Fridde\Entities\User $user        The user (as object) the message was sent to
 * @return array The result of the Update-operation as defined in Fridde\Update
 */
    private function logMessage($response, $msg_carrier, $user)
    {
        $body = $response->getBody()->getContents();
        $result = json_decode($body, true);
        if(is_null($result)){
            $body = trim($body, "\x22"); // removes quotes (=")
            $result = json_decode($body, true);
        }
        $success = $msg_carrier == "sms" && empty($result["result"]["fails"]);
        $success = $success || $msg_carrier == "mail" && $result["success"];

        if($success){
            $msg_props["User"] = $user; // TODO: check if this works. should this really be an object?
            $msg_props["Subject"] = "confirmation";
            $msg_props["Carrier"] = $msg_carrier;
            $msg_props["Status"] = "sent";
            if($msg_carrier == "sms"){
                $return = $result["result"]["success"][0];
                $msg_props["ExtId"] = $return["id"];
                $msg_props["Status"] = $return["status"];
                $msg_props["Content"] = ["visit_id" => $v->getId()];
            }
            $request["updateType"] = "createNewEntity";
            $request["entity_class"] = "Message";
            $request["properties"] = $msg_props;
            $update_result = Update::create($request);
        } else {
            //TODO: log this failed trial to error table or equivalent
        }

        return $update_result;
    }

/**
 * Will send an **email** about a certain visit to the leader of the group that visits.
 * Notice that no check about the validity of the visit is performed here.
 *
 * @param  Fridde\Entities\Visit $visit The visit that the mail is about
 * @return Guzzle\Http\Message\Response The response object returned by the request
 */
    private function sendVisitConfirmationMail($visit)
    {
        $v = $visit;
        $post["receiver"] = $v->getGroup()->getUser()->getMail();
        $data["user_fname"] = $v->getGroup()->getUser()->getFirstName();
        $data["confirmation_url"] = $this->N->getConfirmationUrl($v->getId());
        $school_id = $v->getGroup()->getSchoolId();
        $data["school_url"] = $this->generateUrl("school", ["school" => $school_id]);
        $info["date"] = $v->getDateString();
        $info["date_string"] = $v->getDate()->formatLocalized("%e %B");
        $info["group_name"] = $v->getGroup()->getName();
        $info["topic_name"] = $v->getTopic()->getLongestName();
        $info["topic_url"] = $v->getTopic()->getUrl();
        $info["food_type"] = $v->getTopic()->getFood();
        $info["food_instructions"] = $v->getTopic()->getFoodInstructions(true);

        $data["visit_info"] = $info;
        $post["data"] = $data;
        $url = $this->N->generateUrl("mail", ["type" => "confirm_visit"]);
        return $this->N->sendRequest($url, $post);
    }

    /**
     * Will send a **sms message** about a certain visit to the leader of the group that visits.
     * Notice that no check about the validity of the visit is performed here.
     *
     * @param  Fridde\Entities\Visit $visit The visit that the sms message is about
     * @return Guzzle\Http\Message\Response The response object returned by the request
     */
    private function sendVisitConfirmationSMS($visit)
    {
        $post["receiver"] = $visit->getGroup()->getUser()->getMobil();
        $rep["date_string"] = $visit->getDate()->formatLocalized("%e %B");
        $rep["fname"] = $visit->getGroup()->getUser()->getFirstName();
        $msg = $this->N->getReplacedText(["sms", "confirm_visit"], $rep);
        $post["message"] = $msg;
        $url = $this->N->generateUrl("sms", ["type" => "confirm_visit"]);
        return $this->N->sendRequest($url, $post);
    }

/**
 * Calls compileAdminSummaryMail() and sends the content to the current admin mail adress
 *
 * @return Guzzle\Http\Message\Response The response object returned by the request
 */
    private function sendAdminSummaryMail()
    {

        $this->compileAdminSummaryMail();

        if(empty($this->admin_mail)){
            return true;
        }
        $data["data"]["errors"] = $this->admin_mail;
        $data["data"]["labels"] = $this->N->getText(["admin_summary"]);
        $url = $this->N->createMailUrl("admin_summary");
        return $this->N->sendRequest($url, $data);
    }

    /**
    * Performs a variety of checks of the whole system (visits, missing or bad information, etc)
    * and saves any anomalities in the parameter *admin_mail* using addToAdminMail().
    *
    * @return void
    */
    private function compileAdminSummaryMail()
    {
        // #######################
        // ### visit not confirmed
        // #######################

        $no_conf_interval = $this->N->getSettings("admin_summary", "no_confirmation_warning");
        $days = U::convertDuration($no_conf_interval, "d");
        $unconfirmed_visits = $this->getRepo("Visit")->findUnconfirmedVisitsUntil($days);
        $this->addToAdminMail("visit_not_confirmed", $unconfirmed_visits->toArray());

        // #######################
        // ### food, info or number of students changed
        // #######################

        $deadline = U::addDuration($this->N->getSettings("admin_summary", "important_info_changed"));
        $a = [];

        $crit = [["EntityClass", "Group"], ["in", "Property", ["Food", "NumberStudents", "Info"]]];
        $group_changes = $this->getRepo("Change")->findNewChanges($crit);
        foreach($group_changes as $change){
            $g = $this->getRepo("Group")->find($change->getEntityId());
            $next_visit = $g->getNextVisit();
            if(!empty($next_visit) && $next_visit->isBefore($deadline)){
                $att = $change->getProperty();
                $method = "get" . $att;
                $old_value = $change->getOldValue();
                $new_value = $g->$method();
                if($old_value !== $new_value){
                    $return = ["from" => $old_value, "to" => $new_value];
                    $return["group"] = $g;
                    $a[$att][] = $return;
                }
            }
            $this->processChange($change);
        }
        $this->addToAdminMail("food_changed", $a["Food"] ?? []);
        $this->addToAdminMail("nr_students_changed", $a["NumberStudents"] ?? []);
        $this->addToAdminMail("info_changed", $a["Info"] ?? []);


        // #######################
        // ### user profile incomplete
        //#######################
        $imm_date = $this->getStartDate("immunity");
        $incomplete_users = $this->getRepo("User")->findIncompleteUsers($imm_date);
        $this->addToAdminMail("user_profile_incomplete", $incomplete_users);

        // #######################
        // ### bad mobile number
        //#######################
        $users_with_bad_mob = $this->getRepo("User")->findUsersWithBadMobil($imm_date);
        $this->addToAdminMail("bad_mobil", $users_with_bad_mob);

        // #######################
        // ###  last booked visit is coming soon!
        //#######################
        $days_left_interval = $this->N->getSettings("admin_summary", "soon_last_visit");
        $last_visit_deadline = U::addDuration($days_left_interval);
        $last_visit = $this->getRepo("Visit")->findLastVisit();
        if(empty($last_visit) || $last_visit->getDate()->lte($last_visit_deadline)){
            $this->addToAdminMail("soon_last_visit", $last_visit);
        }

        // #######################
        // ### wrong amount of groups
        // #######################
        $schools = $this->getRepo("School")->findAll();
        $bad_schools = [];

        array_walk($schools, function($s){
            foreach(Group::GRADE_LABELS as $column_val => $label){
                $active = $s->getNrActiveGroupsByGrade($column_val);
                $expected = $s->getGroupNumber($column_val);
                if($expected !== $active){
                    $a = ["expected" => $expected, "active" => $active];
                    $bad_schools[$s->getId()][$column_val][] = $a;
                }
            }
        });
        $this->addToAdminMail("wrong_group_count", $bad_schools);

        // #######################
        // ### wrong group leader
        // #######################
        $groups = $this->getRepo("Group")->findActiveGroups();
        $bad_groups = [];
        foreach($groups as $g){
            $id = $g->getId();
            $u = $g->getUser();
            $reasons = [];
            if(empty($u)){
                $reasons["nonexistent"] = true;
            } else {
                $reasons["inactive"] = ! $u->isActive();
                $reasons["not_teacher"] = ! $u->isRole("teacher");
                $reasons["wrong_school"] = $u->getSchool()->getId() !== $g->getSchool()->getId();

            }
            $reasons = array_filter($reasons);
            if(!empty($reasons)){
                $bad_groups[$id]["group"] = $g;
                $bad_groups[$id]["reasons"] = array_keys($reasons);
            }
        }
        $this->addToAdminMail("wrong_group_leader", $bad_groups);

        // #######################
        // ### visit with inactive group
        // #######################
        $visits = $this->getRepo("Visit")->findFutureVisits();
        $bad_visits = array_filter($visits, function($v){
            return $v->hasGroup() && !$v->getGroup()->isActive(); //empty groups are okay
        });
        $this->addToAdminMail("inactive_group_visit", $bad_visits);


        // #######################
        // ### too many students in class
        // #######################
        $large_groups = array_filter($groups, function($g){
            return $g->getNumberStudents() > 33;
        });
        $this->addToAdminMail("too_many_students", $large_groups);

        /*
        ### recheck bus orders
        ### recheck food orders
        */
    }

/**
 * [addToAdminMail description]
 * @param string $error_type The type of error
 * @param array|Entity $entities   The entities that contain issues with this error or one single entity.
 */
    private function addToAdminMail($error_type, $entities = [])
    {
        if(empty($entities)){
            return ;
        }

        $entities = (array) $entities;
        foreach($entities as $key => $entity){
            $row = "";

            switch($error_type){
                case "visit_not_confirmed":
                $visit = $entity;
                $g = $visit->getGroup();
                $u = $g->getUser();

                $row .= $visit->getDate()->toDateString() . ": ";
                $row .= $visit->getTopic()->getShortName() . " med ";
                $row .= ($g->getName() ?? '???') . " från ";
                $row .= $g->getSchool()->getName() . ". Lärare: ";
                $row .= $u->getFullName() . ", ";
                $row .= $u->getMobil() . ", " . $u->getMail();
                break;
                // ###########################################
                case "food_changed":
                case "student_nr_changed":
                case "info_changed":

                $g = $entity["group"];
                $row .= $g->getGradeLabel() . ", ";
                $row .= "Grupp " . ($g->getName() ?? '???') . " från " . $g->getSchool()->getName();
                $row .= ", möter oss härnast " ;
                $row .= $g->getNextVisit()->getDate()->toDateString() . " : ";
                $labels = ["food_changed" => "Matpreferenserna", "student_nr_changed" => "Antal elever"];
                $labels["info_changed"] = "Information från läraren";
                $row .= $labels[$error_type];
                $row .= ' ändrades från "' . $entity["from"] . '" till "' . $entity["to"] . '"';
                break;
                // ###########################################
                case "soon_last_visit":
                $last_visit = $entity;
                $row .= "Snart är sista planerade mötet med eleverna. Börja planera nästa termin!";
                $row .= " Sista möte: ";
                $row .= empty($last_visit) ? "Inga vidare möten." : $last_visit->getDate()->toDateString();
                break;
                // ###########################################
                case "user_profile_incomplete":
                $u = $entity;
                $row .= $u->getCompleteName() . ", ";
                $row .= $u->getSchool()->getName() . ": ";
                $row .= "Mobil: " . ($u->hasMobil() ? $u->getMobil() : '???') . ", ";
                $row .= "Mejl: " . ($u->hasMail() ? $u->getMail() : '???') . ", ";
                break;
                // ###########################################
                case "bad_mobil":
                $u = $entity;
                $row .= $u->getCompleteName() . ", ";
                $row .= $u->getSchool()->getName() . ": ";
                $row .= "Mobil: " . $u->hasMobil();
                break;
                // ###########################################
                case "wrong_group_leader":
                $reasons = $entity["reasons"];
                $g = $entity["group"];
                $u = $g->getUser();

                $row .= $g->getGradeLabel() . ": ";
                $row .= $g->getName() . " från " . $g->getSchool()->getName();
                $row .= ". Skäl: ";
                $reason_texts = [];
                foreach($reasons as $reason){
                    $reason_texts[] = $this->N->getText(["admin_summary", $reason]);
                }
                $row .= implode(" ", $reason_texts);
                break;
                // ###########################################
                case "wrong_group_count":
                // example ["råbg" => ["2" => ["expected" => 1, "active" => 2]]]
                $school_id = $key;
                $school = $this->getRepo("School")->find($school_id);

                $row .= $school->getName() . " har fel antal grupper. ";
                foreach($entity as $grade => $exp_act){
                    $row .= "I ". Group::GRADE_LABELS[$grade] . " finns det ";
                    $row .= $exp_act["active"] . " grupper, men det borde vara ";
                    $row .= $exp_act["expected"] . ". ";
                }
                break;
                // ###########################################
                case "inactive_group_visit":
                $v = $entity;
                $row .= "Ogiltigt besök på ";
                $row .= $v->getDate()->toDateString() . ": ";
                $row .= $v->getGroup()->getName() . " från ";
                $row .= $v->getGroup()->getSchool()->getName();
                $row .= ", " . $v->getGroup()->getGradeLabel();
                break;
                // ###########################################
                case "too_many_students":
                $g = $entity;
                $row .= $g->getName() . ", " . $g->getGradeLabel() . ", ";
                $row .= "från " . $g->getSchool()->getName() ;
                $row .= " har " . $g->getNumberStudents() . " elever.";
                break;
                // ###########################################
                case "bus_schedule_outdated":
                break;
                // ###########################################
                case "food_order_outdated":
                break;

            }
            $this->admin_mail = $this->admin_mail ?? [];
            $this->admin_mail[$error_type][] = $row;
        }
    }

/**
 * Informs group leaders via email that they have gained or lost one or more groups.
 *
 * @return Guzzle\Http\Message\Response The response object returned by the request
 */
    private function sendChangedGroupLeaderMail()
    {
        $crit = [["EntityClass", "Group"], ["Property", "User"]];
        $user_changes = $this->getRepo("Change")->findNewChanges($crit);
        $user_array = [];
        foreach($user_changes as $change){
            $group = $this->getRepo("Group")->find($change->getEntityId());
            $new_value = $group->getUserId();
            $old_value = $change->getOldValue();
            if($new_value != $old_value){
                if(!empty($old_value)){
                    $user_array[$old_value]["removed"][] = $group->getId();
                }
                $user_array[$new_value]["new"][] = $group->getId();
            }
            $this->processChange($change);
        }
        // ensure that new and removed exist
        array_walk($user_array, function(&$u){
            $u += array_fill_keys(["removed", "new"], []);
        });
        $url = $this->N->generateUrl("mail", ["type" => "changed_groups_for_user"]);
        // for debugging:
        //$url = 'http://379bc1c8.ngrok.io/naturskolan_database/mail/changed_groups_for_user';
        foreach($user_array as $user_id => $group_changes){

            $user = $this->getRepo("User")->find($user_id);
            if($user->hasMail()){
                $post["receiver"] = $user->getMail();
                extract($group_changes);
                $all = $user->getGroupIdArray();
                $rest = array_diff($all, $new, $removed);
                $post["data"]["groups"] = compact("new", "removed", "rest");
                $post["data"]["user_fname"] = $user->getFirstName();
                $school_url = $this->N->generateUrl("school", ["school" =>$user->getSchoolId()]);
                $post["data"]["school_url"] = $school_url;
                return $this->N->sendRequest($url, $post);
            } else {
                // log error and continue
            }

        }
    }

    private function sendNewUserMail()
    {
        // TODO: check also for unprocessed new group assignments in "changes"
    }

    private function changedVisitDateMail()
    {
        // TODO: implement this function

        /*
        any changes where (Table == "visits" && Column == "Date")
        visit = select_where(visit[Date] == value)
        if(visit[Date] != value)
        send_mail(...
        */
    }


/**
 * Checks if sufficient time since last reminder has gone and sends an email (or sms
 * if no mail-adress available) in that case.
 *
 * @return void
 */
    private function sendUpdateProfileReminder()
    {
        $annoyance_start = $this->getStartDate("annoyance");

        $imm_start = $this->getStartDate("immunity");
        $incomplete_users = $this->getRepo("User")->findIncompleteUsers($imm_start);

        // status carrier type
        $msg_props["Status"] = ["sent", "received"];
        $msg_props["Subject"] = "profile_update";
        $incomplete_users = array_filter($incomplete_users, function($u) use ($annoyance_start, $msg_props){
            return !$u->lastMessageWasAfter($annoyance_start, $msg_props);
        });

        $type = ["type" => 'update_profile_reminder'];
        $mail_url =  $this->N->generateUrl("mail", $type);
        $sms_url = $this->N->generateUrl("sms", $type);

        foreach($incomplete_users as $user){
            $post = [];
            if(!$user->hasMail() && $user->hasMobil()){
                $post["receiver"] = $user->getMobil();
                $long_url = $this->N->getLoginUrl($user);
                $short_url = $this->N->shortenUrl($long_url);
                $rep["login_url"] = explode('//', $short_url)[1];
                $msg = $this->N->getReplacedText(["sms", "update_profile"], $rep);
                $post["message"] = $msg;
                $response = $this->N->sendRequest($sms_url, $post);

            } elseif($user->hasMail()) {
                $post["receiver"] = $user->getMail();
                $response = $this->N->sendRequest($mail_url, $post);
            } else {
                $e_text = "User with id <" . $user->getId() . "> has no email or";
                $e_text .= " mobile phone number. Check up on that immediately.";
                $GLOBALS["CONTAINER"]->get('Logger')->warning($e_text);
            }
        }
    }

/**
 * Will clean unused or very old records from the database
 *
 * @return [type] [description]
 */
    private function cleanSQLDatabase()
    {
        // TODO: implement this function
    }

    /**
     * Wrapper for Naturskolan->ORM->getRepository()
     *
     * @param  string $repo The (non-qualified-) name of the class of entities
     * @return Doctrine\ORM\EntityRepository The repository
     */
    private function getRepo($repo)
    {
        return $this->N->ORM->getRepository($repo);
    }

/**
 * A quick function to mark a certain Change as processed
 *
 * @param  Fridde\Entities\Change $change The Change object
 * @return mixed The result of the Update
 */
    private function processChange($change)
    {
        $rq = ["update_type" => "updateProperty"];
        $rq["entity_class"] = "Change";
        $rq["entity_id"] = $change->getId();
        $rq["property"] = "Processed";
        $rq["value"] = Carbon::now()->toIso8601String();
        return Update::create($rq);
    }

}
