<?php
namespace Fridde;

class Task extends Naturskolan
{
    public $type;
    public $current_status;
    public $changed_status;
    private $task_to_function_map = [
        "calendar_rebuild" => "rebuildCalendar",
        "mailchimp_sync" => "syncMailchimp",
        "visit_confirmation_mail" => "sendVisitConfirmationMail",
        "visit_confirmation_sms" => "sendVisitConfirmationSMS",
        "admin_summary" => "sendAdminSummaryMail",
        "new_group_leader_mail" => "sendAdminSummaryMail",
        "profile_update_mail" => "sendChangedGroupLeaderMail",
        "table_cleanup" => "cleanSQLDatabase"
    ];

    function __construct ($type, $current_status = [])
    {
        $this->type = $task_type;
        $this->current_status = $current_status;
    }

    public function getChangedStatus()
    {
        return $this->changed_status;
    }

    public function execute()
    {
        $function_name = $this->task_to_function_map[$this->type];
        $this->$function_name;

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
            $this->changed_status = ["calendar.status" => "clean", "calendar.last_rebuild" => $now->toIso8601String()];
        }
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
    }

    private function sendChangedGroupLeaderMail()
    {
    }

    private function sendProfileUpdateReminderMail()
    {
    }

    private function cleanSQLDatabase()
    {
    }
}
