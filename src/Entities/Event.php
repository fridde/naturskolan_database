<?php
namespace Fridde\Entities;

use Carbon\Carbon;

/**
* @Entity(repositoryClass="Fridde\Entities\EventRepository")
* @Table(name="events")
* @HasLifecycleCallbacks
*/
class Event
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @Column(type="string") */
    protected $StartDate;

    /** @Column(type="string", nullable=true) */
    protected $StartTime;

    /** @Column(type="string", nullable=true) */
    protected $EndDate;

    /** @Column(type="string", nullable=true) */
    protected $EndTime;

    /** @Column(type="string") */
    protected $Title;

    /** @Column(type="text", nullable=true) */
    protected $Description;

    /** @Column(type="string", nullable=true) */
    protected $Location;

    /** @Column(type="string") */
    protected $LastChange;

    public const AUTO_CREATED = ['LastChange'];


    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Carbon
     */
    public function getStartDate()
    {
        return new Carbon($this->StartDate);
    }

    public function getStartDateString()
    {
        return $this->getStartDate()->toDateString();
    }

    /**
     * @param string $StartDate
     */
    public function setStartDate($StartDate)
    {
        $this->StartDate = $StartDate;
    }

    /**
     * @return string
     */
    public function getStartTime()
    {
        return $this->StartTime;
    }

    /**
     * @param string $StartTime
     */
    public function setStartTime($StartTime)
    {
        $this->StartTime = $StartTime;
    }

    /**
     * @return Carbon
     */
    public function getEndDate()
    {
        if(!empty($this->EndDate)){
            return new Carbon($this->EndDate);
        }
        return $this->EndDate;
    }

    public function getEndDateString()
    {
        if(! $this->hasEndTime()){
            return '';
        }
        return $this->getEndDate()->toDateString();
    }

    public function hasEndDate()
    {
        return !empty($this->getEndDate());
    }

    /**
     * @param string $EndDate
     */
    public function setEndDate($EndDate)
    {
        $this->EndDate = $EndDate;
    }

    /**
     * @return string
     */
    public function getEndTime()
    {
        return $this->EndTime;
    }

    public function hasEndTime()
    {
        return !empty($this->getEndTime());
    }

    /**
     * @param string $EndTime
     */
    public function setEndTime($EndTime)
    {
        $this->EndTime = $EndTime;
    }

    public function getStartHour(): int
    {
        return (int) substr($this->getStartTime(), 0, -2);
    }

    public function getStartMinute(): int
    {
        return (int) substr($this->getStartTime(), -2);
    }
    
    public function getEndHour(): int
    {
        return (int) substr($this->getEndTime(), 0, -2);
    }

    public function getEndMinute(): int
    {
        return (int) substr($this->getEndTime(), -2);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->Title;
    }

    /**
     * @param string $Title
     */
    public function setTitle($Title)
    {
        $this->Title = $Title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * @param string $Description
     */
    public function setDescription($Description)
    {
        $this->Description = $Description;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->Location;
    }

    /**
     * @param string $Location
     */
    public function setLocation($Location)
    {
        $this->Location = $Location;
    }

    /**
     * @return string
     */
    public function getLastChange()
    {
        return $this->LastChange;
    }

    /**
     * @param string $LastChange
     */
    public function setLastChange($LastChange = null)
    {
        $this->LastChange = $LastChange ?? Carbon::now()->toIso8601String();
    }

    public function isWholeDay()
    {
        return empty($this->getEndTime()) || empty($this->getStartTime());
    }

    public function isValid()
    {
        return ! (empty($this->getTitle()) || empty($this->getStartDate()));
    }



    /** @PrePersist */
    public function prePersist()
    {
        $this->setLastChange();
    }
    /** @PreUpdate */
    public function preUpdate()
    {
        $this->setLastChange();
    }
    /** @PreRemove */
    public function preRemove(){}

}
