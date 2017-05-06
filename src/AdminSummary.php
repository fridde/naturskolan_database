<?php
namespace Fridde;

use Fridde\Utility as U;
use Fridde\Entities\Group;
use Fridde\Task;

/**
* This class compiles a summary of the state of the system and the database and informs
* about potential errors and inconsistencies.
* @package naturskolan_database
*/

class AdminSummary
{
    /** @var \Fridde\Naturskolan shortcut for the Naturskolan object in the global container */
    private $N;

    /** @var array Contains group changes younger than a certain amount of time.
    *              This is a temporary variable to avoid reproducing the table.
    */
    private $recent_group_changes;

    private $method_translator = [
    "bad_mobil" 				=> "getBadMobileNumbers",
    "bus_order_outdated"        => "getOutdatedBusOrders",
    "food_changed" 				=> "getChangedFood",
    "food_order_outdated"       => "getOutdatedFoodOrders",
    "inactive_group_visit"		=> "getInactiveGroupVisits",
    "info_changed" 				=> "getChangedInfo",
    "nr_students_changed" 		=> "getChangedStudentNrs",
    "soon_last_visit" 			=> "getLoomingLastVisit",
    "too_many_students" 		=> "getWrongStudentNumbers",
    "user_profile_incomplete" 	=> "getIncompleteUserProfiles",
    "visit_not_confirmed" 		=> "getUnconfirmedVisits",
    "wrong_group_count" 		=> "getWrongGroupCounts",
    "wrong_group_leader" 		=> "getWrongGroupLeaders"

];

/**
* @return void
*/
public function __construct ()
{
    $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
}

/**
* Calls compileSummary() and sends the content to the current admin mail address
*
* @return \Guzzle\Http\Message\Response The response object returned by the request
*/
public function send()
{
    $this->summary = $this->compileSummary();
    if(empty($this->summary)){
        return true;
    }
    $data["data"]["errors"] = $this->summary;
    $data["data"]["labels"] = $this->N->getText(["admin_summary"]);
    $url = $this->N->createMailUrl("admin_summary");
    return $this->N->sendRequest($url, $data);
}


/**
* Performs a variety of checks of the whole system (visits, missing or bad information, etc)
* and saves any anomalities in the parameter *summary* using addToAdminMail().
*
* @return array The array containing the error_types as index and an array containing each
*               incident of the error occuring as a string
*/
private function compileSummary()
{
    $summary = [];
    foreach($this->method_translator as $error_type => $method_name){
        $summary[$error_type] = $this->$method_name();
    }
    return array_filter($summary);
}

private function getBadMobileNumbers()
{
    $rows = [];
    $imm_date = Task::getStartDate("immunity");
    $users_with_bad_mob = $this->N->getRepo("User")->findUsersWithBadMobil($imm_date);
    foreach($users_with_bad_mob as $u){
        $row = $u->getCompleteName() . ", ";
        $row .= $u->getSchool()->getName() . ": ";
        $row .= "Mobil: " . $u->hasMobil();
        $rows[] = $row;
    }
    return $rows;
}

private function formatGroupChange($change, string $property_name)
{
    $text = "";
    $g = $change["group"];

    $text = $g->getGradeLabel() . ", ";
    $text .= "Grupp " . ($g->getName() ?? '???') . " från " . $g->getSchool()->getName();
    $text .= ", möter oss härnast " ;
    $text .= $g->getNextVisit()->getDate()->toDateString() . ": ";
    $text .= $property_name;
    $text .= ' ändrades från "' . $change["from"] . '" till "' . $change["to"] . '"';

    return $text;
}

private function getChangedFood()
{
    return array_map(function($change){
        return $this->formatGroupChange($change, "Specialkost");
    }, $this->getRecentGroupChangesFor("Food"));
}

private function getInactiveGroupVisits()
{
    $rows = [];
    $visits = $this->N->getRepo("Visit")->findFutureVisits();
    $bad_visits = array_filter($visits, function($v){
        return $v->hasGroup() && !$v->getGroup()->isActive(); //empty groups are okay
    });
    foreach($bad_visits as $v){
        $row = "Ogiltigt besök på ";
        $row .= $v->getDate()->toDateString() . ": ";
        $row .= $v->getGroup()->getName() . " från ";
        $row .= $v->getGroup()->getSchool()->getName();
        $row .= ", " . $v->getGroup()->getGradeLabel();
        $rows[] = $row;
    }
    return $rows;
}

private function getChangedInfo()
{
    return array_map(function($change){
        return $this->formatGroupChange($change, "Information från läraren");
    }, $this->getRecentGroupChangesFor("Info"));
}

private function getChangedStudentNrs()
{
    return array_map(function($change){
        return $this->formatGroupChange($change, "Antal elever");
    }, $this->getRecentGroupChangesFor("NumberStudents"));
}

private function getLoomingLastVisit()
{
    $days_left_interval = Naturskolan::getSetting("admin_summary", "soon_last_visit");
    $last_visit_deadline = U::addDuration($days_left_interval);
    $last_visit = $this->N->getRepo("Visit")->findLastVisit();
    if(empty($last_visit) || $last_visit->getDate()->lte($last_visit_deadline)){
        $text = "Snart är sista planerade mötet med eleverna. Börja planera nästa termin!";
        $text .=  " Sista möte: ";
        $text .= empty($last_visit) ? "Inga fler besök." : $last_visit->getDate()->toDateString();
        return $text;
    }
    return false;
}

private function getWrongStudentNumbers()
{
    $rows = [];
    $range = Naturskolan::getSetting("admin_summary", "allowed_group_size");
    $ill_sized_groups = array_filter($groups, function($g) use ($range){
        $nr_students = $g->getNumberStudents();
        return $nr_students > 0 && ($nr_students < $range[0] || $nr_students > $range[1]);
    });
    foreach($ill_sized_groups as $g){
        $row = $g->getName() . ", " . $g->getGradeLabel() . ", ";
        $row .= "från " . $g->getSchool()->getName() ;
        $row .= " har " . $g->getNumberStudents() . " elever.";
        $rows[] = $row;
    }
    return $rows;
}

private function getIncompleteUserProfiles()
{
    $rows = [];
    $imm_date = Task::getStartDate("immunity");
    $incomplete_users = $this->N->getRepo("User")->findIncompleteUsers($imm_date);
    foreach($incomplete_users as $u){
        $row = $u->getCompleteName() . ", ";
        $row .= $u->getSchool()->getName() . ": ";
        $row .= "Mobil: " . ($u->hasMobil() ? $u->getMobil() : '???') . ", ";
        $row .= "Mejl: " . ($u->hasMail() ? $u->getMail() : '???') . ", ";
        $rows[] = $row;
    }
    return $rows;
}

private function getUnconfirmedVisits()
{
    $rows = [];
    $no_conf_interval = Naturskolan::getSetting("admin_summary", "no_confirmation_warning");
    $days = U::convertDuration($no_conf_interval, "d");
    $unconfirmed_visits = $this->N->getRepo("Visit")->findUnconfirmedVisitsUntil($days)->toArray();

    $visit = $entity;
    foreach($unconfirmed_visits as $visit){
        $g = $visit->getGroup();
        $u = $g->getUser();

        $row .= $visit->getDate()->toDateString() . ": ";
        $row .= $visit->getTopic()->getShortName() . " med ";
        $row .= ($g->getName() ?? '???') . " från ";
        $row .= $g->getSchool()->getName() . ". Lärare: ";
        $row .= $u->getFullName() . ", ";
        $row .= $u->getMobil() . ", " . $u->getMail();
        $rows[] = $row;
    }
    return $rows;
}

private function getWrongGroupCounts()
{
    $rows = [];
    $schools = $this->N->getRepo("School")->findAll();

    foreach($schools as $school){
        foreach(Group::GRADE_LABELS as $grade_id => $label){
            $active = $school->getNrActiveGroupsByGrade($grade_id);
            $expected = $school->getGroupNumber($grade_id);
            if($expected !== $active){
                $row = $school->getName() . " har fel antal grupper i årskurs ";
                $row .= $label . ". Det finns ";
                $row .= $active . " grupper, men det borde vara " . $expected . ". ";
                $rows[] = $row;
            }
        }
    }
    return $rows;
}

private function getWrongGroupLeaders()
{
    $rows = [];

    $groups = $this->N->getRepo("Group")->findActiveGroups();

    foreach($groups as $group){
        $id = $group->getId();
        $u = $group->getUser();
        $reasons = [];
        if(empty($u)){
            $reasons["nonexistent"] = true;
        } else {
            $reasons["inactive"] = ! $u->isActive();
            $reasons["not_teacher"] = ! $u->isRole("teacher");
            $reasons["wrong_school"] = $u->getSchool()->getId() !== $group->getSchool()->getId();

        }
        $reasons = array_keys(array_filter($reasons));
        if(count($reasons) === 0){
            continue;
        }

        $row = $group->getGradeLabel() . ": ";
        $row .= $group->getName() . " från " . $g->getSchool()->getName();
        $row .= ". Skäl: ";
        $reason_texts = [];
        foreach($reasons as $reason){
            $reason_texts[] = $this->N->getText(["admin_summary", $reason]);
        }
        $rows[] = implode(" ", $reason_texts);
    }
    return $rows;
}

private function getOutdatedBusOrders(){} //TODO: implement this function
private function getOutdatedFoodOrders(){} //TODO: implement this function

private function setRecentGroupChanges()
{
    $deadline = U::addDuration(Naturskolan::getSetting("admin_summary", "important_info_changed"));
    $recent_group_changes = [];

    $crit = [["EntityClass", "Group"], ["in", "Property", ["Food", "NumberStudents", "Info"]]];
    $group_changes = $this->N->getRepo("Change")->findNewChanges($crit);
    foreach($group_changes as $change){
        $g = $this->N->getRepo("Group")->find($change->getEntityId());
        $next_visit = $g->getNextVisit();
        if(!empty($next_visit) && $next_visit->isBefore($deadline)){
            $att = $change->getProperty();
            $method = "get" . $att;
            $old_value = $change->getOldValue();
            $new_value = $g->$method();
            if($old_value !== $new_value){
                $return = ["from" => $old_value, "to" => $new_value];
                $return["group"] = $g;
                $recent_group_changes[$att][] = $return;
            }
        }
        Task::processChange($change);
    }
    $this->recent_group_changes =  $recent_group_changes;
}

private function getRecentGroupChangesFor(string $type)
{
    if(!isset($this->recent_group_changes)){
        $this->setRecentGroupChanges();
    }
    return $this->recent_group_changes[$type] ?? [];
}


}
