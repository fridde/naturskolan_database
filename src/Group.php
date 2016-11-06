<?php
namespace Fridde;

class Group extends Naturskolan
{
    public $id;
    public $information;
    public $visits;
    public $future_visits;

    function __construct ($id = null)
    {
        $this->id = $id;
    }

    private function setVisits($renew = false)
    {
        if($renew || !isset($this->visits) || !isset($this->future_visits)){
            $visits = U::filterFor($this->getTable("visits"), ["id", $this->id], false);
            $this->visits = U::orderBy($visits, "Date", "datestring");
            $this->future_visits = U::filterFor($this->visits, ["Date", $this->_NOW_UNIX_, "after"]);
        }
    }

    public function getInfo($key = null)
    {
        $this->information = $this->information ??  U::filterFor($this->getTable("groups"), ["id", $this->id]);
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
