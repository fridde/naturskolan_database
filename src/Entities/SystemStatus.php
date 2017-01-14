<?php
namespace Fridde\Entities;

use \Carbon\Carbon as C;
use \Fridde\{Calendar, Utility as U, Mailer};
use Doctrine\Common\Collections\ArrayCollection;


/**
* @Entity
* @Table(name="systemstatus")
*/
class SystemStatus
{
    /** @Id @Column(type="string")  */
    protected $id;

    /** @Column(type="string") */
    protected $Value;

    /** @Column(type="string") */
    protected $LastChange;

    const CLEAN = "clean";
    const DIRTY = "dirty";

    public function getId(){return $this->id;}
    public function setId($id){$this->id = $id;}
    public function getValue(){return $this->Value;}
    public function setValue($Value){$this->Value = $Value;}
    public function getLastChange(){return $this->LastChange;}
    public function setLastChange($LastChange){$this->LastChange = $LastChange;}
    /** @PostPersist */
    public function postPersist(){ }
    /** @PostUpdate */
    public function postUpdate(){ }
    /** @PreRemove */
    public function preRemove(){ }

    // /*
    // public $type;
    // public $task_table;
    // public $result;
    // // an array containing a valid set of update-parameters to give to batchUpdate()
    // public $updates;
    // public $admin_mail;
    // private $task_to_function_map = [
    //     "calendar_rebuild" => "rebuildCalendar",
    //     "backup" => "backup",
    //     "mailchimp_sync" => "syncMailchimp",
    //     "visit_confirmation_mail" => "sendVisitConfirmationMail",
    //     "visit_confirmation_sms" => "sendVisitConfirmationSMS",
    //     "admin_summary" => "sendAdminSummaryMail",
    //     "new_group_leader_mail" => "sendAdminSummaryMail",
    //     "profile_update_mail" => "sendChangedGroupLeaderMail",
    //     "table_cleanup" => "cleanSQLDatabase"
    // ];
    //
    // function __construct ($task_type)
    // {
    //     parent::__construct();
    //     $this->type = $task_type;
    // }
    //
    // public function execute()
    // {
    //     $function_name = $this->task_to_function_map[$this->type];
    //     $this->$function_name();
    //     $this->result = $this->result ?? false;
    //     $this->updates = $this->updates ?? null;
    //
    //     return ["result" => $this->result, "updates" => $this->updates];
    // }
    //
    // private function setTaskTable()
    // {
    //     $task_table = $this->getTable("tasks");
    //     foreach($task_table as $task_row){
    //         $this->task_table[$task_row["Name"]] = $task_row["Value"];
    //     }
    // }
    //
    // public function getStatus()
    // {
    //     return $this->result ?? false;
    // }
    //
    // public function getUpdates()
    // {
    //     return $this->updates ?? [];
    // }
    //
    // public function addToUpdates($name, $value)
    // {
    //     $new["Value"] = $value;
    //     $new["Timestamp"] = $this->getTimestamp();
    //     $crit = ["Name", $name];
    //     $this->updates[] = [$new, $crit];
    // }
    //
    // private function rebuildCalendar()
    // {
    //     $this->setTaskTable();
    //     $last_rebuild = $this->task_table["calendar.last_rebuild"];
    //     $last_rebuild = new C($last_rebuild);
    //     $is_dirty = $this->task_table["calendar.status"] == "dirty";
    //     $too_old = $this->_NOW_->diffInHours($last_rebuild) >= 24;
    //     if($is_dirty || $too_old){
    //         $cal = new Calendar();
    //         $cal->save();
    //         $this->result = true;
    //         $this->addToUpdates("calendar.status", "clean");
    //         $this->addToUpdates("calendar.last_rebuild", $this->getTimestamp());
    //     }
    // }
    //
    // private function backupDatabase()
    // {
    //
    // }
    //
    // private function syncMailchimp()
    // {
    // }
    //
    // private function sendVisitConfirmationMail()
    // {
    // }
    //
    // private function sendVisitConfirmationSMS()
    // {
    // }
    //
    // private function sendAdminSummaryMail()
    // {
    //
    //     $this->compileAdminSummaryMail();
    //
    //     if(empty($this->admin_mail)){
    //         return true;
    //     }
    //     $settings = $GLOBALS["SETTINGS"]["admin_summary"];
    //     $params["to"] = $settings["admin_adress"];
    //     $params["from"] = $settings["admin_adress"];
    //     $params["subject"] = $this->getText("admin_mail/defaults/subject");
    //     $M = new Mailer($params);
    //
    //     foreach($this->admin_mail as $type => $rows){
    //         $pre_text = $this->getText("admin_mail/headers/" . $type);
    //         $M->addHeader($pre_text);
    //         foreach ($rows as $row){
    //             $M->addRow($row);
    //         }
    //     }
    //     bdump($this->admin_mail);
    //     //TODO: Finish the creation of this mail and send it
    // }
    //
    // /**
    // * [compileAdminSummaryMail description]
    // * @return [type] [description]
    // */
    // private function compileAdminSummaryMail()
    // {
    //     $settings = $GLOBALS["SETTINGS"]["admin_summary"];
    //
    //     extract($this->getTable(["changes", "users", "messages",
    //     "visits", "schools"]));
    //
    //     // #######################
    //     // ### visit not confirmed
    //     // #######################
    //     foreach($VISITS as $visit_row){
    //         $visit = new Visit($visit_row);
    //         $too_close = $visit->daysLeft() <= $settings["no_confirmation_warning"];
    //
    //         if($too_close && ! $visit->isConfirmed()){
    //             $info = ["visit" => $visit];
    //             $this->addToAdminMail("visit_not_confirmed", $info);
    //         }
    //     }
    //
    //     // #######################
    //     // ### food or number of students changed
    //     // // #######################
    //     $criteria[] = ["Table_name", "groups"];
    //     $criteria[] = ["Column_name", "in", ["Food", "Students"]];
    //     $error_type_map = ["Food" => "food_changed", "Students" => "student_nr_changed"];
    //
    //     $CHANGES = U::filterFor($CHANGES, $criteria);
    //     foreach($CHANGES as $change){
    //         $group = new Group($change["Row_id"]);
    //         $next_visit = $group->getNextVisit();
    //         if($next_visit !== false && $next_visit->daysLeft() <= $settings["important_info_changed"]){
    //             $col_name = $change["Column_name"];
    //             $val = $change["Value"];
    //             $error_type = $error_type_map[$col_name];
    //             $info = ["old" => $val, "new" => $group->getInfo($col_name)];
    //             $info["group"] = $group;
    //             $info["next_visit"] = $next_visit["Date"];
    //             $this->addToAdminMail($error_type, $info);
    //         }
    //     }
    //
    //     // #######################
    //     // ### user profile incomplete
    //     // // #######################
    //     foreach($USERS as $user_row){
    //         $user = new User($user_row);
    //         $immune = $user->daysSinceAdded() <= $settings["immunity_time"];
    //         $too_frequent = $user->daysSinceLastMessage("reminder") < $settings["annoyance_interval"];
    //         if( ! ($immune || $too_frequent) &&  ! ($user->has("Mobil") && $user->has("Mail"))){
    //             $info = ["user" => $user];
    //             $this->addToAdminMail("user_profile_incomplete", $info);
    //         }
    //     }
    //
    //     // #######################
    //     // ### less than 60days to last booked visit
    //     // // #######################
    //     $visits = U::orderBy($VISITS, "Date", "datestring");
    //     $last_visit = new Visit(array_pop($visits));
    //     if($last_visit->daysLeft() <= $settings["soon_last_visit"]){
    //         $info = ["last_visit" => $last_visit];
    //         $this->addToAdminMail("soon_last_visit", $info);
    //     }
    //
    //     // #######################
    //     // ### wrong amount of groups
    //     // // #######################
    //     foreach($SCHOOLS as $school_row){
    //         $school = new School($school_row);
    //         $expected = $school->countExpectedGroups();
    //         $active = $school->countActiveGroups();
    //         if($expected !== $active){
    //             $info = ["school" => $school, "expected" => $expected, "active" => $active];
    //             $this->addToAdminMail("wrong_group_count", $info);
    //         }
    //     }
    //
    //     /*
    //     any school
    //     any arskurs
    //     count_is = count_where(group[arskurs] == arskurs)
    //     count_should = school[arskurs]
    //     if(count_is != count_should)
    //     add to mail(count_is, count_should, school, arskurs)
    //
    //     /*
    //
    //
    //
    //     ---------------------------------------------------------------
    //     ### bus not in sync
    //
    //     ### food not in sync
    //
    //     ###group leader is not teacher
    //     any group
    //     group_leader = select_where(group[user] == user[id])
    //     if(group_leader[type] == "admin")
    //     add to mail(group, user)
    //     */
    // }
    //
    // /**
    // * [addToAdminMail description]
    // * @param [type] $type [description]
    // * @param [type] $info [description]
    // */
    // private function addToAdminMail($error_type, $info = []){
    //     $row = "";
    //
    //     switch($error_type){
    //         case "visit_not_confirmed":
    //         $visit = $info["visit"]; //as object
    //         $topic = $visit->getAsObject("Topic");
    //         $group = $visit->getAsObject("Group");
    //         $school = $group->getAsObject("School");
    //         $user = $group->getAsObject("User");
    //
    //         $row .= $visit->pick("Date") .  ": ";
    //         $row .= $topic->pick("ShortName") . " med ";
    //         $row .= $group->pick("Name") . " från ";
    //         $row .= $school->pick("Name") . ". Lärare: ";
    //         $row .= $user->getCompleteName() . ", ";
    //         $row .= $user->pick("Mobil") . ", " . $user->pick("Mail");
    //         break;
    //
    //         case "food_changed":
    //         case "student_nr_changed":
    //         $group = $info["group"];  // This is already a group object
    //         $row .= "ÅK " . $group->pick("Grade") . ", ";
    //         $row .= "Grupp " . $group->pick("Name") . " från " . $group->getSchool("Name");
    //         $row .= ", möter oss härnast " .  $group->getNextVisit() . " : ";
    //         $row .= $error_type == "food_changed" ? "Matpreferenserna " : "Antal elever ";
    //         $row .= 'ändrades från "' . $info["old"] . '" till "' . $info["new"] . '"';
    //         break;
    //
    //         case "soon_last_visit":
    //         $last_visit = $info["last_visit"];
    //         $row .= "Snart är sista planerade mötet med eleverna. Börja planera nästa termin!";
    //         $row .= " Sista möte: " . $last_visit->pick("Date");
    //         break;
    //
    //         case "user_profile_incomplete":
    //         $user = $info["user"];  // as an object!
    //         $school = $user->pick("School");
    //         $mob_nr = $user->pick("Mobil");
    //         $mail = $user->pick("Mail");
    //
    //         $row .= $user->getCompleteName() . " från ";
    //         $row .= $school->pick("Name") . " saknar kontaktuppgifter. ";
    //         $row .= "Mobil: " . (empty($mob_nr) ? "???" : $mob_nr) . ", ";
    //         $row .= "Mejl: " .  (empty($mail) ? "???" : $mail) . ".";
    //         break;
    //
    //         case "group_leader_not_teacher":
    //         break;
    //
    //         case "bus_schedule_outdated":
    //         break;
    //
    //         case "food_order_outdated":
    //         break;
    //
    //         case "wrong_group_count":
    //         //$info = ["school" => $school, "expected" => $expected, "active" => $active];
    //         extract($info);
    //         $row .= $school->pick("Name") . " har fel antal grupper. Det finns $active grupper, ";
    //         $row .= " men det borde vara $expected";
    //         break;
    //         /*
    //         case "":
    //         break;
    //         */
    //
    //     }
    //     $this->admin_mail = $this->admin_mail ?? [];
    //     $this->admin_mail[$error_type][] = $row;
    // }
    //
    // private function sendChangedGroupLeaderMail()
    // {
    //
    //
    //     /*
    //     any changes where (Table == "groups" && Column == "User")
    //     group = select_where(group[user] == value)
    //     if(group[user] != value)
    //     send_mail(group[user], group)
    //     */
    // }
    //
    // private function changedVisitDateMail()
    // {
    //     /*
    //     any changes where (Table == "visits" && Column == "Date")
    //     visit = select_where(visit[Date] == value)
    //     if(visit[Date] != value)
    //     send_mail(...
    //     */
    // }
    //
    // private function sendProfileUpdateReminderMail()
    // {
    //     /*
    //     any user
    //     if
    //     */
    // }
    //
    // private function cleanSQLDatabase()
    // {
    // }
    //
}
