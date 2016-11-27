<?php
namespace Fridde\Entities;

class Group extends Entity
{
    public $visits;
    public $future_visits;
    public $school;
    public $grade_labels = ["2" => "åk 2/3", "5" => "åk 5", "fbk16" => "FBK F-6", "fbk79" => "FBK 7-9"];
    const INACTIVE = 0;
    const ACTIVE = 1; 

    private function setVisits($renew = false)
    {
        if($renew || !isset($this->visits) || !isset($this->future_visits)){
            $visits = U::filterFor($this->getTable("visits"), ["Group", $this->id], false);
            $this->visits = U::orderBy($visits, "Date", "datestring");
            $this->future_visits = U::filterFor($this->visits, ["Date", $this->_NOW_UNIX_, "after"]);
        }
    }

    private function setSchool()
    {
        $this->setInformation();
        $this->school = $this->school ?? U::getById($this->getTable("schools"),
            $this->information["School"]);
    }

    public function getSchool($key = null)
    {
        $this->setSchool();
        return $this->school[$key] ?? $this->school;
    }

    public function getInfo($key = null)
    {
        $this->information = $this->information ??  U::getById($this->getTable("groups"), $this->id);
        return $this->information[$key] ?? $this->information;
    }

    public function getGradeLabel()
    {
        $this->setInformation();
        return $this->grade_labels[$this->pick("Grade")];
    }

    public function getNextVisit()
    {
        $this->setVisits();
        if(!empty($this->future_visits)){
            $next_visit = new Visit(reset($this->future_visits));
            return $next_visit;
        } else {
            return false;
        }
    }

}
