<?php
namespace Fridde\Entities;

use Carbon\Carbon;

/**
* @Entity
* @Table(name="systemstatus")
* @HasLifecycleCallbacks
*/
class SystemStatus
{
    /** @Id @Column(type="string")  */
    protected $id;

    /** @Column(type="string", nullable=true) */
    protected $Value;

    /** @Column(type="string", nullable=true) */
    protected $LastChange;

    public const CLEAN = 'clean';
    public const DIRTY = 'dirty';


    public function getId(){return $this->id;}
    public function setId($id){$this->id = $id;}
    public function getValue(){return $this->Value;}
    public function setValue($Value){$this->Value = $Value;}


    public function getLastChange(){return $this->LastChange;}
    public function setLastChange($LastChange){$this->LastChange = $LastChange;}

    /** @PrePersist */
    public function prePersist(){
        $this->setLastChange(Carbon::now()->toIso8601String());
    }
    /** @PreUpdate */
    public function preUpdate()
    {
        $this->setLastChange(Carbon::now()->toIso8601String());
    }
    /** @PreRemove */
    public function preRemove(){}

}
