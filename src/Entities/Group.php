<?php
namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Carbon\Carbon;
use Fridde\ORM;

/**
* @Entity(repositoryClass="Fridde\Entities\GroupRepository")
* @Table(name="groups")
*/
class Group
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @Column(type="string") */
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

    /** @Column(type="text") */
    protected $Food;

    /** @Column(type="text") */
    protected $Info;

    /** @Column(type="text") */
    protected $Notes;

    /** @Column(type="integer") */
    protected $Status;

    /** @Column(type="string") */
    protected $LastChange;

    /** @Column(type="string")  */
    protected $CreatedAt;

    /** @OneToMany(targetEntity="Visit", mappedBy="Group")     **/
    protected $Visits;

    public function __construct() {
        $this->Visits = new ArrayCollection();
    }


    const GRADE_LABELS = ["2" => "åk 2/3", "5" => "åk 5", "fbk" => "FBK"];
    const COLOUR_NAMES = ["beige","blå","brun","röd","fuchsia","grå","grön",
    "gul","guld","khaki","lila","magenta","orange","rosa","sepia","silver",
    "svart","turkos","violett","vit"];
    const STATUS = [0 => "inactive", 1 => "active"];

    public function getId(){return $this->id;}
    public function getName(){return $this->Name;}
    public function setName(){$this->Name = func_get_arg(0);}
    public function getPlaceholderName(){
        $id = $this->id ?? mt_rand();
        $index = $id % count($this->colour_names);
        return "Grupp " . ucfirst($this->colour_names[$id]);
    }
    public function setPlaceholderName()
    {
        $this->setName($this->getPlaceholderName());
    }
    public function hasName(){return $this->has("Name");}
    public function getUser(){return $this->User;}

    public function getUserId()
    {
        return $this->getUser()->getId();
    }
    public function setUser(){
        $this->User = $this->convertToEntity("User", func_get_args());
    }
    public function hasUser(){return !empty($this->User);}
    public function getSchool(){return $this->School;}

    public function getSchoolId()
    {
        return $this->getSchool()->getId();
    }

    public function setSchool(){
        $this->School = $this->convertToEntity("School", func_get_args());
    }
    public function getGrade(){return $this->Grade;}
    public function getGradeLabel(){
        return self::GRADE_LABELS[$this->Grade];
    }

    public function getGradeOptions()
    {
        return self::GRADE_LABELS;
    }


    public function setGrade()
    {
        $this->Grade = func_get_arg(0);
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
    public function hasVisits(){return !$this->Visits->isEmpty();}
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

    private function sortVisits($visit_collection = null)
    {
        if($visit_collection->isEmpty()){
            $visits = $this->getVisits();
        }
        if($visits->isEmpty()){
            return $visits; //empty collection
        }
        $visits = $visits->getIterator();

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

    private function has($attribute)
    {
        return !empty(trim($this->$attribute));
    }

    /** @PostPersist */
    public function postPersist(){ }
    /** @PostUpdate */
    public function postUpdate(){ }
    /** @PreRemove */
    public function preRemove(){ }

}
