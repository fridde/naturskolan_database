<?php

namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Carbon\Carbon;
use Doctrine\ORM\Mapping AS ORM;
use Fridde\Annotations\Loggable;

/**
 * @ORM\Entity(repositoryClass="Fridde\Entities\GroupRepository")
 * @ORM\Table(name="groups")
 * @ORM\HasLifecycleCallbacks
 */
class Group
{
    /** @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="string", nullable=true)
     */
    protected $Name;

    /** @ORM\ManyToOne(targetEntity="User", inversedBy="Groups")
     * @Loggable     *
     */
    protected $User;

    /** @ORM\ManyToOne(targetEntity="School", inversedBy="Groups")     * */
    protected $School;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Segment;

    /** @ORM\Column(type="smallint", nullable=true) */
    protected $StartYear;

    /** @ORM\Column(type="smallint", nullable=true)
     * @Loggable
     */
    protected $NumberStudents;

    /** @ORM\Column(type="text", nullable=true)
     * @Loggable
     */
    protected $Food;

    /** @ORM\Column(type="text", nullable=true)
     * @Loggable
     */
    protected $Info;

    /** @ORM\Column(type="smallint") */
    protected $Status = self::ACTIVE;

    /** @ORM\Column(type="string", nullable=true) */
    protected $LastChange;

    /** @ORM\Column(type="string", nullable=true) */
    protected $CreatedAt;

    /** @ORM\OneToMany(targetEntity="Visit", mappedBy="Group")
     * @ORM\OrderBy({"Date"="ASC"})
     **/
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
        return !empty($this->Name);
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

    public function isSegment(string $Segment)
    {
        return $this->getSegment() === $Segment;
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
        return !empty($this->Info);
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

        return array_filter(
            $visits,
            function (Visit $v) {
                return $v->isActive() && $v->getDate()->gte(Carbon::today());
            }
        );
    }


    /**
     * @return array Visit[]
     */
    public function getSortedVisits(): array
    {
        $visits = $this->getVisits();

        if (empty($visits)) {
            return [];
        }

        uasort(
            $visits,
            function (Visit $v1, Visit $v2) {
                return $v1->getDate()->lt($v2->getDate()) ? -1 : 1;
            }
        );

        return $visits;
    }

    public function getSortedActiveVisits(): array
    {
        return array_filter(
            $this->getSortedVisits(),
            function (Visit $v) {
                return $v->isActive();
            }
        );
    }

    /*
    public function sortVisits(): void
    {
        $this->Visits = $this->getSortedVisits();
    }
    */

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
     * @param \Fridde\Entities\Group $other_group Another group to compare to.
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

    /** @ORM\PrePersist */
    public function prePersist($event)
    {
        $now_string = Carbon::now()->toIso8601String();
        $this->setCreatedAt($now_string);
        $this->setLastChange($now_string);
    }

    /** @ORM\PreUpdate */
    public function preUpdate($event)
    {
        $this->setLastChange(Carbon::now()->toIso8601String());
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
    }

}
