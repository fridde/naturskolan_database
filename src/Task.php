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
    private function addToAdminMail($type, $info = []){
        $tables = $this->getAllTables();
        $row = "";

        switch($type){
            case "visit_not_confirmed":
            $topic = U::filterFor($tables["topics"], ["id", $info["Topic"]]);
            $group = U::filterFor($tables["groups"], ["id", $info["Group"]]);
            $school = U::filterFor($tables["schools"], ["id", $group["School"]]);
            $user = U::filterFor($tables["users"], ["id", $group["User"]]);

            $row .= $info["Date"] .  ": ";
            $row .= $topic["ShortName"] . " med ";
            $row .= $group["Name"] . " från ";
            $row .= $school["Name"] . " Lärare: ";
            $row .= $user["FirstName"] . " " . $user["LastName"] . ", ";
            $row .= $user["Mobil"] . ", " . $user["Mail"];
            break;

            case "food_changed":

            break;

            case "student_nr_changed":
            break;

            case "soon_last_visit":
            break;

            case "user_profile_incomplete":
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

        $visits = $this->getTable("visits");

        // visit not confirmed
        foreach($tables["visits"] as $visit_row){
            $visit = new Visit($visit_row);
            $too_close = $visit->daysLeft() <= $settings["no_confirmation_warning"];
            if($too_close && ! $visit->isConfirmed()){
                $this->addToAdminMail("visit_not_confirmed", $visit);
            }
        }

        // food or number of students changed
        $criteria[] = ["Table_name", "groups"];
        $criteria[] = ["Column_name", "in", ["Food", "Students"]];

        $field_history = U::filterFor($tables["field_history"], $criteria);
        foreach($field_history as $hist_field){
            $group = new Group($hist_field["Row_id"]);
            $next_visit = $group->getNextVisit();
            if($next_visit !== false && $next_visit->daysLeft() <= $settings["important_info_changed"]){
                $type = $hist_field["Column_name"] == "Food" ? "food_changed" : "student_nr_changed";
                //$this->addToAdminMail($type [, $info])
                //TODO: implement "get group information"
            }

        }



        /*

        any field_history where (Table == "groups" && (Column in(["Food", "Students"])
        group = select_where(group[id] == field_history[row_id])
        group_visits = select_where(visit[group] == group[id] && visit[date] >= now)
        next_visit_date = min(group_visits[date])
        column = field_history[Column]
        if(field_history[value] != group[column] && diff(next_visit_date, now) < 2 weeks)
        add to mail(group, column, value, next_visit_date)
        delete field_history

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


        ### user profile incomplete
        any user
        reminder_messages = select_where(message[to] == user[id] && message[type] == "reminder")
        latest_reminder_date = max(reminder_messages[date])
        if (user[created] more than 1 week ago && (user[mobil] == "" || user[mail] == "") && diff(now, latest_reminder_date) > 4 days)
        add to mail(user name)

        ###

        */
    }

    private function sendChangedGroupLeaderMail()
    {
        /*
        any field_history where (Table == "groups" && Column == "User")
        group = select_where(group[user] == value)
        if(group[user] != value)
        send_mail(group[user], group)
        */
    }

    private function changedVisitDateMail()
    {
        /*
        any field_history where (Table == "visits" && Column == "Date")
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
