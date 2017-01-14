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

    const GRADES_COLUMN = ["2" => "GroupsAk2", "5" => "GroupsAk5", "fbk" => "GroupsFbk"];

    public function __construct() {
        $this->Groups = new ArrayCollection();
        $this->Users = new ArrayCollection();
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
    public function getUsers(){return $this->Users;}

    public function getActiveGroupsByGrade($grade)
    {
        return $this->getGroups()->filter(function($g) use ($grade){
            return $g->getGrade() == $grade && $g->isActive();
        });
    }

    public function getNrActiveGroupsByGrade($grade)
    {
        return count($this->getActiveGroupsByGrade($grade));
    }

    /*
    public function set($attribute, $value)
    {
    $this->$attribute = $value;
}



public function getAllGrades()
{
return array_keys($this->grade_column_map);
}

public function getVisitOrder()
{
$this->setInformation();
return $this->pick("VisitOrder");
}

public function getName()
{
$this->setInformation();
return $this->pick("Name");

}

public function getUsers()
{
return $this->Users;
}

public function countActiveGroups($grade = null)
{
$this->setInformation();
$grades = $grade ?? $this->getAllGrades();
$grades = (array) $grades;
$groups = $this->getTable("groups");

$group_count = 0;
foreach($grades as $grade){
$criteria = [["IsActive", "true"], ["Grade", $grade]];
$group_count += count(U::filterFor($groups, $criteria));
}
return $group_count;
}

public function countExpectedGroups($grade = null)
{
$this->setInformation();
$grades = $grade ?? $this->getAllGrades();
$grades = (array) $grades;

$group_count = 0;
foreach($grades as $grade){
$col_name = $this->grade_column_map[$grade];
$group_count += $this->pick($col_name);
}
return $group_count;
}
*/

}
