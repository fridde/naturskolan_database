<?php
namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Carbon\Carbon;

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
    const INACTIVE = 0;
    const ACTIVE = 1;

    public function getId(){return $this->id;}
    public function getName(){return $this->Name;}
    public function setName($Name){$this->Name = $Name;}
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
    public function setUser($User){$this->User = $User;}
    public function hasUser(){return !empty($this->User);}
    public function getSchool(){return $this->School;}
    public function setSchool($School){$this->School = $School;}
    public function getGrade(){return $this->Grade;}
    public function getGradeLabel(){
        return self::GRADE_LABELS[$this->Grade];
    }
    public static function translateGradeToLabel(){

    }
    public function setGrade($Grade){$this->Grade = $Grade;}
    public function isGrade($Grade)
    {
        return $this->getGrade() === strval($Grade);
    }

    public function getStartYear(){return $this->StartYear;}
    public function setStartYear($StartYear){$this->StartYear = $StartYear;}
    public function getNumberStudents(){return $this->NumberStudents;}
    public function setNumberStudents($NumberStudents){$this->NumberStudents = $NumberStudents;}
    public function getFood(){return $this->Food;}
    public function setFood($Food){$this->Food = $Food;}
    public function getInfo(){return $this->Info;}
    public function setInfo($Info){$this->Info = $Info;}
    public function hasInfo(){return $this->has("Info");}
    public function getNotes(){return $this->Notes;}
    public function setNotes($Notes){$this->Notes = $Notes;}
    public function hasNotes(){return $this->has("Notes");}
    public function getStatus(){return $this->Status;}
    public function setStatus($Status){$this->Status = $Status;}
    public function isActive()
    {
        return $this->getStatus() == self::ACTIVE;
    }

    public function getLastChange(){return $this->LastChange;}
    public function setLastChange($LastChange){$this->LastChange = $LastChange;}
    public function getCreatedAt(){return $this->CreatedAt;}
    public function setCreatedAt($CreatedAt){$this->CreatedAt = $CreatedAt;}
    public function hasVisits(){return !empty($this->Visits);}
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
        if(empty($visit_collection)){
            $visits = $this->getVisits();
        }
        if(empty($visits)){
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
        if(!empty($visits)){
            return $visits->first();
        } else {
            return null;
        }
    }

    public function hasNextVisit()
    {
        return !empty($this->getNextVisit());
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
