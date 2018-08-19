<?php

namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Carbon\Carbon;

/**
 * @Entity(repositoryClass="Fridde\Entities\GroupRepository")
 * @Table(name="groups")
 * @HasLifecycleCallbacks
 */
class Group
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(type="string", nullable=true)
     */
    protected $Name;

    /** @ManyToOne(targetEntity="User", inversedBy="Groups")
     * @Loggable     *
     */
    protected $User;

    /** @ManyToOne(targetEntity="School", inversedBy="Groups")     * */
    protected $School;

    /** @Column(type="string", nullable=true) */
    protected $Segment;

    /** @Column(type="smallint", nullable=true) */
    protected $StartYear;

    /** @Column(type="smallint", nullable=true)
     * @Loggable
     */
    protected $NumberStudents;

    /** @Column(type="text", nullable=true)
     * @Loggable
     */
    protected $Food;

    /** @Column(type="text", nullable=true)
     * @Loggable
     */
    protected $Info;

    /** @Column(type="text", nullable=true) */
    protected $Notes;

    /** @Column(type="smallint") */
    protected $Status = self::ACTIVE;

    /** @Column(type="string", nullable=true) */
    protected $LastChange;

    /** @Column(type="string", nullable=true) */
    protected $CreatedAt;

    /** @OneToMany(targetEntity="Visit", mappedBy="Group")     * */
    protected $Visits;

    public const ARCHIVED = 0;
    public const ACTIVE = 1;

    public function __construct()
    {
        $this->Visits = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->Name;
    }

    public function setName($Name)
    {
        $this->Name = trim($Name);
    }

    public function hasName()
    {
        return $this->has('Name');
    }

    /**
     * @return \Fridde\Entities\User
     */
    public function getUser()
    {
        return $this->User;
    }

    public function getUserId()
    {
        if ($this->hasUser()) {
            return $this->getUser()->getId();
        }

        return null;
    }

    public function setUser(User $User)
    {
        $this->User = $User;
    }

    public function hasUser()
    {
        return !empty($this->User);
    }

    /**
     * @return \Fridde\Entities\School
     */
    public function getSchool()
    {
        return $this->School;
    }

    public function getSchoolId()
    {
        $school = $this->getSchool();

        return empty($school) ? null : $school->getId();
    }

    public function setSchool($School)
    {
        $this->School = $School;
    }

    public function getSegment()
    {
        return $this->Segment;
    }

    public function getSegmentLabel()
    {
        $labels = self::getSegmentLabels();

        return $labels[$this->Segment] ?? null;
    }

    public static function getSegmentLabels()
    {
        return SETTINGS['segments'];
    }

    public function setSegment($Segment)
    {
        $this->Segment = $Segment;
    }

    public function isSegment($Segment)
    {
        return $this->getSegment() === (string)$Segment;
    }

    public function getStartYear()
    {
        return $this->StartYear;
    }

    public function setStartYear(int $StartYear)
    {
        $this->StartYear = $StartYear;
    }

    public function getNumberStudents()
    {
        return $this->NumberStudents;
    }

    public function setNumberStudents(int $NumberStudents)
    {
        $this->NumberStudents = $NumberStudents;
    }

    public function getFood()
    {
        return $this->Food;
    }

    public function setFood(string $Food)
    {
        $this->Food = $Food;
    }

    public function getInfo()
    {
        return $this->Info;
    }

    public function setInfo()
    {
        $this->Info = func_get_arg(0);
    }

    public function hasInfo()
    {
        return $this->has('Info');
    }

    public function getNotes()
    {
        return $this->Notes;
    }

    public function setNotes()
    {
        $this->Notes = func_get_arg(0);
    }

    public function hasNotes()
    {
        return $this->has('Notes');
    }

    public function getStatus(): int
    {
        return $this->Status;
    }

    public static function getStatusOptions()
    {
        return [
            self::ARCHIVED => 'archived',
            self::ACTIVE => 'active',
        ];
    }

    public function setStatus(int $Status)
    {
        $this->Status = $Status;
    }

    public function isActive()
    {
        return $this->getStatus() === self::ACTIVE;
    }

    public function getLastChange()
    {
        return $this->LastChange;
    }

    public function setLastChange($LastChange)
    {
        $this->LastChange = $LastChange;
    }

    public function getCreatedAt()
    {
        return $this->CreatedAt;
    }

    public function setCreatedAt($CreatedAt)
    {
        $this->CreatedAt = $CreatedAt;
    }

    public function hasVisits()
    {
        return !$this->Visits->isEmpty();
    }

    public function getVisits(): array
    {
        return $this->Visits->toArray();
    }

    public function getFutureVisits(): array
    {
        $visits = $this->getSortedVisits();

        return array_filter($visits, function(Visit $v){
            $v->getDate()->gte(Carbon::today());
        });
    }

    public function getSortedVisits(): array
    {
        $visits = $this->getVisits();
        if (empty($visits)){
            return [];
        }

        uasort($visits, function(Visit $v1, Visit $v2){
            return $v1->getDate()->lt($v2->getDate()) ? -1 : 1;
        });
        return $visits;

    }

    public function sortVisits(): void
    {
        $this->Visits = $this->getSortedVisits();
    }

    public function getNextVisit(): ?Visit
    {
        $visits = $this->getFutureVisits();

        return array_shift($visits);
    }

    public function hasNextVisit()
    {
        return !empty($this->getNextVisit());
    }

    /**
     *
     * @param  \Fridde\Entities\Group $other_group Another group to compare to.
     * @return int Returns a negative number if this group is supposed to visit before
     *             the other group, returns a positive number otherwise. Won't return 0.
     */
    public function compareVisitOrder(Group $other_group)
    {
        $v_order_1 = $this->getSchool()->getVisitOrder();
        $v_order_2 = $other_group->getSchool()->getVisitOrder();
        if ($v_order_1 !== $v_order_2) {
            return $v_order_1 - $v_order_2;
        }

        return $this->getId() - $other_group->getId();
    }

    private function has($attribute)
    {
        return !empty(trim($this->$attribute));
    }

    /** @PrePersist */
    public function prePersist($event)
    {
        $now_string = Carbon::now()->toIso8601String();
        $this->setCreatedAt($now_string);
        $this->setLastChange($now_string);
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
