<?php

namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Carbon\Carbon;
use Fridde\Update;

/**
 * @Entity(repositoryClass="Fridde\Entities\GroupRepository")
 * @Table(name="groups")
 * @HasLifecycleCallbacks
 */
class Group
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(type="string", nullable=true) */
    protected $Name;

    /** @ManyToOne(targetEntity="User", inversedBy="Groups")     * */
    protected $User;

    /** @ManyToOne(targetEntity="School", inversedBy="Groups")     * */
    protected $School;

    /** @Column(type="string", nullable=true) */
    protected $Grade;

    /** @Column(type="integer", nullable=true) */
    protected $StartYear;

    /** @Column(type="integer", nullable=true) */
    protected $NumberStudents;

    /** @Column(type="text", nullable=true) */
    protected $Food;

    /** @Column(type="text", nullable=true) */
    protected $Info;

    /** @Column(type="text", nullable=true) */
    protected $Notes;

    /** @Column(type="integer") */
    protected $Status = self::ACTIVE;

    /** @Column(type="string", nullable=true) */
    protected $LastChange;

    /** @Column(type="string") */
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

    public function setName($Name = null)
    {
        if (empty($Name)) {
            $alias_names = \Fridde\Naturskolan::getSetting('defaults', 'placeholder', 'animals');
            $Name = 'Grupp '.$alias_names[mt_rand(0, count($alias_names) - 1)];
        }
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
        return $this->getSchool()->getId();
    }

    public function setSchool($School)
    {
        $this->School = $School;
    }

    public function getGrade()
    {
        return $this->Grade;
    }

    public function getGradeLabel()
    {
        $labels = self::getGradeLabels();

        return $labels[$this->Grade];
    }

    public static function getGradeLabels()
    {
        return SETTINGS['grades'];
    }

    public function setGrade($Grade)
    {
        $this->Grade = $Grade;
    }

    public function isGrade($Grade)
    {
        return $this->getGrade() === (string)$Grade;
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

    public function getStatus()
    {
        return $this->Status;
    }

    public function getStatusOptions()
    {
        return array_flip(
            [
                'archived' => self::ARCHIVED,
                'active' => self::ACTIVE,
            ]
        );
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

    public function getVisits()
    {
        return $this->Visits;
    }

    public function getFutureVisits()
    {
        return $this->getVisitsAfter(Carbon::today());
    }

    public function getVisitsAfter($date = null, $ordered = true)
    {

        if ($ordered) {
            $this->sortVisits();
        }
        if (is_string($date)) {
            $date = new Carbon($date);
        } elseif (empty($date)) {
            $date = Carbon::today();
        }

        return $this->getVisits()->filter(
            function ($v) use ($date) {
                return $v->isAfter($date);
            }
        );
    }

    public function getSortedVisits()
    {
        $this->sortVisits();

        return $this->getVisits();
    }

    private function sortVisits()
    {
        if ($this->Visits->isEmpty()) {
            return $this->Visits; //empty collection
        }
        $visits = $this->Visits->getIterator();

        $visits->uasort(
            function ($a, $b) {
                return ($a->getDate()->lt($b->getDate())) ? -1 : 1;
            }
        );
        $this->Visits = new ArrayCollection(iterator_to_array($visits));

        return $this->Visits;
    }

    public function getNextVisit()
    {
        $visits = $this->sortVisits($this->getFutureVisits());
        if (!$visits->isEmpty()) {
            return $visits->first();
        } else {
            return null;
        }
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
