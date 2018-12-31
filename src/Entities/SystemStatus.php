<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="systemstatus")
 * @ORM\HasLifecycleCallbacks
 */
class SystemStatus
{
    /** @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Value;

    /** @ORM\Column(type="string", nullable=true) */
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

    /** @ORM\PrePersist */
    public function prePersist()
    {
        $this->setLastChange(Carbon::now()->toIso8601String());
    }

    /** @ORM\PreUpdate */
    public function preUpdate()
    {
        $this->setLastChange(Carbon::now()->toIso8601String());
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
    }

}
