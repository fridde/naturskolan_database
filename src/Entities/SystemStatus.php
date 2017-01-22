<?php
namespace Fridde\Entities;

use \Carbon\Carbon as C;
use \Fridde\{Calendar, Utility as U, Mailer};
use Doctrine\Common\Collections\ArrayCollection;


/**
* @Entity
* @Table(name="systemstatus")
*/
class SystemStatus
{
    /** @Id @Column(type="string")  */
    protected $id;

    /** @Column(type="string") */
    protected $Value;

    /** @Column(type="string") */
    protected $LastChange;

    const CLEAN = "clean";
    const DIRTY = "dirty";

    public function getId(){return $this->id;}
    public function setId($id){$this->id = $id;}
    public function getValue(){return $this->Value;}
    public function setValue($Value){$this->Value = $Value;}
    public function getLastChange(){return $this->LastChange;}
    public function setLastChange($LastChange){$this->LastChange = $LastChange;}
    /** @PostPersist */
    public function postPersist(){ }
    /** @PostUpdate */
    public function postUpdate(){ }
    /** @PreRemove */
    public function preRemove(){ }

}
