<?php
namespace Fridde;

use GuzzleHttp\Client;
use Carbon\Carbon;
use Fridde\{Calendar, Utility as U, Update};
use Fridde\Entities\{Group};

class Task
{
    private $N;
    private $Client;
    public $type;
    public $result;
    public $admin_mail;
    private $api_key;
    private $task_activation;
    private $task_to_function_map = [
        "calendar_rebuild" => "rebuildCalendar",
        "backup" => "backup",
        "mailchimp_sync" => "syncMailchimp",
        "visit_confirmation_message" => "sendVisitConfirmationMessage",
        "admin_summary" => "sendAdminSummaryMail",
        "changed_group_leader_mail" => "sendChangedGroupLeaderMail",
        "profile_update_reminder" => "sendProfileUpdateReminder",
        "table_cleanup" => "cleanSQLDatabase"
    ];

    public function __construct ($task_type = null)
    {
        $this->type = $task_type;
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->api_key = $this->getSettings("smtp_settings", "api_key");

    }

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

    private function rebuildCalendar()
    {
        $last_rebuild = $this->N->getLastRebuild();
        $is_dirty = $this->N->calendarIsDirty();
        $too_old = Carbon::now()->diffInHours($last_rebuild) >= 24;
        if($is_dirty || $too_old){
            $cal = new Calendar();
            $cal->save();
            $this->N->setCalendarToClean();
        }
    }

    private function backupDatabase()
    {

    }

    private function syncMailchimp()
    {
    }

    private function getStartDate($type)
    {
        $translator = ["immunity" => "immunity_time", "annoyance" => "annoyance_interval"];
        $setting = $translator[strtolower($type)];
        $t = $this->getSettings("user_message", $setting);
        $days = U::convertDuration($t, "d");
        return Carbon::today()->subDays($days);
    }

    private function sendVisitConfirmationMessage()
    {
        $annoyance_start = $this->getStartDate("annoyance");
        $msg_props["Status"] = ["sent", "received"];
        $msg_props["Subject"] = "confirmation";

        $time = $this->getSettings("user_message", "visit_confirmation_time");
        $days = U::convertDuration($time, "d");

        $unconfirmed_visits = $this->getRepo("Visit")->findUnconfirmedVisitsWithin($days);
        $unconfirmed_visits = array_values($unconfirmed_visits->toArray());

        foreach($unconfirmed_visits as $index => $v){
            if($index !== 0){ exit(); } //TODO: remove in production, only for testing purposes
            $user = $v->getGroup()->getUser();
            $last_msg = $user->getlastMessage($msg_props);

            // TODO: Change in production
            $msg_carrier = "sms";
            $response = $this->sendVisitConfirmationSMS($v);
            //$msg_carrier = "mail";
            //$response = $this->sendVisitConfirmationMail($v);

            /*
            *    if(empty($last_msg) || !$user->hasMobil()){
            *    $msg_carrier = "mail";
            *    $response = $this->sendVisitConfirmationMail($v);
            *    } elseif(!$last_msg->wasSentAfter($annoyance_start)){
            *    $msg_carrier = "sms";
            *    $response = $this->sendVisitConfirmationSMS($v);
            *    }
            */
            $body = (string) $response->getBody();
            $result = json_decode($body, true);
            if($result["success"]){
                $msg_props["User"] = $user;
                $msg_props["Subject"] = "confirmation";
                $msg_props["Carrier"] = $msg_carrier;
                $msg_props["Status"] = "sent";
                $request["updateType"] = "createNewEntity";
                $request["entity_class"] = "Message";
                $request["properties"] = $msg_props;
                $update_result = Update::create($request);
                echo "";
            }



        }
    }

    private function sendVisitConfirmationMail($visit)
    {
        $v = $visit;
        $post["api_key"] = $this->api_key;
        // TODO: remove in production
        //$post["receiver"] = $v->getGroup()->getUser()->getMail();
        $post["receiver"] = 'kjhtt@cheaphub.net';
        $data["user_fname"] = $v->getGroup()->getUser()->getFirstName();
        $data["confirmation_url"] = $this->N->getConfirmationUrl($v->getId());
        $s_url = APP_URL . "skola/" . $v->getGroup()->getSchool()->getId();
        $data["school_url"] = $s_url;
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
        return $this->sendRequest($url, $post);
    }

    private function sendVisitConfirmationSMS($visit)
    {
        $post["api_key"] = $this->api_key;
        $post["receiver"] = $visit->getGroup()->getUser()->getMobil();
        $post["receiver"] = "0736665275"; // TODO: remove in production
        $rep["date_string"] = $visit->getDate()->formatLocalized("%e %B");
        $msg = $this->N->getReplacedText(["sms", "confirm_visit"], $rep);
        $post["message"] = $msg;

        $url = $this->N->generateUrl("sms", ["type" => "confirm_visit"]);
        return $this->sendRequest($url, $post);
    }

    private function sendRequest($url, $data = [])
    {
        return $this->getClient()->post($url, ['form_params' => $data]);
        usleep(100 * 1000); // = 0.1 seconds to not choke the server
    }

    public function sendAdminSummaryMail()
    {

        $this->compileAdminSummaryMail();

        if(empty($this->admin_mail)){
            return true;
        }
        $data["data"]["errors"] = $this->admin_mail;
        $data["data"]["labels"] = $this->N->getText(["admin_summary"]);
        $data["api_key"] = $this->api_key;
        $url = $this->N->createMailUrl("admin_summary");
        echo $this->sendRequest($url, $data);
    }

