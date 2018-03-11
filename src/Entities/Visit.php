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

    /** @Column(type="string", nullable=true) */
    protected $LastChange;

    /** @ManyToOne(targetEntity="Topic", inversedBy="Visits")   * */
    protected $Topic;

    /** This is the owning side. The visit has many colleagues (=users)
     * @ManyToMany(targetEntity="User", inversedBy="Visits")
     * @JoinTable(name="colleagues_visits")
     */
    protected $Colleagues;

    /** @Column(type="integer") */
    protected $Confirmed = self::UNCONFIRMED;

    /** @Column(type="string", nullable=true) */
    protected $Time;

    /** @Column(type="integer") */
    protected $Status = self::ACTIVE;

    /** @Column(type="integer", nullable=true) */
    protected $BusIsBooked;

    /** @Column(type="integer", nullable=true) */
    protected $FoodIsBooked;

    public const AUTO_CREATED = ['Confirmed', 'Status'];

    public const ARCHIVED = 0;
    public const ACTIVE = 1;
    public const UNCONFIRMED = 0;
    public const CONFIRMED = 1;

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
        if ($Date instanceof Carbon) {
            $Date = $Date->toDateString();
        } else {
            $Date = Carbon::createFromFormat($input_format, $Date)->toDateString(); // validates, too
        }
        $this->Date = $Date;
    }

    public function getLastChange()
    {
        return $this->LastChange;
    }

    public function setLastChange($LastChange)
    {
        $this->LastChange = $LastChange;
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

    public function hasColleagues()
    {
        return !empty($this->getColleagues()->toArray());
    }

    public function getColleagues()
    {
        return $this->Colleagues;
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

    public function addColleague(User $Colleague)
    {
        $this->Colleagues->add($Colleague);
        $Colleague->addVisit($this);
    }

    public function setColleagues(array $colleagues)
    {
        $this->Colleagues = new ArrayCollection();
        foreach ($colleagues as $c) {
            if (empty($c) && $c !== 0) { // horrible hack because the POST from work_schedule removes any empty array, so a falsy value has to be added, that should be ignored here
                continue;
            }
            if (!$c instanceof User && !is_null($c)) {
                $c = $GLOBALS['CONTAINER']->get('Naturskolan')->ORM->getRepository('User')->find($c);
            }
            $this->addColleague($c);
        }
    }

    public function removeColleague(User $Colleague)
    {
        $this->Colleagues->removeElement($Colleague);
        $Colleague->removeVisit($this);
    }

    public function getColleaguesAsAcronymString()
    {
        return implode(
            '+',
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
        return (bool)$this->Confirmed;
    }

    public function getConfirmed()
    {
        return $this->Confirmed ?? self::UNCONFIRMED;
    }

    public function setConfirmed($Confirmed)
    {
        $this->Confirmed = (int)$Confirmed;
    }

    public function getConfirmedOptions()
    {
        return [1 => '']; // This is necessary to create the yes or no checkbox
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
        $parts = explode('-', $time_string);
        $parts = preg_replace('%\D%', '', $parts); // remove all non-digits
        $parts = array_map(
            function ($v) {
                $v = str_pad($v, 4, '0', STR_PAD_LEFT); // '730' becomes '0730'
                $h_and_m['hh'] = substr($v, 0, 2);
                $h_and_m['mm'] = substr($v, 2, 2);

                return $h_and_m;
            },
            $parts
        );
        $return['start'] = $parts[0];
        $return['end'] = $parts[1] ?? null;

        return $return;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->Status ?? self::ACTIVE;
    }

    /**
     * @param mixed $Status
     */
    public function setStatus($Status)
    {
        $this->Status = $Status;
    }


    private function isBeforeOrAfter($date, $beforeOrAfter = 'after')
    {
        if (is_string($date)) {
            $date = new Carbon($date);
        }
        if ($beforeOrAfter === 'before') {
            return $this->getDate()->lte($date);
        } elseif ($beforeOrAfter === 'after') {
            return $this->getDate()->gte($date);
        } else {
            throw new \Exception('The comparison '.$beforeOrAfter.' is not defined.');
        }
    }

    public function isAfter($date)
    {
        return $this->isBeforeOrAfter($date, 'after');
    }

    public function isBefore($date)
    {
        return $this->isBeforeOrAfter($date, 'before');
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

    public function needsFoodOrder()
    {
        if (!$this->hasGroup()) {
            return false;
        }

        return $this->getTopic()->getFoodOrder() !== Topic::FOOD_NONE;
    }

    /**
     * @return mixed
     */
    public function getBusIsBooked()
    {
        return (bool)$this->BusIsBooked;
    }

    /**
     * @param mixed $BusIsBooked
     */
    public function setBusIsBooked($BusIsBooked)
    {
        $this->BusIsBooked = (int)$BusIsBooked;
    }

    /**
     * @return mixed
     */
    public function getFoodIsBooked()
    {
        return (bool)$this->FoodIsBooked;
    }

    /**
     * @param mixed $FoodIsBooked
     */
    public function setFoodIsBooked($FoodIsBooked)
    {
        $this->FoodIsBooked = (int)$FoodIsBooked;
    }

    public function getLabel(string $pattern = 'DTGSU')
    {
        $label = '';
        $topic_name = $this->getTopic()->getShortName();
        $has_group = $this->hasGroup();

        $include = [
            'date' => 'D',
            'topic' => 'T',
            'group' => 'G',
            'school' => 'S',
            'user' => 'U',
        ];

        array_walk($include, function (&$v) use ($pattern){
            $v = strpos($pattern, $v) !== false;
        });

        if ($include['date']) {
            $label .= '['.$this->getDateString().'] ';
        }
        if ($include['topic']) {
            if ($has_group) {
                $label .= $topic_name;
            } else {
                $label .= 'Reservtillfälle: '.$topic_name;
            }
        }

        if ($has_group && $include['group']) {
            $label .= ' med '.$this->getGroup()->getName();
        }
        if($has_group && $include['school']){
            $label .= ' från ' . $this->getGroup()->getSchool()->getName();
        }
        if ($has_group && $include['user'] && $this->getGroup()->hasUser()) {
            $label .= ' ('.$this->getGroup()->getUser()->getShortName().')';
        }

        return $label;
    }


    /** @PrePersist */
    public function prePersist()
    {
        $this->setLastChange(Carbon::now()->toIso8601String());
    }

    /** @PreUpdate */
    public function preUpdate($event)
    {
        $this->setLastChange(Carbon::now()->toIso8601String());
    }

    /** @PreRemove */
    public function preRemove()
    {
    }

}
