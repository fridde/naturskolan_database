<?php
namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;

/**
* @Entity(repositoryClass="Fridde\Entities\ChangeRepository")
* @Table(name="changes")
*/
class Change
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @Column(type="string") */
    protected $EntityName;

    /** @Column(type="string") */
    protected $EntityId;

    /** @Column(type="string") */
    protected $Attribute;

    /** @Column(type="string") */
    protected $OldValue;

    /** @Column(type="string") */
    protected $Timestamp;

    public function getId(){return $this->id;}
    public function setId($id){$this->id = $id;}
    public function getEntityName(){return $this->EntityName;}
    public function setEntityName($EntityName){$this->EntityName = $EntityName;}
    public function getEntityId(){return $this->EntityId;}
    public function setEntityId($EntityId){$this->EntityId = $EntityId;}
    public function getAttribute(){return $this->Attribute;}
    public function setAttribute($Attribute){$this->Attribute = $Attribute;}
    public function getOldValue(){return $this->OldValue;}
    public function setOldValue($OldValue){$this->OldValue = $OldValue;}
    public function getTimestamp(){return $this->Timestamp;}
    public function setTimestamp($Timestamp){$this->Timestamp = $Timestamp;}
    /** @PostPersist */
    public function postPersist(){ }
    /** @PostUpdate */
    public function postUpdate(){ }
    /** @PreRemove */
    public function preRemove(){ }

}