    /**
    * [compileAdminSummaryMail description]
    * @return [type] [description]
    */
    private function compileAdminSummaryMail()
    {
        // #######################
        // ### visit not confirmed
        // #######################
        $days = $this->getSettings("admin_summary", "no_confirmation_warning");
        $unconfirmed_visits = $this->getRepo("Visit")->findUnconfirmedVisitsWithin($days);
        $this->addToAdminMail("visit_not_confirmed", $unconfirmed_visits->toArray());

        // #######################
        // ### food or number of students changed
        // #######################
        $deadline = Carbon::today()->addDays($this->getSettings("important_info_changed"));
        $a = [];

        $group_changes = $this->getRepo("Change")->findGroupChanges()->toArray();
        foreach($group_changes as $change){
            $g = $this->getRepo("Group")->find($change->getEntityId());
            $next_visit = $g->getNextVisit();
            if(!empty($next_visit) && $next_visit->isBefore($deadline)){
                $att = $change->getAttribute();
                $method = "get" . $att;
                $new_value = $g->$method();
                $return = ["from" => $change->getOldValue(), "to" => $new_value];
                $return["group"] = $g;
                $a[$att][] = $return;
            }
        }
        $this->addToAdminMail("food_changed", $a["Food"] ?? []);
        $this->addToAdminMail("nr_students_changed", $a["NumberStudents"] ?? []);


        // #######################
        // ### user profile incomplete
        //#######################
        $imm_date = $this->getStartDate("immunity");
        $incomplete_users = $this->getRepo("User")->findIncompleteUsers($imm_date);
        $this->addToAdminMail("user_profile_incomplete", $incomplete_users);

        // #######################
        // ###  last booked visit is coming soon!
        //#######################
        $days_left = $this->getSettings("admin_summary", "soon_last_visit");
        $last_visit_deadline =  Carbon::today()->addDays($days_left);
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
        $bad_visits = $visits->filter(function($v){
            $g = $v->getGroup();
            return (empty($g) ? false : !$g->isActive()); //empty groups are okay
        });

        $this->addToAdminMail("inactive_group_visit", $bad_visits->toArray());


        // #######################
        // ### too many students in class
        // #######################
        $large_groups = array_filter($groups, function($g){
            return $g->getNumberStudents() > 33;
        });
        $this->addToAdminMail("too_many_students", $large_groups);

        /*
        ### bus not in sync
        ### food not in sync
        */
    }

    /**
    * [addToAdminMail description]
    * @param [type] $type [description]
    * @param [type] $info [description]
    */
    private function addToAdminMail($error_type, $entities = []){

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

                $g = $entity["group"];
                $row .= $g->getGradeLabel() . ", ";
                $row .= "Grupp " . ($g->getName() ?? '???') . " från " . $g->getSchool()->getName();
                $row .= ", möter oss härnast " ;
                $row .= $g->getNextVisit()->getDate()->toDateString() . " : ";
                $row .= $error_type == "food_changed" ? "Matpreferenserna" : "Antal elever";
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
                $mob_nr = $u->getMobil();
                $mail = $u->getMail();

                $row .= $u->getCompleteName() . ", ";
                $row .= $u->getSchool()->getName() . ": ";
                $row .= "Mobil: " . ($u->hasMobil() ? $u->getMobil() : '???') . ", ";
                $row .= "Mejl: " . ($u->hasMail() ? $u->getMail() : '???') . ", ";
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

    private function sendChangedGroupLeaderMail()
    {


        /*
        any changes where (Table == "groups" && Column == "User")
        group = select_where(group[user] == value)
        if(group[user] != value)
        send_mail(group[user], group)
        */
    }

    private function sendNewGroupLeaderMail()
    {
    }

    private function changedVisitDateMail()
    {
        /*
        any changes where (Table == "visits" && Column == "Date")
        visit = select_where(visit[Date] == value)
        if(visit[Date] != value)
        send_mail(...
        */
    }



    private function sendProfileUpdateReminder()
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

        $mail_url =  APP_URL . 'mail/update_profile_reminder';
        $sms_url = APP_URL . 'sms/update_profile_reminder';

        foreach($incomplete_users as $user){
            $post = ["api_key" => $this->api_key];
            if(!$user->hasMail() && $user->hasMobil()){
                $post["receiver"] = $user->getMobil();
                $long_url = $this->N->getLoginUrl($user);
                $short_url = $this->N->shortenUrl($long_url);
                $rep["login_url"] = explode('//', $short_url)[1];
                $msg = $this->N->getReplacedText(["sms", "update_profile"], $rep);
                $post["message"] = $msg;
                $response = $this->sendRequest($sms_url, $post);

            } elseif($user->hasMail()) {
                $post["receiver"] = $user->getMail();
                $response = $this->sendRequest($mail_url, $post);
            } else {
                $e_text = "User with id <" . $user->getId() . "> has no email or";
                $e_text .= " mobile phone number. Check up on that immediately.";
                $GLOBALS["LOGGER"]->warning($e_text);
            }
        }
    }

    private function cleanSQLDatabase()
    {
    }

    private function getSettings()
    {
        $args = func_get_args();
        if(count($args) === 1 && is_array($args[0])){
            $args = $args[0];
        }
        return U::resolve(SETTINGS, $args);
    }

    private function getRepo($repo)
    {
        return $this->N->ORM->getRepository($repo);
    }

    private function getClient($new_client = false)
    {
        if(empty($this->Client) || $new_client){
            $this->Client = new Client();
        }
        return $this->Client;
    }

}
