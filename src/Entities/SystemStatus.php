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
    /** @Id @Column(type="string") */
    protected $id;

    /** @Column(type="string", nullable=true) */
    protected $Value;

    /** @Column(type="string", nullable=true) */
    protected $LastChange;


    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getValue(): ?string
    {
        return $this->Value;
    }

    public function setValue(string $Value)
    {
        $this->Value = $Value;
    }


    public function getLastChange(): ?string
    {
        return $this->LastChange;
    }

    public function setLastChange(string $LastChange)
    {
        $this->LastChange = $LastChange;
    }

    /** @PrePersist */
    public function prePersist()
    {
        $this->setLastChange(Carbon::now()->toIso8601String());
    }

    /** @PreUpdate */
    public function preUpdate()
    {
        $this->setLastChange(Carbon::now()->toIso8601String());
    }

    /** @PreRemove */
    public function preRemove()
    {
    }

}
