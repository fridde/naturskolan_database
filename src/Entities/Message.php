<?php
namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Carbon\Carbon;

/**
* @Entity(repositoryClass="Fridde\Entities\MessageRepository")
* @Table(name="messages")
*/
class Message
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @ManyToOne(targetEntity="User", inversedBy="Messages")     **/
    protected $User;

    /** @Column(type="string") */
    protected $Type;

    /** @Column(type="string") */
    protected $Carrier;

    /** @Column(type="text") */
    protected $Content;

    /** @Column(type="string") */
    protected $Timestamp;

    public function getId(){return $this->id;}
    public function setId($id){$this->id = $id;}
    public function getUser(){return $this->User;}
    public function setUser($User){$this->User = $User;}
    public function getType(){return $this->Type;}
    public function setType($Type){$this->Type = $Type;}
    public function getCarrier(){return $this->Carrier;}
    public function setCarrier($Carrier){$this->Carrier = $Carrier;}
    public function getContent(){return $this->Content;}
    public function setContent($Content){$this->Content = $Content;}
    public function getTimestamp()
    {
        if(is_string($this->Timestamp)){
            $this->Timestamp = new Carbon($this->Timestamp);
        }
        return $this->Timestamp;
    }
    public function setTimestamp($Timestamp)
    {
        if(!is_string($Timestamp)){
            $Timestamp = $Timestamp->toIso8601String();
        }
        $this->Timestamp = $Timestamp;
    }
    /** @PostPersist */
    public function postPersist(){ }
    /** @PostUpdate */
    public function postUpdate(){ }
    /** @PreRemove */
    public function preRemove(){ }

}
