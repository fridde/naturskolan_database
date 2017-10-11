<?php

namespace Fridde;

use Carbon\Carbon;
use Fridde\Entities\Change;
use Fridde\Entities\User;
use Fridde\Entities\UserRepository;
use Fridde\Entities\Visit;
use Fridde\Messenger\Mail;
use Fridde\Messenger\SMS;
use Fridde\Utility as U;


/**
 * This class deals with tasks that are executed on a regular basis. The interval
 * of each task is defined in cronjobs.intervals in the settings and the actual
 * execution is called from Fridde\Controller\CronController.
 * @package naturskolan_database
 */
class Task
{
    /** @var \Fridde\Naturskolan shortcut for the Naturskolan object in the global container */
    private $N;
    /** @var string Type of task to perform. Corresponds to index in TASK_TO_METHOD_MAP */
    private $type;
    /** @var string[] Matches task names as they are used outside this class to
     * methods used inside this class */
    const TASK_TO_METHOD_MAP = [
        "calendar_rebuild" => "rebuildCalendar",
        "backup" => "backupDatabase",
        "visit_confirmation_message" => "sendVisitConfirmationMessage",
        "admin_summary" => "sendAdminSummaryMail",
        "changed_group_leader_mail" => "sendChangedGroupLeaderMail",
        "new_user_mail" => "sendNewUserMail",
        "update_profile_reminder" => "sendUpdateProfileReminder",
        "table_cleanup" => "cleanSQLDatabase",
    ];

