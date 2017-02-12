<?php
namespace Fridde\Entities;

use \Fridde\{Utility as U};
use Doctrine\Common\Collections\ArrayCollection;

/**
* @Entity(repositoryClass="Fridde\Entities\SchoolRepository")
* @Table(name="schools")
*/
class School
{
    /** @Id @Column(type="string") */
    protected $id;

    /** @Column(type="string") */
    protected $Name;

    /** @Column(type="integer") */
    protected $GroupsAk2;

    /** @Column(type="integer") */
    protected $GroupsAk5;

    /** @Column(type="integer") */
    protected $GroupsFbk;

    /** @Column(type="string") */
    protected $Coordinates;

    /** @Column(type="integer") */
    protected $VisitOrder;

    /** @OneToMany(targetEntity="Group", mappedBy="School") */
    protected $Groups;

    /** @OneToMany(targetEntity="User", mappedBy="School") */
    protected $Users;

    /** @OneToMany(targetEntity="Password", mappedBy="School") */
    protected $Passwords;

    public function __construct() {
        $this->Groups = new ArrayCollection();
        $this->Users = new ArrayCollection();
        $this->Passwords = new ArrayCollection();
    }

    public function getId(){return $this->id;}
    public function setId($id){$this->id = $id;}
    public function getName(){return $this->Name;}
    public function setName($Name){$this->Name = $Name;}
    public function getGroupsAk2(){return $this->GroupsAk2;}
    public function setGroupsAk2($GroupsAk2){$this->GroupsAk2 = $GroupsAk2;}
    public function getGroupsAk5(){return $this->GroupsAk5;}
    public function setGroupsAk5($GroupsAk5){$this->GroupsAk5 = $GroupsAk5;}
    public function getGroupsFbk(){return $this->GroupsFbk;}
    public function setGroupsFbk($GroupsFbk){$this->GroupsFbk = $GroupsFbk;}
    public function getCoordinates(){return $this->Coordinates;}
    public function setCoordinates($Coordinates){$this->Coordinates = $Coordinates;}
    public function getVisitOrder(){return $this->VisitOrder;}
    public function setVisitOrder($VisitOrder){$this->VisitOrder = $VisitOrder;}
    public function getGroups(){return $this->Groups;}
    public function getPasswords(){return $this->Passwords;}
    public function setPasswords($Passwords){$this->Passwords = $Passwords;}
    public function addPassword($Password){$this->Passwords->add($Password);}
    public function getUsers(){return $this->Users;}
    public function addUser($User){$this->Users->add($User);}

    public function getActiveGroupsByGrade($grade)
    {
        return $this->getGroups()->filter(function($g) use ($grade){
            return $g->getGrade() == $grade && $g->isActive();
        });
    }

    public function hasGrade($grade)
    {
        return !$this->getActiveGroupsByGrade($grade)->isEmpty();
    }

    public function getGradesAvailable($withLabels = false)
    {
        $available_grades = array_filter(Group::GRADE_LABELS, function($k){
            return $this->hasGrade($k);
        }, ARRAY_FILTER_USE_KEY);

        return $withLabels ? $available_grades : array_keys($available_grades);
    }

    public function getNrActiveGroupsByGrade($grade)
    {
        return count($this->getActiveGroupsByGrade($grade));
    }

}
