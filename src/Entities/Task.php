<?php
namespace Fridde\Entities;

class Task extends Entity
{
    public $type;
    public $result = false;
    public $admin_mail = [];
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

    function __construct ($type)
    {
        $this->type = $task_type;
    }

    public function execute()
    {
        $function_name = $this->task_to_function_map[$this->type];
        $this->$function_name;
        return $this->result;
    }

    private function rebuildCalendar()
    {
        $last_rebuild = $this->status_array["calendar.last_rebuild"];
        $last_rebuild = new C($last_rebuild);
        $now = C::now();
        $is_dirty = $this->status_array["calendar.status"] == "dirty";
        $too_old = $now->diffInHours($last_rebuild) >= 24;
        if($is_dirty || $too_old){
            $table_names = ["events", "groups", "locations", "schools", "topics", "users", "visits"];
            foreach($table_names as $name){
                $tables[$name] = $this->get($name);
            }
            $cal = new Calendar($tables);
            $cal->save();
            $this->result = ["calendar.status" => "clean", "calendar.last_rebuild" => $now->toIso8601String()];
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

            $row .= $visit->get("Date") .  ": ";
            $row .= $topic->get("ShortName") . " med ";
            $row .= $group->get("Name") . " från ";
            $row .= $school->get("Name") . ". Lärare: ";
            $row .= $user->getCompleteName() . ", ";
            $row .= $user->get("Mobil") . ", " . $user->get("Mail");
            break;

            case "food_changed":
            case "student_nr_changed":
            $group = $info["group"];  // This is already a group object
            $row .= "ÅK " . $group->get("Grade") . ", ";
            $row .= "Grupp " . $group->get("Name") . " från " . $group->getSchool("Name");
            $row .= ", möter oss härnast " .  $group->getNextVisit() . " : ";
            $row .= $error_type == "food_changed" ? "Matpreferenserna " : "Antal elever ";
            $row .= 'ändrades från "' . $info["old"] . '" till "' . $info["new"] . '"';
            break;

            case "soon_last_visit":
            $last_visit = $info["last_visit"];
            $row .= "Snart är sista planerade mötet med eleverna. Börja planera nästa termin!";
            $row .= " Sista möte: " . $last_visit->get("Date");
            break;

            case "user_profile_incomplete":
            $user = $info["user"];  // as an object!
            $school = $user->get("School");
            $mob_nr = $user->get("Mobil");
            $mail = $user->get("Mail");

            $row .= $user->getCompleteName() . " från ";
            $row .= $school->get("Name") . " saknar kontaktuppgifter. ";
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
            $row .= $school->get("Name") . " har fel antal grupper. Det finns $active grupper, ";
            $row .= " men det borde vara $expected";
            break;
            /*
            case "":
            break;
            */

        }
        $this->admin_mail[$type][] = $row;
        $this->sendAdminSummaryMail();
    }

    private function sendAdminSummaryMail()
    {

        if(empty($this->admin_mail)){
            return true;
        }
        $settings = $SETTINGS["admin_summary"];
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
    }

    /**
    * [compileAdminSummaryMail description]
    * @return [type] [description]
    */
    private function compileAdminSummaryMail()
    {
        $settings = $SETTINGS["admin_summary"];


        extract($this->getTable(["history", "changes", "users", "messages",
        "visits", "schools"]));

        // #######################
        // ### visit not confirmed
        // #######################
        foreach($VISITS as $visit_row){
            $visit = new Visit($visit_row);
            $too_close = $visit->daysLeft() <= $settings["no_confirmation_warning"];

            if($too_close && ! $visit->isConfirmed()){
                $info = ["visit" => $visit];
                $this->addToAdminMail("visit_not_confirmed", $info);
            }
        }

        // #######################
        // ### food or number of students changed
        // // #######################
        $criteria[] = ["Table_name", "groups"];
        $criteria[] = ["Column_name", "in", ["Food", "Students"]];
        $error_type_map = ["Food" => "food_changed", "Students" => "student_nr_changed"];

        $CHANGES = U::filterFor($CHANGES, $criteria);
        foreach($CHANGES as $change){
            $group = new Group($change["Row_id"]);
            $next_visit = $group->getNextVisit();
            if($next_visit !== false && $next_visit->daysLeft() <= $settings["important_info_changed"]){
                $col_name = $change["Column_name"];
                $val = $change["Value"];
                $error_type = $error_type_map[$col_name];
                $info = ["old" => $val, "new" => $group->getInfo($col_name)];
                $info["group"] = $group;
                $info["next_visit"] = $next_visit["Date"];
                $this->addToAdminMail($error_type, $info);
            }
        }

        // #######################
        // ### user profile incomplete
        // // #######################
        foreach($USERS as $user_row){
            $user = new User($user_row);
            $immune = $user->daysSinceAdded() <= $settings["immunity_time"];
            $too_frequent = $user->daysSinceLastMessage("reminder") < $settings["annoyance_interval"];
            if( ! ($immune || $too_frequent) &&  ! ($user->has("Mobil") && $user->has("Mail"))){
                $info = ["user" => $user];
                $this->addToAdminMail("user_profile_incomplete", $info);
            }
        }

        // #######################
        // ### less than 60days to last booked visit
        // // #######################
        $visits = U::orderBy($VISITS, "Date", "datestring");
        $last_visit = new Visit(array_pop($visits));
        if($last_visit->daysLeft() <= $settings["soon_last_visit"]){
            $info = ["last_visit" => $last_visit];
            $this->addToAdminMail("soon_last_visit", $info);
        }

        // #######################
        // ### wrong amount of groups
        // // #######################
        foreach($SCHOOLS as $school_row){
            $school = new School($school_row);
            $expected = $school->countExpectedGroups();
            $active = $school->countActiveGroups();
            if($expected !== $active){
                $info = ["school" => $school, "expected" => $expected, "active" => $active];
                $this->addToAdminMail("wrong_group_count", $info);
            }
        }

        /*
        any school
        any arskurs
        count_is = count_where(group[arskurs] == arskurs)
        count_should = school[arskurs]
        if(count_is != count_should)
        add to mail(count_is, count_should, school, arskurs)

        /*



        ---------------------------------------------------------------
        ### bus not in sync

        ### food not in sync

        ###group leader is not teacher
        any group
        group_leader = select_where(group[user] == user[id])
        if(group_leader[type] == "admin")
        add to mail(group, user)
        */
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