    /**
     * The constructor.
     *
     * @param string $task_type Type of task to perform. Corresponds to index in $task_to_method_map
     */
    public function __construct(string $task_type = null)
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->type = $task_type;
    }

    /**
     * The main method that executes a certain task by calling its corresponding method
     * defined in TASK_TO_METHOD_MAP, but only if its corresponding entry in
     * $task_activation is not set to a falsy value
     *
     * @param boolean $ignore_task_activation
     */
    public function execute()
    {
        $function_name = self::TASK_TO_METHOD_MAP[$this->type];
        $result = $this->$function_name();
        $this->N->log("Executed task: ".$this->type, 'Task->execute()');

        return $result;

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
        $max_age = SETTINGS["cronjobs"]["max_calendar_age"];
        $too_old = U::subDuration($max_age)->gt($last_rebuild);

        $is_dirty = $this->N->calendarIsDirty();
        if ($is_dirty || $too_old) {
            $cal = new Calendar();
            $cal->save();
            $this->N->setStatus('calendar.last_rebuild', Carbon::now()->toIso8601String());
            $this->N->log("Actually recalculated calendar", 'Task->rebuildCalendar()');
        } else {
            $this->N->log("No calendar recalculation needed", 'Task->rebuildCalendar()');
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
     * @return \Carbon\Carbon The date calculated by the subtraction.
     */
    public static function getStartDate(string $type)
    {
        $translator = ["immunity" => "immunity_time", "annoyance" => "annoyance_interval"];
        $setting = $translator[strtolower($type)];
        $t = SETTINGS["user_message"][$setting];
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
        $annoyance_start = self::getStartDate("annoyance");
        $search_props["Status"] = ["sent", "received"];
        $search_props["Subject"] = "confirmation";

        $time = Naturskolan::getSetting("user_message", "visit_confirmation_time");
        $deadline = U::addDuration($time);

        /* @var \Fridde\Entities\Visit[] $unconfirmed_visits */
        $unconfirmed_visits = $this->N->getRepo("Visit")->findUnconfirmedVisitsUntil($deadline);

        foreach ($unconfirmed_visits as $v) {
            if ($v->hasGroup()) {
                $user = $v->getGroup()->getUser();
                $last_msg = $user->getlastMessage($search_props);

                if (empty($last_msg) || !$user->hasMobil()) {
                    $msg_carrier = "mail";
                    $response = $this->sendVisitConfirmationMail($v);
                } elseif (!$last_msg->wasSentAfter($annoyance_start)) {
                    $msg_carrier = "sms";
                    $response = $this->sendVisitConfirmationSMS($v);
                } else {
                    $e = 'User ' . $user->getId() . ' has neither mail nor mobile number ';
                    $e .= 'and couldn\'t be contacted to confirm visit. Check this!';
                    $this->N->log($e, 'Task->sendVisitConfirmationMessage()');
                }

                $this->logMessage($response, $msg_carrier, $user);
            }
        }
    }


    /**
     * Logs a sent mail or sms to the database for later retrieval.
     *
     * @param  array $response The response array returned by the Messenger
     * @param  string $msg_carrier How was the message sent? "sms" or "mail" are currently implemented
     * @param  \Fridde\Entities\User $user The user (as object) the message was sent to
     * @return \Fridde\Update|null The result of the Update-operation as defined in Fridde\Update
     */
    private function logMessage($response, $msg_carrier, $user)
    {
        $success = $msg_carrier === "sms" && empty($response["result"]["fails"]);
        $success = $success || $msg_carrier === "mail" && !empty($response["success"]);

        if ($success) {
            $msg_props["User"] = $user->getId(); // TODO: check if this works. should this really be an object?
            $msg_props["Subject"] = "confirmation";
            $msg_props["Carrier"] = $msg_carrier;
            $msg_props["Status"] = "sent";
            if ($msg_carrier == "sms") {
                $return = $response["result"]["success"][0];
                $msg_props["ExtId"] = $return["id"];
                $msg_props["Status"] = $return["status"];
                $msg_props["Content"] = ''; // TODO: Retrieve the message!
            }

            return (new Update)->createNewEntity("Message", $msg_props);
        } else {
            //TODO: log this failed trial to error table or equivalent
        }

        return null;
    }

    /**
     * Will send an **email** about a certain visit to the leader of the group that visits.
     * Notice that no check about the validity of the visit is performed here.
     *
     * @param  \Fridde\Entities\Visit $visit The visit that the mail is about
     * @return ResponseInterface The response object returned by the request
     */
    private function sendVisitConfirmationMail(Visit $visit)
    {
        $v = $visit;
        $params = ['purpose' => 'confirm_visit'];
        $params["receiver"] = $v->getGroup()->getUser()->getMail();
        $data["user_fname"] = $v->getGroup()->getUser()->getFirstName();
        $data["confirmation_url"] = $this->N->createConfirmationUrl($v->getId());
        $school_id = $v->getGroup()->getSchoolId();
        $data["school_url"] = $this->N->generateUrl("school", ["school" => $school_id]);
        $info["date"] = $v->getDateString();
        $info["date_string"] = $v->getDate()->formatLocalized("%e %B");
        $info["group_name"] = $v->getGroup()->getName();
        $info["topic_name"] = $v->getTopic()->getLongestName();
        $info["topic_url"] = $v->getTopic()->getUrl();
        $info["food_type"] = $v->getTopic()->getFood();
        $info["food_instructions"] = $v->getTopic()->getFoodInstructions(true);

        $data["visit_info"] = $info;
        $params["data"] = $data;

        $mail = new Mail($params);

        return $mail->buildAndSend();

    }

    /**
     * Will send a **sms message** about a certain visit to the leader of the group that visits.
     * Notice that no check about the validity of the visit is performed here.
     *
     * @param  \Fridde\Entities\Visit $visit The visit that the sms message is about
     * @return array The response object returned by the request
     */
    private function sendVisitConfirmationSMS(Visit $visit)
    {
        $params = ['purpose' => 'confirm_visit'];
        $params["receiver"] = $visit->getGroup()->getUser()->getMobil();
        $rep["date_string"] = $visit->getDate()->formatLocalized("%e %B");
        $rep["fname"] = $visit->getGroup()->getUser()->getFirstName();
        $msg = $this->N->getReplacedText(["sms", "confirm_visit"], $rep);
        $params["message"] = $msg;

        $sms = new SMS($params);

        return $sms->buildAndSend();
    }

    /**
     * Calls compileAdminSummaryMail() and sends the content to the current admin mail address
     *
     * @return ResponseInterface The response object returned by the request
     */
    private function sendAdminSummaryMail()
    {
        $admin_summary = new AdminSummary();

        return $admin_summary->send();
    }

    /**
     * Informs group leaders via email that they have gained or lost one or more groups.
     *
     * @return ResponseInterface The response object returned by the request
     */
    private function sendChangedGroupLeaderMail()
    {
        $crit = [["EntityClass", "Group"], ["Property", "User"]];
        $user_changes = $this->N->getRepo("Change")->findNewChanges($crit);
        $user_array = [];
        foreach ($user_changes as $change) {
            /* @var Change $change */
            /** @var Entities\Group $group */
            $group = $this->N->getRepo("Group")->find($change->getEntityId());
            $new_value = $group->getUserId();
            $old_value = $change->getOldValue();
            if ($new_value != $old_value) {
                if (!empty($old_value)) {
                    $user_array[$old_value]["removed"][] = $group->getId();
                }
                $user_array[$new_value]["new"][] = $group->getId();
            }
            self::processChange($change);
        }
        // ensure that "new" and "removed" exist
        array_walk(
            $user_array,
            function (&$u) {
                $u += array_fill_keys(["removed", "new"], []);
            }
        );
        $url = $this->N->generateUrl("mail", ["type" => "changed_groups_for_user"]);
        /** @var Entities\User $user */
        foreach ($user_array as $user_id => $group_changes) {
            $user = $this->N->getRepo("User")->find($user_id);
            $params = ['purpose' => 'changed_groups_for_user'];
            if ($user->hasMail()) {
                $params["receiver"] = $user->getMail();
                list($new, $removed) = [$group_changes["new"], $group_changes["removed"]];
                $all = $user->getGroupIdArray();
                $rest = array_diff($all, $new, $removed);
                $params["data"]["groups"] = compact("new", "removed", "rest");
                $params["data"]["user_fname"] = $user->getFirstName();
                $school_url = $this->N->generateUrl("school", ["school" => $user->getSchoolId()]);
                $params["data"]["school_url"] = $school_url;
                $mail = new Mail($params);
                $mail->buildAndSend();

            } else {
                $msg = 'User '.$user->getFullName().' has no mailadress. Check this!';
                $this->N->log($msg, 'Task->sendChangedGroupLeaderMail()');
            }
        }
    }

    private function sendNewUserMail()
    {
        // TODO: check also for unprocessed new group assignments in "changes"
        $new_user_changes = $this->N->ORM->N->getRepository("Change")->findChangesWithNewUser();
        foreach ($new_user_changes as $change) {
            /* @var User $user */
            $user = $this->N->ORM->N->getRepository("User")->find($change->getEntityId());
            $params = ['purpose' => 'welcome_new_user'];
            if ($user->hasMail()) {
                $data["user"]["fname"] = $user->getFirstName();
                $data["user"]["has_mobil"] = $user->hasMobil();
                $data["password"] = $this->N->createPassword($user->getSchoolId());
                $data["groups"] = $user->getGroupIdArray();
                $data["school_url"] = $this->N->generateUrl("school", ["school" => $user->getSchoolId()]);

                $params["data"] = $data;
                $params["receiver"] = $user->getMail();
                $mail = new Mail($params);
                $mail->buildAndSend();
                // TODO: remove in production
                //self::processChange($change);
            } else {
                // TODO: log this somewhere and inform admin
            }
        }

    }

    private function changedVisitDateMail()
    {
        // TODO: implement this function
    }


    /**
     * Checks if sufficient time since last reminder has gone and sends an email (or sms
     * if no mail-address available) to all Users with incomplete profile.
     *
     * @return array $returns;
     */
    private function sendUpdateProfileReminder()
    {
        $annoyance_start = $this->getStartDate("annoyance");
        $imm_start = $this->getStartDate("immunity");
        /* @var UserRepository $user_repo */
        $user_repo = $this->N->getRepo("User");
        $incomplete_users = $user_repo->findIncompleteUsers($imm_start);

        $msg_props["Status"] = ["sent", "received"];
        $msg_props["Subject"] = "profile_update";
        $incomplete_users = array_filter(
            $incomplete_users,
            function ($u) use ($annoyance_start, $msg_props) {
                /* @var User $u */
                // We don't need to remind users without groups or users that have recently gotten a message.
                return $u->hasGroups() && !$u->lastMessageWasAfter($annoyance_start, $msg_props);
            }
        );
        $returns = [];
        /* @var \Fridde\Entities\User $user */
        foreach ($incomplete_users as $user) {
            $params = ['purpose' => 'update_profile_reminder'];
            $data = ['user_fname' => $user->getFirstName()];
            $data['school_staff_url'] = $this->N->createLoginUrl($user);
            $carrier = $user->hasMail() ? 'mail' : ($user->hasMobil() ? 'sms' : null);

            if ($carrier === 'sms') {
                $params["receiver"] = $user->getMobil();
                $long_url = $this->N->createLoginUrl($user);
                $short_url = $this->N->shortenUrl($long_url);
                $rep["login_url"] = explode('//', $short_url)[1];
                $msg = $this->N->getReplacedText(["sms", "update_profile"], $rep);
                $params["message"] = $msg;
                $sms = new SMS($params);
                $return = $sms->buildAndSend();
            } elseif ($carrier === 'mail') {
                $carrier = 'mail';
                $p = ['school', ['school' => $user->getSchoolId()]];
                $data['school_url'] = $this->N->generateUrl(...$p);
                $params["receiver"] = $user->getMail();
                $params['data'] = $data;
                $mail = new Mail($params);
                $return = $mail->buildAndSend();
            } else {
                $e_text = "User with id <".$user->getId()."> has no email or";
                $e_text .= " mobile phone number. Check up on that immediately.";
                $this->N->log($e_text, 'Task->sendUpdateProfileReminder()');
                $return = null;
            }
            $this->logMessage($return, $carrier, $user);
            $returns[] = $return;
        }
        return $returns;
    }

    /**
     * Will clean unused or very old records from the database
     *
     * @return [type] [description]
     */
    private function cleanSQLDatabase()
    {
        $DBM = new DatabaseMaintainer();
        $DBM->cleanOldGroupNumbers();

        // TODO: add more functionality to this function
    }


    /**
     * A quick function to mark a certain Change as processed
     *
     * @param  \Fridde\Entities\Change $change The Change object
     * @return mixed The result of the Update
     */
    public static function processChange(Change $change)
    {
        $e_id = $change->getId();
        $val = Carbon::now()->toIso8601String();

        return (new Update)->updateProperty("Change", $e_id, "Processed", $val);
    }

}
