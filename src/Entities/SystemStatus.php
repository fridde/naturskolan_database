<?php
namespace Fridde\Entities;

/**
* @Entity
* @Table(name="systemstatus")
*/
class SystemStatus
{
    /** @Id @Column(type="string")  */
    protected $id;

    /** @Column(type="string", nullable=true) */
    protected $Value;

    /** @Column(type="string", nullable=true) */
    protected $LastChange;

    const CLEAN = "clean";
    const DIRTY = "dirty";


    public function getId(){return $this->id;}
    public function setId($id){$this->id = $id;}
    public function getValue(){return $this->Value;}
    public function setValue($Value){$this->Value = $Value;}


    public function getLastChange(){return $this->LastChange;}
    public function setLastChange($LastChange){$this->LastChange = $LastChange;}

    /** @PrePersist */
    public function prePersist(){}
    /** @PreUpdate */
    public function preUpdate(){}
    /** @PreRemove */
    public function preRemove(){}

}
