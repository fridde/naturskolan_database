<?php
namespace Fridde;

class Group extends Entity
{
    public $visits;
    public $future_visits;
    public $school;

    function __construct ()
    {
        parent::__construct(func_get_arg(0));
        $this->$corresponding_table = "groups";
    }

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
