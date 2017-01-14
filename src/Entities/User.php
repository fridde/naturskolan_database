<?php
namespace Fridde\Entities;

use \Fridde\{Utility as U};
use \Carbon\Carbon;

use Doctrine\Common\Collections\ArrayCollection;

/**
* @Entity(repositoryClass="Fridde\Entities\UserRepository")
* @Table(name="users")
*/
class User
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @Column(type="string") */
    protected $FirstName;

    /** @Column(type="string")       */
    protected $LastName;

    /** @Column(type="string")       */
    protected $Mobil;

    /** @Column(type="string")       */
    protected $Mail;

    /** @ManyToOne(targetEntity="School", inversedBy="Users")     **/
    protected $School;

    /** @Column(type="integer")       */
    protected $Role;

    /** @Column(type="string", nullable=true)       */
    protected $Acronym;

    /** @Column(type="integer")      */
    protected $Status;

    /** @Column(type="string")  */
    protected $LastChange;

    /** @Column(type="string")  */
    protected $CreatedAt;

    /** @ManyToMany(targetEntity="Visit", mappedBy="Colleagues")
    * @JoinTable(name="Colleagues_Visits") */
    protected $Visits;

    /** @OneToMany(targetEntity="Message", mappedBy="User") */
    protected $Messages;

    public $roleMapper = [0 => "teacher", 1 => "rektor", 2 => "administrator",
    3 => "stakeholder", 4 => "superadmin"];

    const ACTIVE = 1;
    const ARCHIVED = 0;
    const TEACHER = 0;
    const REKTOR = 1;
    const ADMINISTRATOR = 2;
    const INTRESSENT = 3;
    const SUPERADMIN = 4;

    public function __construct() {
        $this->Visits = new ArrayCollection();
        $this->Messages = new ArrayCollection();
    }

    public function getId(){return $this->id;}
    public function getFirstName(){return $this->FirstName;}
    public function setFirstName($FirstName){$this->FirstName = $FirstName;}
    public function getLastName(){return $this->LastName;}
    public function setLastName($LastName){$this->LastName = $LastName;}

    public function getFullName()
    {
        return $this->FirstName . " " . $this->LastName;
    }

    public function getShortName()
    {
        return $this->FirstName . " " . substr($this->LastName, 0 , 1);
    }


    public function getMobil(){return $this->Mobil;}
    public function setMobil($Mobil){$this->Mobil = $Mobil;}
    public function hasMobil(){return !empty(trim($this->Mobil));}
    public function getMail(){return $this->Mail;}
    public function setMail($Mail){$this->Mail = $Mail;}
    public function hasMail(){return !empty(trim($this->Mail));}
    public function getSchool(){return $this->School;}
    public function setSchool($School){$this->School = $School;}
    public function getRole(){return $this->Role;}
    public function setRole($Role){$this->Role = $Role;}
    public function getAcronym(){return $this->Acronym;}
    public function setAcronym($Acronym){$this->Acronym = $Acronym;}
    public function getStatus(){return $this->Status;}
    public function setStatus($Status){$this->Status = $Status;}
    public function getLastChange(){return $this->LastChange;}
    public function setLastChange($LastChange){$this->LastChange = $LastChange;}
    public function getCreatedAt()
    {
        if(!empty($this->CreatedAt)){
            $date = new Carbon($this->CreatedAt);
            return $date;
        }
        return null;
    }
    public function setCreatedAt($CreatedAt){
        if(!is_string($CreatedAt) && get_class($CreatedAt) == "Carbon\Carbon"){
            $CreatedAt = $CreatedAt->toIso8601String();
        }
        $this->CreatedAt = $CreatedAt;
    }
    public function getVisits(){return $this->Visits;}
    public function addVisit($Visit){$this->Visits->add($Visit);}
    public function removeVisit($Visit){$this->Visits->removeElement($Visit);}
    public function getMessages(){return $this->Messages;}
    public function setMessages($Messages){$this->Messages = $Messages;}

    public function wasCreatedAfter($date)
    {
        if(is_string($date)){
            $date = new Carbon($date);
        }
        return $date->lte($this->getCreatedAt());
    }

    private function sortMessagesByDate($message_collection = null)
    {
        if(empty($message_collection)){
            $messages = $this->getMessages();
        }
        if(empty($messages)){
            return $messages; //empty collection
        }

        $messages = $messages->getIterator();

        $messages->uasort(function($a, $b){
            return ($a->getTimestamp()->lt($b->getTimestamp())) ? -1 : 1 ;
        });
        $this->Messages =  new ArrayCollection(iterator_to_array($messages));
        return $this->Messages;
    }

    public function getLastMessage()
    {
        $this->sortMessagesByDate();
        return $this->Messages->last();
    }

    public function lastMessageWasAfter($date)
    {
        if(is_string($date)){
            $date = new Carbon($date);
        }
        return $this->getLastMessage()->getTimestamp()->gte($date);
    }

    /** @PostPersist */
    public function postPersist(){ }
    /** @PostUpdate */
    public function postUpdate(){ }
    /** @PreRemove */
    public function preRemove(){ }


}

/*
public function getRoleName()
{
$this->setInformation();
$roleId = $this->pick("Role");
return $this->roleMapper[$roleId];
}
*/
