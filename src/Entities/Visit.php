<?php

namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Carbon\Carbon;
use Fridde\Update;

/**
 * @Entity(repositoryClass="Fridde\Entities\VisitRepository")
 * @Table(name="visits")
 * @HasLifecycleCallbacks
 */
class Visit
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @ManyToOne(targetEntity="Group", inversedBy="Visits")   * */
    protected $Group;

    /** @Column(type="string") */
    protected $Date;

    /** @ManyToOne(targetEntity="Topic", inversedBy="Visits")   * */
    protected $Topic;

    /** This is the owning side. The visit has many colleagues (=users)
     * @ManyToMany(targetEntity="User", inversedBy="Visits")
     * @JoinTable(name="Colleagues_Visits")
     */
    protected $Colleagues;

    /** @Column(type="integer") */
    protected $Confirmed = 0;

    /** @Column(type="string", nullable=true) */
    protected $Time;

    const STATUS = [0 => "unconfirmed", 1 => "confirmed"];

    public function __construct()
    {
        $this->Colleagues = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Fridde\Entities\Group
     */
    public function getGroup()
    {
        return $this->Group;
    }

    /**
     * @return int|null
     */
    public function getGroupId()
    {
        if ($this->hasGroup()) {
            return $this->getGroup()->getId();
        }

        return null;

    }

    public function setGroup($Group)
    {
        $this->Group = $Group;
    }

    public function hasGroup()
    {
        return !empty($this->getGroup());
    }

    public function getDate()
    {
        if (is_string($this->Date)) {
            $this->Date = new Carbon($this->Date);
        }

        return $this->Date;
    }

    public function getDateString()
    {
        return $this->getDate()->toDateString();
    }

    /**
     * @param string|Carbon $Date
     * @param string $input_format
     */
    public function setDate($Date, string $input_format = 'Y-m-d')
    {
        if($Date instanceof Carbon){
           $Date = $Date->toDateString();
        } else {
            $Date = Carbon::createFromFormat($input_format, $Date)->toDateString(); // validates, too
        }
        $this->Date = $Date;
    }

    /**
     * @return \Fridde\Entities\Topic
     */
    public function getTopic()
    {
        return $this->Topic;
    }

    public function getTopicId()
    {
        return $this->getTopic()->getId();
    }

    public function setTopic($Topic)
    {
        $this->Topic = $Topic;
    }

    public function getColleagues()
    {
        return $this->Colleagues ?? [];
    }

    public function getColleaguesIdArray()
    {
        return array_map(
            function ($col) {
                return $col->getId();
            },
            $this->getColleagues()->toArray()
        );
    }

    public function getColleaguesIdAsString()
    {
        return implode(",", $this->getColleaguesIdArray());
    }

    public function addColleague($Colleague)
    {
        $this->Colleagues->add($Colleague);
        $Colleague->addVisit($this);
    }

    public function setColleagues($colleagues)
    {
        // TODO: implement this function

    }

    public function removeColleague($Colleague)
    {
        $this->Colleagues->removeElement($Colleague);
        $Colleague->removeVisit($this);
    }

    public function getColleaguesAsAcronymString()
    {
        return implode(
            ',',
            array_map(
                function ($col) {
                    return $col->getAcronym() ?: $col->getId();
                },
                $this->getColleagues()->toArray()
            )
        );
    }

    public function isConfirmed()
    {
        return boolval($this->Confirmed);
    }

    public function getConfirmedOptions()
    {
        return [1 => ""];
    }

    public function getConfirmed()
    {
        return $this->Confirmed;
    }

    public function setConfirmed($Confirmed)
    {
        $this->Confirmed = intval($Confirmed);
    }

    public function getTime()
    {
        return $this->Time;
    }

    public function getTimeAsArray()
    {
        if (!$this->hasTime()) {
            return null;
        }

        return $this->timeStringToArray($this->Time);
    }

    public function setTime($Time)
    {
        $this->Time = $Time;
    }

    public function hasTime()
    {
        return !empty($this->Time);
    }

    public function timeStringToArray($time_string)
    {
        $parts = explode("-", $time_string);
        $parts = preg_replace('%\D%', '', $parts); // remove all non-digits
        $parts = array_map(
            function ($v) {
                $v = str_pad($v, 4, "0", STR_PAD_LEFT); // "730" becomes "0730"
                $h_and_m["hh"] = substr($v, 0, 2);
                $h_and_m["mm"] = substr($v, 2, 2);

                return $h_and_m;
            },
            $parts
        );
        $return["start"] = $parts[0];
        $return["end"] = $parts[1] ?? null;

        return $return;
    }

    private function isBeforeOrAfter($date, $beforeOrAfter = "after")
    {
        if (is_string($date)) {
            $date = new Carbon($date);
        }
        if ($beforeOrAfter == "before") {
            return $this->getDate()->lte($date);
        } elseif ($beforeOrAfter == "after") {
            return $this->getDate()->gte($date);
        } else {
            throw new \Exception("The comparison ".$beforeOrAfter." is not defined.");
        }
    }

    public function isAfter($date)
    {
        return $this->isBeforeOrAfter($date, "after");
    }

    public function isBefore($date)
    {
        return $this->isBeforeOrAfter($date, "before");
    }

    public function isInFuture()
    {
        return $this->isAfter(Carbon::today());
    }

    public function isLessThanNrDaysAway($days)
    {
        return $this->isBefore(Carbon::today()->addDays($days));
    }

    public function needsBus()
    {
        if (!$this->hasGroup()) {
            return false;
        }
        $bus_id = $this->getTopic()->getLocation()->getBusId();
        $bus_rule = $this->getGroup()->getSchool()->getBusRule();
        $location_value = empty($bus_id) ? 0 : 2 ** $bus_id;

        return $bus_rule & $location_value; // bitwise AND !
    }

    public function needsFood()
    {
        if (!$this->hasGroup()) {
            return false;
        }

        return $this->getTopic()->getFoodInstructions() === 0;

    }

    /** @PrePersist */
    public function prePersist()
    {
    }

    /** @PreUpdate */
    public function preUpdate($event)
    {
        $trackables = ["Group", "Date", "Time"];
        (new Update)->logChange($event, $trackables);
    }

    /** @PreRemove */
    public function preRemove()
    {
    }

}
