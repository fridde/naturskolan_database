<?php
namespace Fridde;

use Carbon\Carbon;
use Fridde\{Naturskolan, Calendar, Mailer};
use Fridde\Entities\{User, School};
use Doctrine\Common\Collections\{ArrayCollection, Criteria};

class Task
{
    public $N;
    public $type;
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

    private function sendVisitConfirmationMail()
    {
    }

    private function sendVisitConfirmationSMS()
    {
    }

    public function sendAdminSummaryMail()
    {

        $this->compileAdminSummaryMail();

        if(empty($this->admin_mail)){
            return true;
        }
        bdump($this->admin_mail);
        /*
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
        */
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
        $this->addToAdminMail("visit_not_confirmed", $unconfirmed_visits->toArray());

        // #######################
        // ### food or number of students changed
        // #######################

        $deadline = Carbon::today()->addDays($settings["important_info_changed"]);
        $a = [];

        $group_changes = $this->N->ORM->getRepository("Change")->findGroupChanges()->toArray();
        array_walk($group_changes, function($c){
            $g = $this->N->ORM->getRepository("Group")->find($c->getEntityId());
            $next_visit = $g->getNextVisit();
            if(!empty($next_visit) && $next_visit->isBefore($deadline)){
                $att = $c->getAttribute();
                $method = "get" . $att;
                $new_value = $g->$method();
                $return = ["from" => $c->getOldValue(), "to" => $new_value];
                $return["group"] = $g;
                $a[$att][] = $return;
            }
        }, $deadline);

        $this->addToAdminMail("food_changed", $a["Food"] ?? []);
        $this->addToAdminMail("nr_students_changed", $a["NumberStudents"] ?? []);


        // #######################
        // ### user profile incomplete
        //#######################
        $immunity_start = Carbon::today()->subDays($settings["immunity_time"]);
        $annoyance_start = Carbon::today()->subDays($settings["annoyance_interval"]);
        $users = $this->N->ORM->getRepository("User")->findActiveUsers();
        $users = new ArrayCollection($users);
        $incomplete_users = $users->filter(function($u) use ($immunity_start, $annoyance_start){
            $immune = $u->wasCreatedAfter($immunity_start);
            $too_frequent = $u->lastMessageWasAfter($annoyance_start);
            return !($immune || $too_frequent) &&  !($u->hasMobil() && $u->hasMail());
        });
        if(!empty($incomplete_users)){
            $this->addToAdminMail("user_profile_incomplete", $incomplete_users->toArray());
        }



        // #######################
        // ###  last booked visit is coming soon!
        //#######################
        $last_visit_deadline =  Carbon::today()->addDays($settings["soon_last_visit"]);
        $last_visit = $this->N->ORM->getRepository("Visit")->findLastVisit();
        if(empty($last_visit) || $last_visit->getDate()->lte($last_visit_deadline)){
            $this->addToAdminMail("soon_last_visit", $last_visit);
        }

        // #######################
        // ### wrong amount of groups
        // #######################
        $schools = $this->N->ORM->getRepository("School")->findAll();
        $bad_schools = [];

        array_walk($schools, function($s){
            foreach(Group::GRADE_LABELS as $column_val => $attribute){
                $active = $s->getNrActiveGroupsByGrade($column_val);
                $method_name = "get" . $attribute;
                $expected = $s->$method_name();
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
        $groups = $this->N->ORM->getRepository("Group")->findActiveGroups();
        $groups = new ArrayCollection($groups);
        $bad_groups = $groups->filter(function($g){
            $u = $g->getUser();
            if(empty($u)){
                return true;
            }
            $inactive = ! $u->isActive();
            $not_teacher = ! $u->isRole("teacher");
            $wrong_school = $u->getSchool()->getId() !== $g->getSchool()->getId();
            return $inactive || $not_teacher || $wrong_school;
        });
        if(!empty($bad_groups)){
            $this->addToAdminMail("wrong_group_leader", $bad_groups->toArray());
        }

        // #######################
        // ### visit with inactive group
        // #######################
        $visits = $this->N->ORM->getRepository("Visit")->findFutureVisits();
        $bad_visits = $visits->filter(function($v){
            $g = $v->getGroup();
            return (empty($g) ? false : !$g->isActive()); //empty groups are okay
        });
        if(!empty($bad_visits)){
            $this->addToAdminMail("inactive_group_visit", $bad_visits->toArray());
        }

        // #######################
        // ### too many students in class
        // #######################
        $exp = Criteria::expr()->gt("NumberStudents", 33);
        $crit = Criteria::create()->where($exp);
        $large_groups = $groups->matching($crit);
        if(!empty($large_groups)){
            $this->addToAdminMail("too_many_students", $large_groups->toArray());
        }

        /*
        ---------------------------------------------------------------
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

                $row .= $visit->getDate()->toDateString() . ": Ej bekräftad ";
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
                $row .= $u->getSchool()->getName() . ", saknar kontaktuppgifter. ";
                $row .= "Mobil: " . ($u->hasMobil() ? $u->getMobil() : '???') . ", ";
                $row .= "Mejl: " . ($u->hasMail() ? $u->getMail() : '???') . ", ";
                break;
                // ###########################################
                case "wrong_group_leader":
                $g = $entity;
                $u = $g->getUser();

                $row .= $g->getGradeLabel() . ":";
                $row .= $g->getName() . " från " . $g->getSchool()->getName();
                $row .= " har fel ledare: ";
                $row .= $g->hasUser() ? '[id:' . $u->getId() . '] ' . $u->getFullName() : "Ingen!";
                break;
                // ###########################################
                case "wrong_group_count":
                // example ["råbg" => ["2" => ["expected" => 1, "active" => 2]]]
                $school_id = $key;
                $school = $this->N->ORM->getRepository("School")->find($school_id);

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
                $row .= ", är inte längre aktiv.";
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
