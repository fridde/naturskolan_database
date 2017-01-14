<?php
namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use \Carbon\Carbon;

/**
* @Entity(repositoryClass="Fridde\Entities\VisitRepository")
* @Table(name="visits")
*/
class Visit
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @ManyToOne(targetEntity="Group", inversedBy="Visits")   **/
    protected $Group;

    /** @Column(type="string") */
    protected $Date;

    /** @ManyToOne(targetEntity="Topic", inversedBy="Visits")   **/
    protected $Topic;

    /** This is the owning side. The visit has many colleagues (=users)
    * @ManyToMany(targetEntity="User", inversedBy="Visits")
    * @JoinTable(name="Colleagues_Visits") */
    protected $Colleagues;

    /** @Column(type="integer") */
    protected $Confirmed;

    /** @Column(type="string", nullable=true) */
    protected $Time;

    public function __construct() {
        $this->Colleagues = new ArrayCollection();
    }

    public function getId(){return $this->id;}
    public function getGroup(){return $this->Group;}
    public function setGroup($Group){$this->Group = $Group;}

    public function getDate(){
        if(is_string($this->Date)){
            $this->Date = new Carbon($this->Date);
        }         
        return $this->Date;
    }

    public function setDate($Date){
        if(!is_string($Date)){
            $Date = $Date->toDateString();
        }
        $this->Date = $Date;
    }

    public function getTopic(){return $this->Topic;}
    public function setTopic($Topic){$this->Topic = $Topic;}
    public function getColleagues(){return $this->Colleagues;}

    public function addColleague($Colleague){
        $this->Colleagues->add($Colleague);
        $Colleague->addVisit($this);
    }
    public function removeColleague($Colleague){
        $this->Colleagues->removeElement($Colleague);
        $Colleague->removeVisit($this);
    }
    public function isConfirmed(){return boolval($this->Confirmed);}
    public function getConfirmed(){return $this->Confirmed;}
    public function setConfirmed($Confirmed){$this->Confirmed = $Confirmed;}
    public function getTime(){return $this->Time;}
    public function getTimeAsArray()
    {
        if(!$this->hasTime()){
            return null;
        }
        return $this->timeStringToArray($this->Time);
    }

    public function setTime($Time){$this->Time = $Time;}
    public function hasTime(){return !empty($this->Time);}

    public function timeStringToArray($time_string)
    {
        $parts = explode("-", $time_string);
        $parts = preg_replace('%\D%', '', $parts); // remove all non-digits
        $parts = array_map(function($v){
            $v = str_pad($v, 4, "0", STR_PAD_LEFT); // "730" becomes "0730"
            $h_and_m["hh"] = substr($v, 0, 2);
            $h_and_m["mm"] = substr($v, 2, 2);
            return $h_and_m;
        }, $parts);
        $return["start"] = $parts[0];
        $return["end"] = $parts[1] ?? null;

        return $return;
    }

    private function isBeforeOrAfter($date, $beforeOrAfter = "after")
    {
        if(is_string($date)){
            $date = new Carbon($date);
        }
        if($beforeOrAfter == "before"){
            return $this->getDate()->lte($date);
        } elseif($beforeOrAfter == "after"){
            return $this->getDate()->gte($date);
        } else {
            throw new \Exception("The comparison " . $beforeOrAfter . " is not defined.");
        }


    }

    public function isAfter($date)
    {
        return $this->isBeforeOrAfter($date , "after");
    }

    public function isBefore($date)
    {
        return $this->isBeforeOrAfter($date , "before");
    }

    public function isInFuture()
    {
        return $this->isAfter(Carbon::today());
    }

    /** @PostPersist */
    public function postPersist(){ }
    /** @PostUpdate */
    public function postUpdate(){ }
    /** @PreRemove */
    public function preRemove(){ }

    /*
    private function setDate()
    {
    $this->setInformation();
    $this->date = new C($this->information["Date"]);
}

public function getGroup()
{
return $this->getAsObject("Group");
}

public function daysLeft()
{
return $this->_NOW_->diffInDays($this->date);
}

public function isInFuture()
{
return $this->daysLeft() >= 0;
}

public function isConfirmed()
{
$this->confirmed = $this->confirmed ?? in_array($this->information["Confirmed"], [1, true, "true", "yes"]);
return $this->confirmed;
}

public function getColleagues()
{
$this->setInformation();
if($this->has("Colleague")){
$colleagues = explode(",", $this->pick("Colleague"));
return array_map(function($id){return new User($id);}, $colleagues);
} else {
return [];
}

}
*/
}
