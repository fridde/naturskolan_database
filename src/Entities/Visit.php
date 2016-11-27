<?php
namespace Fridde\Entities;

class Visit extends Entity
{
    public $date;
    public $confirmed;

    private function setDate()
    {
        $this->setInformation();
        $this->date = new C($this->information["Date"]);
    }

    public function getGroup()
    {
        return $this->getAsObject("Group");
    }

    public function daysLeft()
    {
        return $this->_NOW_->diffInDays($this->date);
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

    public function getColleagues()
    {
        $this->setInformation();
        if($this->has("Colleague")){
            $colleagues = explode(",", $this->pick("Colleague"));
            return array_map(function($id){return new User($id);}, $colleagues);
        } else {
            return [];
        }

    }
}
