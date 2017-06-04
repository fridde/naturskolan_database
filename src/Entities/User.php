<?php
namespace Fridde\Entities;

use Carbon\Carbon;

use Doctrine\Common\Collections\ArrayCollection;
use Fridde\Update;

/**
* @Entity(repositoryClass="Fridde\Entities\UserRepository")
* @Table(name="users")
* @HasLifecycleCallbacks
*/
class User
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @Column(type="string", nullable=true) */
    protected $FirstName;

    /** @Column(type="string", nullable=true)       */
    protected $LastName;

    /** @Column(type="string", nullable=true)       */
    protected $Mobil;

    /** @Column(type="string", nullable=true)       */
    protected $Mail;

    /** @ManyToOne(targetEntity="School", inversedBy="Users")     **/
    protected $School;

    /** @Column(type="integer", nullable=true)       */
    protected $Role = 0;

    /** @Column(type="string", nullable=true)       */
    protected $Acronym;

    /** @Column(type="integer", nullable=true)      */
    protected $Status = 1;

    /** @Column(type="string", nullable=true)  */
    protected $LastChange;

    /** @Column(type="string", nullable=true)  */
    protected $CreatedAt;

    /** @ManyToMany(targetEntity="Visit", mappedBy="Colleagues")
    * @JoinTable(name="Colleagues_Visits") */
    protected $Visits;

    /** @OneToMany(targetEntity="Message", mappedBy="User") */
    protected $Messages;

    /** @OneToMany(targetEntity="Group", mappedBy="User") */
    protected $Groups;

    const ROLES = [0 => "teacher", 1 => "headmaster", 2 => "administrator",
    3 => "stakeholder", 4 => "superadmin"];

    const STATUS = [0 => "archived", 1 => "active"];

    public function __construct() {
        $this->Visits = new ArrayCollection();
        $this->Messages = new ArrayCollection();
        $this->Groups = new ArrayCollection();
    }

    public function getId(){return $this->id;}
    public function hasId($Id)
    {
        return $this->getId() == $Id;
    }
    public function getFirstName(){return $this->FirstName;}
    public function setFirstName($FirstName){$this->FirstName = trim($FirstName);}
    public function getLastName(){return $this->LastName;}
    public function setLastName($LastName){$this->LastName = trim($LastName);}

    public function getFullName()
    {
        return $this->FirstName . " " . $this->LastName;
    }

    public function getShortName()
    {
        return $this->FirstName . " " . substr($this->LastName, 0 , 1);
    }


    public function getMobil(){
        return $this->Mobil;
    }

    public function getStandardizedMobil()
    {
        return $this->standardizeMobNr($this->Mobil);
    }

    public function setMobil($Mobil)
    {
        $mob = preg_replace('/[^0-9+]/', '', $Mobil);
        $this->Mobil = $mob;
    }

    public function hasMobil(){return !empty(trim($this->Mobil));}
    public function getMail(){return $this->Mail;}
    public function setMail($Mail){$this->Mail = trim($Mail);}
    public function hasMail(){return !empty(trim($this->Mail));}
    /**
     * @return \Fridde\Entities\School
     */
    public function getSchool(){return $this->School;}

    public function getSchoolId()
    {
        return $this->getSchool()->getId();
    }

    public function setSchool($School)
    {
        $this->School = $School;
        $School->addUser($this);
    }

    public function isFromSchool($school_id)
    {
        $school_id = (array) $school_id;
        return in_array($this->getSchool()->getId(), $school_id);
    }

    public function getRole(){return $this->Role;}
    public function setRole($Role){$this->Role = $Role;}
    public function getRoleLabel()
    {
        return self::ROLES($this->getRole());

    }

    public function getRoleOptions()
    {
        return self::ROLES;
    }

    public function isRole($role)
    {
        if(is_string($role)){
            $role = array_search($role, self::ROLES);
        }
        return $this->getRole() == $role;
    }


    public function getAcronym(){return $this->Acronym;}
    public function setAcronym($Acronym){$this->Acronym = $Acronym;}
    public function getStatus(){return $this->Status;}

    public function getStatusOptions()
    {
        return self::STATUS;
    }

    public function setStatus($Status){$this->Status = $Status;}

    public function isActive()
    {
        return self::STATUS[$this->getStatus()] == "active";
    }

    public function getLastChange(){return $this->LastChange;}
    public function setLastChange($LastChange)
    {
        if(!is_string($LastChange)){
            $LastChange = $LastChange->toIso8601String();
        }
        $this->LastChange = $LastChange;
    }
    public function getCreatedAt()
    {
        if(is_string($this->CreatedAt)){
            $this->CreatedAt = new Carbon($this->CreatedAt);
        }
        return $this->CreatedAt;
    }
    public function setCreatedAt($CreatedAt){
        if(!is_string($CreatedAt)){
            $CreatedAt = $CreatedAt->toIso8601String();
        }
        $this->CreatedAt = $CreatedAt;
    }
    public function getVisits(){return $this->Visits;}
    public function addVisit($Visit){$this->Visits->add($Visit);}
    public function removeVisit($Visit){$this->Visits->removeElement($Visit);}
    public function getMessages(){return $this->Messages;}

    public function getFilteredMessages($properties, $messages = null)
    {
        if(empty($messages)){
            $messages = $this->Messages;
        }
        return $messages->filter(function($m) use ($properties){
            return $m->checkProperties($properties);
        });
    }

    public function setMessages($Messages){$this->Messages = $Messages;}

    public function addMessage($Message)
    {
        $this->Messages->add($Message);
    }

    public function getGroups(){return $this->Groups;}

    public function getGroupIdArray()
    {
        return array_map(function($g){
            return $g->getId();
        }, $this->getGroups()->toArray());
    }

    public function addGroup($Group){$this->Groups->add($Group);}
    public function removeGroup($Group){$this->Groups->removeElement($Group);}

    public function wasCreatedAfter($date)
    {
        if(is_string($date)){
            $date = new Carbon($date);
        }
        $created_at = $this->getCreatedAt() ?? Carbon::now();
        return $date->lte($created_at);
    }

    public function sortMessagesByDate()
    {
        $messages = $this->getMessages()->getIterator();

        $messages->uasort(function($a, $b){
            if(empty($a->getTimestamp()) && empty($b->getTimestamp())){
                return 0;
            } elseif(empty($a->getTimestamp())){
                return 1;
            } elseif(empty($b->getTimestamp())){
                return -1;
            } else {
                return ($a->getTimestamp()->lt($b->getTimestamp())) ? -1 : 1 ;
            }

        });
        $this->Messages =  new ArrayCollection(iterator_to_array($messages));
        return $this->Messages;
    }



    /**
    * [getLastMessage description]
    * @param  string $type    [description]
    * @param  string $carrier [description]
    * @return [type]          [description]
    */
    public function getLastMessage($properties = null)
    {
        $msg = $this->Messages;
        if(!empty($properties)){
            $msg = $this->getFilteredMessages($properties);
        }
        $msg = $this->sortMessagesByDate($msg);
        $last_message = $msg->last();
        return ($last_message === false ? null : $last_message);
    }


    public function lastMessageWasAfter($date, $properties = null)
    {
        $last_message = $this->getLastMessage();
        if(!empty($lastMessage)){
            return $this->getLastMessage($properties)->wasSentAfter($date);
        }
        return false;
    }

    public function hasStandardizedMob()
    {
        return $this->standardizeMobNr(null, true);
    }

    public function standardizeMobNr($number = null, $just_check = false)
    {
        $number = !empty($number) ? $number : $this->Mobil;
        $nr = preg_replace("/[^0-9]/", "", $number);
        $trim_characters = ["0", "4", "6"]; // we need to trim from left to right order
        foreach($trim_characters as $char){
            $nr = ltrim($nr, $char);
        }
        if(in_array(substr($nr, 0, 2), ["70", "72", "73", "76"])){
            $nr = "+46" . $nr;
            $standardized = true;
        } else {
            $nr = $number;
            $standardized = false;
        }
        if($just_check){
            return $standardized;
        }
        return $nr;
    }

    /** @PrePersist */
    public function prePersist($event)
    {
        $this->setCreatedAt(Carbon::now());
    }
    /** @PostPersist */
    public function postPersist($event)
    {
        (new Update())->logNewEntity($event);
    }
    /** @PreUpdate */
    public function preUpdate()
    {
        $this->setLastChange(Carbon::now());
    }
    /** @PreRemove */
    public function preRemove(){ }


}
