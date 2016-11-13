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
