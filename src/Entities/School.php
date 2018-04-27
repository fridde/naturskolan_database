<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Fridde\Entities\Group;
use Doctrine\Common\Collections\ArrayCollection;
use Fridde\Utility;
use Monolog\Handler\Curl\Util;

/**
 * @Entity(repositoryClass="Fridde\Entities\SchoolRepository")
 * @Table(name="schools")
 * @HasLifecycleCallbacks
 */
class School
{
    /** @Id @Column(type="string") */
    protected $id;

    /** @Column(type="string") */
    protected $Name;

    /** @Column(type="json_array", nullable=true) */
    protected $GroupNumbers;

    /** @Column(type="string", nullable=true) */
    protected $Coordinates;

    /** @Column(type="integer", nullable=true) */
    protected $VisitOrder;

    /** @Column(type="integer") */
    protected $BusRule = 0;

    /** @OneToMany(targetEntity="Group", mappedBy="School") */
    protected $Groups;

    /** @OneToMany(targetEntity="User", mappedBy="School") */
    protected $Users;

    public function __construct()
    {
        $this->Groups = new ArrayCollection();
        $this->Users = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getIdAsInteger()
    {
        return Utility::stringToInt($this->getId());
    }

    public function getName()
    {
        return $this->Name;
    }

    public function setName($Name)
    {
        $this->Name = $Name;
    }

    public function getGroupNumbersAsString()
    {
        return json_encode($this->getGroupNumbers());
    }

    /**
     * @return array|null
     */
    public function getGroupNumbers()
    {
        return $this->GroupNumbers;
    }

    public function getGroupNumber($grade, int $start_year = null)
    {
        $start_year = $start_year ?? Carbon::today()->year;
        $groupNumbers = $this->getGroupNumbers();

        return $groupNumbers[$start_year][$grade] ?? 0;
    }

    public function setGroupNumbers(array $GroupNumbers)
    {
        $this->GroupNumbers = $GroupNumbers;
    }

    /**
     * @param string $grade
     * @param int $value
     * @param int|null $start_year
     */
    public function setGroupNumber(string $grade, int $value = 0, int $start_year = null)
    {
        $start_year = $start_year ?? Carbon::today()->year;

        $current_values = $this->getGroupNumbers() ?? [];
        $current_values[$start_year][$grade] = $value;
        $this->setGroupNumbers($current_values);
    }

    public function getCoordinates()
    {
        return $this->Coordinates;
    }

    public function setCoordinates($Coordinates)
    {
        $this->Coordinates = $Coordinates;
    }

    public function getVisitOrder()
    {
        return $this->VisitOrder;
    }

    public function setVisitOrder($VisitOrder)
    {
        $this->VisitOrder = $VisitOrder;
    }

    public function getBusRule()
    {
        return $this->BusRule;
    }

    public function setBusRule($BusRule)
    {
        $this->BusRule = $BusRule;
    }

    public function getGroups()
    {
        return $this->Groups->toArray();
    }


    public function getUsers()
    {
        return $this->Users;
    }

    public function addUser($User)
    {
        $this->Users->add($User);
    }

    /**
     * @param $grade
     * @param mixed $start_year If null, the current year is assumed. If false, all years are included
     * @return array
     */
    public function getActiveGroupsByGradeAndYear($grade, $start_year = null)
    {
        $start_year = $start_year ?? Carbon::today()->year;

        return array_filter(
            $this->getGroups(),
            function (Group $g) use ($grade, $start_year) {
                $cond1 = $start_year !== false ? $g->getStartYear() == $start_year : true;
                $cond2 = $g->getGrade() === $grade;
                $cond3 = $g->isActive();

                return $cond1 && $cond2 && $cond3;
            }
        );
    }

    public function hasGrade($grade)
    {
        return ! empty($this->getActiveGroupsByGradeAndYear($grade, false));
    }

    public function getGradesAvailable($withLabels = false)
    {
        $available_grades = array_filter(
            Group::getGradeLabels(),
            function ($k) {
                return $this->hasGrade($k);
            },
            ARRAY_FILTER_USE_KEY
        );

        return $withLabels ? $available_grades : array_keys($available_grades);
    }

    public function getNrActiveGroupsByGradeAndYear($grade, $start_year = null)
    {
        return count($this->getActiveGroupsByGradeAndYear($grade, $start_year));
    }

    public function isNaturskolan()
    {
        return $this->getId() === 'natu';
    }

    /** @PrePersist */
    public function prePersist()
    {
        $GLOBALS['CONTAINER']->get('Naturskolan')->setCalendarTo('dirty');
    }

    /** @PreUpdate */
    public function preUpdate()
    {
        $GLOBALS['CONTAINER']->get('Naturskolan')->setCalendarTo('dirty');
    }

    /** @PreRemove */
    public function preRemove()
    {
    }

}
