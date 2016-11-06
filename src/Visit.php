<?php
namespace Fridde;

class Visit extends Naturskolan
{
    public $information;
    public $id;
    public $date;
    public $confirmed;

    function __construct ($information = null)
    {
        if(is_array($information)){
            $this->information = $information;
            $this->id = $information["id"];
        }
        else {
            $this->id = $information;
        }
    }

    private function setInformation()
    {
        if(!isset($this->information)){
            $visits = $this->getTable("visits");
            $this->information = U::filterFor($visits, ["id", $this->id]);
        }
    }

    private function setDate()
    {
        $this->setInformation();
        $this->date = new C($this->information["Date"]);
    }

    public function daysLeft()
    {
        return $this->now->diffInDays($this->date);
    }

    public function isInFuture()
    {
        return $this->daysLeft() >= 0;
    }

    public function isConfirmed()
    {
        $this->confirmed = $this->confirmed ?? in_array($this->information["Confirmed"], [1, true, "true", "yes"]);
        return $this->confirmed;
    }
}
