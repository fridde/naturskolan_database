<?php
namespace Fridde;

class Task extends Naturskolan
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
        $tables = $this->getTable(["topics", "groups", "schools", "users"]);
        $row = "";

        switch($error_type){
            case "visit_not_confirmed":
            $topic = U::getById($TOPICS, $info["Topic"]);
            $group = U::getById($GROUPS, $info["Group"]);
            $school = U::getById($SCHOOLS, $group["School"]);
            $user = U::getById($USERS, $group["User"]);

            $row .= $info["Date"] .  ": ";
            $row .= $topic["ShortName"] . " med ";
            $row .= $group["Name"] . " från ";
            $row .= $school["Name"] . " Lärare: ";
            $row .= $user["FirstName"] . " " . $user["LastName"] . ", ";
            $row .= $user["Mobil"] . ", " . $user["Mail"];
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
            break;

            case "user_profile_incomplete":
            $user = $info["user"];  // as an object!
            // TODO: continue this part!
            break;

            case "group_leader_not_teacher":
            break;

            case "bus_schedule_outdated":
            break;

            case "food_order_outdated":
            break;

            case "wrong_group_count":
            break;
            /*
            case "":
            break;
            */

        }
        $this->admin_mail[$type][] = $row;
    }

    private function sendAdminSummaryMail()
    {
        if(count($this->admin_mail) == 0){
            return true;
        }
        $M = new Mailer();
        foreach($this->admin_mail as $type => $rows){
            $pre_text = $this->getText("admin_mail/headers/" . $type);
            foreach ($rows as $row){

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


        extract($this->getTable(["history", "changes", "users", "messages", "visits"]));

        // #######################
        // ### visit not confirmed
        // #######################
        foreach($VISITS as $visit_row){
            $visit = new Visit($visit_row);
            $too_close = $visit->daysLeft() <= $settings["no_confirmation_warning"];
            if($too_close && ! $visit->isConfirmed()){
                $this->addToAdminMail("visit_not_confirmed", $visit);
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


        /*

*/



        /*

        ---------------------------------------------------------------
        ### bus not in sync

        ### food not in sync

        ### less than 60days to last booked visit
        if(diff(max visit[date], now) < 60 days))
        add to mail(diff)

        ### wrong amount of groups
        any school
        any arskurs
        count_is = count_where(group[arskurs] == arskurs)
        count_should = school[arskurs]
        if(count_is != count_should)
        add to mail(count_is, count_should, school, arskurs)

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
