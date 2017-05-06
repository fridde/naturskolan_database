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
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @Column(type="string", nullable=true) */
    protected $Name;

    /** @ManyToOne(targetEntity="User", inversedBy="Groups")     **/
    protected $User;

    /** @ManyToOne(targetEntity="School", inversedBy="Groups")     **/
    protected $School;

    /** @Column(type="string") */
    protected $Grade;

    /** @Column(type="integer") */
    protected $StartYear;

    /** @Column(type="integer") */
    protected $NumberStudents;

    /** @Column(type="text", nullable=true) */
    protected $Food;

    /** @Column(type="text", nullable=true) */
    protected $Info;

    /** @Column(type="text", nullable=true) */
    protected $Notes;

    /** @Column(type="integer") */
    protected $Status;

    /** @Column(type="string", nullable=true) */
    protected $LastChange;

    /** @Column(type="string")  */
    protected $CreatedAt;

    /** @OneToMany(targetEntity="Visit", mappedBy="Group")     **/
    protected $Visits;

    public function __construct() {
        $this->Visits = new ArrayCollection();
    }


    const GRADE_LABELS = ["2" => "åk 2/3", "5" => "åk 5", "fbk" => "FBK"];
    const STATUS = [0 => "inactive", 1 => "active"];

    public function getId(){return $this->id;}
    public function getName(){return $this->Name;}
    public function setName($Name = null)
    {
        if(empty($Name)){
            $alias_names = \Fridde\Naturskolan::getSetting("defaults","placeholder", "animals");
            $Name = "Grupp " . $alias_names[mt_rand(0, count($alias_names) - 1)];
        }
        $this->Name = trim($Name);
    }

    public function hasName(){return $this->has("Name");}
    public function getUser(){return $this->User;}

    public function getUserId()
    {
        return $this->getUser()->getId();
    }
    public function setUser(...$properties){
        $this->User = $this->convertToEntity("User", $properties);
        $this->User->addGroup($this);
    }
    public function hasUser(){return !empty($this->User);}
    public function getSchool(){return $this->School;}

    public function getSchoolId()
    {
        return $this->getSchool()->getId();
    }

    public function setSchool(...$properties){
        $this->School = $this->convertToEntity("School", $properties);
    }
    public function getGrade(){return $this->Grade;}
    public function getGradeLabel(){
        return self::GRADE_LABELS[$this->Grade];
    }

    public function getGradeOptions()
    {
        return self::GRADE_LABELS;
    }


    public function setGrade($Grade)
    {
        $this->Grade = $Grade;
    }

    public function isGrade($Grade)
    {
        return $this->getGrade() === strval($Grade);
    }

    public function getStartYear(){return $this->StartYear;}
    public function setStartYear(){$this->StartYear = func_get_arg(0);}
    public function getNumberStudents(){return $this->NumberStudents;}
    public function setNumberStudents(){$this->NumberStudents = func_get_arg(0);}
    public function getFood(){return $this->Food;}
    public function setFood(){$this->Food = func_get_arg(0);}
    public function getInfo(){return $this->Info;}
    public function setInfo(){$this->Info = func_get_arg(0);}
    public function hasInfo(){return $this->has("Info");}
    public function getNotes(){return $this->Notes;}
    public function setNotes(){$this->Notes = func_get_arg(0);}
    public function hasNotes(){return $this->has("Notes");}
    public function getStatus(){return $this->Status;}

    public function getStatusOptions()
    {
        return self::STATUS;
    }

    public function setStatus(){$this->Status = func_get_arg(0);}
    public function isActive()
    {
        return self::STATUS[$this->getStatus()] == "active";
    }

    public function getLastChange(){return $this->LastChange;}
    public function setLastChange(){$this->LastChange = func_get_arg(0);}
    public function getCreatedAt(){return $this->CreatedAt;}
    public function setCreatedAt(){$this->CreatedAt = func_get_arg(0);}
    public function hasVisits()
    {
        return !$this->Visits->isEmpty();
    }
    public function getVisits(){return $this->Visits;}
    public function getFutureVisits()
    {
        return $this->getVisitsAfter(Carbon::today());
    }

    public function getVisitsAfter($date, $ordered = true)
    {
        if($ordered){
            $this->sortVisits();
        }
        if(is_string($date)){
            $date = new Carbon($date);
        }
        return $this->getVisits()->filter(function($v) use ($date){
            return $v->isAfter($date);
        });
    }

    public function getSortedVisits()
    {
        $this->sortVisits();
        return $this->getVisits();
    }

    private function sortVisits()
    {
        if($this->Visits->isEmpty()){
            return $this->Visits; //empty collection
        }
        $visits = $this->Visits->getIterator();

        $visits->uasort(function($a, $b){
            return ($a->getDate()->lt($b->getDate())) ? -1 : 1;
        });
        $this->Visits = new ArrayCollection(iterator_to_array($visits));
        return $this->Visits;
    }

    public function getNextVisit()
    {
        $visits = $this->sortVisits($this->getFutureVisits());
        if(!$visits->isEmpty()){
            return $visits->first();
        } else {
            return null;
        }
    }

    public function hasNextVisit()
    {
        return !empty($this->getNextVisit());
    }

    public function convertToEntity($entity_class, $args)
    {
        $id_or_entity = $args[0];
        $entity = $id_or_entity;
        if(is_string($id_or_entity) || is_integer($id_or_entity)){
            $ORM = $args[1];
            $entity = $ORM->find($entity_class, $id_or_entity);
        }
        return $entity;
    }

/**
 *
 * @param  \Fridde\Entities\Group  $other_group Another group to compare to.
 * @return int Returns a negative number if this group is supposed to visit before
 *             the other group, returns a positive number otherwise. Won't return 0.
 */
    public function compareVisitOrder(Group $other_group)
    {
        $v_order_1 = $this->getSchool()->getVisitOrder();
        $v_order_2 = $other_group->getSchool()->getVisitOrder();
        if($v_order_1 !== $v_order_2){
            return $v_order_1 - $v_order_2;
        }
        return $this->getId() - $other_group->getId();
    }

    private function has($attribute)
    {
        return !empty(trim($this->$attribute));
    }

    /** @PrePersist */
    public function prePersist($event){}
    /** @PreUpdate */
    public function preUpdate($event)
    {
        $rq["update_method"] = "logChange";
        $rq["event"] = $event;
        $rq["trackables"] = ["User", "Food", "NumberStudents", "Info"];
        Update::create($rq);
    }
    /** @PreRemove */
    public function preRemove(){}

}
