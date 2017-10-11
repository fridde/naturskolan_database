<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Fridde\Entities\Group;
use Doctrine\Common\Collections\ArrayCollection;

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

    /** @OneToMany(targetEntity="Cookie", mappedBy="School") */
    protected $Hashes;

    public function __construct()
    {
        $this->Groups = new ArrayCollection();
        $this->Users = new ArrayCollection();
        $this->Hashes = new ArrayCollection();
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
        $int = "";
        $all_letters = array_merge(range("a", "z"), ["åäö"]);
        $d = (int)(log(max(array_keys($all_letters)), 10) + 1);
        $id_array = preg_split('//u', $this->getId(), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($id_array as $ch) {
            $id = array_search(strtolower($ch), $all_letters);
            $int .= str_pad($id, $d, "0", STR_PAD_LEFT);
        }

        return intval($int);
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

    public function getGroupNumber($grade, $start_year = null)
    {
        $start_year = $start_year ?? Carbon::today()->year;
        $groupNumbers = $this->getGroupNumbers();

        return $groupNumbers[$start_year][$grade];
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
        return $this->Groups;
    }

    public function getHashes()
    {
        return $this->Hashes;
    }

    public function setHashes($Hashes)
    {
        $this->Hashes = $Hashes;
    }

    public function addPassword($Hash)
    {
        $this->Hashes->add($Hash);
    }

    public function getUsers()
    {
        return $this->Users;
    }

    public function addUser($User)
    {
        $this->Users->add($User);
    }

    public function getActiveGroupsByGrade($grade)
    {
        return $this->getGroups()->filter(
            function ($g) use ($grade) {
                return $g->getGrade() == $grade && $g->isActive();
            }
        );
    }

    public function hasGrade($grade)
    {
        return !$this->getActiveGroupsByGrade($grade)->isEmpty();
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

    public function getNrActiveGroupsByGrade($grade)
    {
        return count($this->getActiveGroupsByGrade($grade));
    }

    public function isNaturskolan()
    {
        return $this->getId() == "natu";
    }

    /** @PrePersist */
    public function prePersist()
    {
        $GLOBALS["CONTAINER"]->get("Naturskolan")->setCalendarTo('dirty');
    }

    /** @PreUpdate */
    public function preUpdate()
    {
        $GLOBALS["CONTAINER"]->get("Naturskolan")->setCalendarTo('dirty');
    }

    /** @PreRemove */
    public function preRemove()
    {
    }

}
