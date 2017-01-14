<?php
namespace Fridde;

use \Carbon\Carbon as C;
use \Fridde\{ORM, Calendar, Utility as U, Mailer};

class Task
{
    public $N;
    public $type;
    public $task_table;
    public $result;
    public $admin_mail;
    private $task_to_function_map = [
        "calendar_rebuild" => "rebuildCalendar",
        "backup" => "backup",
        "mailchimp_sync" => "syncMailchimp",
        "visit_confirmation_mail" => "sendVisitConfirmationMail",
        "visit_confirmation_sms" => "sendVisitConfirmationSMS",
        "admin_summary" => "sendAdminSummaryMail",
        "new_group_leader_mail" => "sendAdminSummaryMail",
        "profile_update_mail" => "sendChangedGroupLeaderMail",
        "table_cleanup" => "cleanSQLDatabase"
    ];

    public function __construct ($task_type = null)
    {
        $this->type = $task_type ?? null;
        $this->N = new Naturskolan();
    }

    public function execute()
    {
        $function_name = $this->task_to_function_map[$this->type];
        $this->$function_name();
    }

    private function rebuildCalendar()
    {
        $last_rebuild = $this->N->getLastRebuild();
        $is_dirty = $this->N->calendarIsDirty();
        $too_old = $this->_NOW_->diffInHours($last_rebuild) >= 24;
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

    private function sendVisitConfirmationMail()
    {
    }

    private function sendVisitConfirmationSMS()
    {
    }

    private function sendAdminSummaryMail()
    {

        $this->compileAdminSummaryMail();

        if(empty($this->admin_mail)){
            return true;
        }
        $settings = $GLOBALS["SETTINGS"]["admin_summary"];
        $params["to"] = $settings["admin_adress"];
        $params["from"] = $settings["admin_adress"];
        $params["subject"] = $this->getText("admin_mail/defaults/subject");
        $M = new Mailer($params);

        foreach($this->admin_mail as $type => $rows){
            $pre_text = $this->getText("admin_mail/headers/" . $type);
            $M->addHeader($pre_text);
            foreach ($rows as $row){
                $M->addRow($row);
            }
        }
        bdump($this->admin_mail);
        //TODO: Finish the creation of this mail and send it
    }

    /**
    * [compileAdminSummaryMail description]
    * @return [type] [description]
    */
    private function compileAdminSummaryMail()
    {
        $settings = $GLOBALS["SETTINGS"]["admin_summary"];


        // #######################
        // ### visit not confirmed
        // #######################
        $deadline = Carbon::now()->addDays($settings["no_confirmation_warning"]);
        $visits = $this->N->ORM->getRepository("Visit")->findFutureVisits($deadline);
        $unconfirmed_visits = $visits->filter(function($visit){
            return ! $visit->isConfirmed();
        });
        $this->addToAdminMail("visit_not_confirmed", $unconfirmed_visits);

        // #######################
        // ### food or number of students changed
        // // #######################
        $error_type_map = ["Food" => "food_changed", "Students" => "student_nr_changed"];

        $changes_concerning_groups = $this->N->ORM->getRepository("Change")->findGroupChanges();
        $deadline = Carbon::today()->addDays($settings["important_info_changed"]);
        foreach($changes_concerning_groups as $change){
            $group = $this->N->ORM->getRepository("Group")->find($change->getEntityId());
            $next_visit = $group->getNextVisit();

            if(!empty($next_visit) && $next_visit->isBefore($deadline)){
                $att = $change->getAttribute();
                $error_type = $error_type_map[$att];
                if($att == "Food"){
                    $new_value = $group->getFood();
                } elseif($att == "NumberStudents"){
                    $new_value = $group->getNumberStudents();
                }
                $info = ["from" => $change->getOldValue(), "to" => $new_value];
                $info["group"] = $group;
                $this->addToAdminMail($error_type, $info);
            }
        }

        // #######################
        // ### user profile incomplete
        // // #######################
        $immunity_start = Carbon::today()->subDays($settings["immunity_time"]);
        $annoyance_start = Carbon::today()->subDays($settings["annoyance_interval"]);
        $users = $this->N->ORM->getRepository("User")->findActiveUsers();
        foreach($users as $user){
            $immune = $user->wasCreatedAfter($immunity_start);
            $too_frequent = $user->lastMessageWasAfter($annoyance_start);
            if( ! ($immune || $too_frequent) &&  ! ($user->hasMobil() && $user->hasMail())){
                $this->addToAdminMail("user_profile_incomplete", $user);
            }
        }


        // #######################
        // ###  last booked visit is coming soon!
        // // #######################
        $last_visit_deadline =  Carbon::today()->addDays($settings["soon_last_visit"]);
        $last_visit = $this->N->ORM->getRepository("Visit")->findLastVisit();
        if(empty($last_visit) || $last_visit->getDate()->lte($last_visit_deadline)){
            $this->addToAdminMail("soon_last_visit", $last_visit);
        }


        // #######################
        // ### wrong amount of groups
        // // #######################
        $schools = $this->N->ORM->getRepository("School")->findAll();
        foreach($schools as $school){
            foreach(School::GRADES_COLUMN as $column_val => $attribute){
                $active = $school->getNrActiveGroupsByGrade($column_val);
                $method_name = "get" . $attribute;
                $expected = $school->$method_name();

                if($expected !== $active){
                    $info["school"] = $school;
                    $info["grade"] = $column_val;
                    $info["expected"] = $expected;
                    $info["active"] = $active;

                    $this->addToAdminMail("wrong_group_count", $info);
                }
            }
        }

/*
        ---------------------------------------------------------------
        ### bus not in sync

        ### food not in sync

        ###group leader is not teacher
        any active group
        any group->User->getRole != User::TEACHER
        if(group_leader[type] == "admin")
        add to mail(group, user)
        */
    }

    /**
    * [addToAdminMail description]
    * @param [type] $type [description]
    * @param [type] $info [description]
    */
    private function addToAdminMail($error_type, $info = []){
        $row = "";

        switch($error_type){
            case "visit_not_confirmed":
            $visit = $info["visit"]; //as object
            $topic = $visit->getAsObject("Topic");
            $group = $visit->getAsObject("Group");
            $school = $group->getAsObject("School");
            $user = $group->getAsObject("User");

            $row .= $visit->pick("Date") .  ": ";
            $row .= $topic->pick("ShortName") . " med ";
            $row .= $group->pick("Name") . " från ";
            $row .= $school->pick("Name") . ". Lärare: ";
            $row .= $user->getCompleteName() . ", ";
            $row .= $user->pick("Mobil") . ", " . $user->pick("Mail");
            break;

            case "food_changed":
            case "student_nr_changed":
            $group = $info["group"];  // This is already a group object
            $row .= "ÅK " . $group->pick("Grade") . ", ";
            $row .= "Grupp " . $group->pick("Name") . " från " . $group->getSchool("Name");
            $row .= ", möter oss härnast " .  $group->getNextVisit() . " : ";
            $row .= $error_type == "food_changed" ? "Matpreferenserna " : "Antal elever ";
            $row .= 'ändrades från "' . $info["old"] . '" till "' . $info["new"] . '"';
            break;

            case "soon_last_visit":
            $last_visit = $info["last_visit"];
            $row .= "Snart är sista planerade mötet med eleverna. Börja planera nästa termin!";
            $row .= " Sista möte: " . $last_visit->pick("Date");
            break;

            case "user_profile_incomplete":
            $user = $info["user"];  // as an object!
            $school = $user->pick("School");
            $mob_nr = $user->pick("Mobil");
            $mail = $user->pick("Mail");

            $row .= $user->getCompleteName() . " från ";
            $row .= $school->pick("Name") . " saknar kontaktuppgifter. ";
            $row .= "Mobil: " . (empty($mob_nr) ? "???" : $mob_nr) . ", ";
            $row .= "Mejl: " .  (empty($mail) ? "???" : $mail) . ".";
            break;

            case "group_leader_not_teacher":
            break;

            case "bus_schedule_outdated":
            break;

            case "food_order_outdated":
            break;

            case "wrong_group_count":
            //$info = ["school" => $school, "expected" => $expected, "active" => $active];
            extract($info);
            $row .= $school->pick("Name") . " har fel antal grupper. Det finns $active grupper, ";
            $row .= " men det borde vara $expected";
            break;
            /*
            case "":
            break;
            */

        }
        $this->admin_mail = $this->admin_mail ?? [];
        $this->admin_mail[$error_type][] = $row;
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

    private function changedVisitDateMail()
    {
        /*
        any changes where (Table == "visits" && Column == "Date")
        visit = select_where(visit[Date] == value)
        if(visit[Date] != value)
        send_mail(...
        */
    }

    private function sendProfileUpdateReminderMail()
    {
        /*
        any user
        if
        */
    }

    private function cleanSQLDatabase()
    {
    }
}
