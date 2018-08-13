<?php

namespace Fridde\Entities;

use Carbon\Carbon;

/**
 * @Entity(repositoryClass="Fridde\Entities\ChangeRepository")
 * @Table(name="changes")
 * @HasLifecycleCallbacks
 */
class Change
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(type="smallint") */
    protected $Type;

    /** @Column(type="string") */
    protected $EntityClass;

    /** @Column(type="string") */
    protected $EntityId;

    /** @Column(type="string", nullable=true) */
    protected $Property;

    /** @Column(type="string", nullable=true) */
    protected $OldValue;

    /** @Column(type="string", nullable=true) */
    protected $Processed;

    /** @Column(type="string") */
    protected $Timestamp;

    public const TYPE_DELETION = -1;
    public const TYPE_UPDATE = 0;
    public const TYPE_INSERTION = 1;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->Type;
    }

    /**
     * @param mixed $Type
     */
    public function setType(int $Type)
    {
        $this->Type = $Type;
    }

    public function typeToString(int $type)
    {
        $types = [
            self::TYPE_DELETION => 'deletion',
            self::TYPE_UPDATE => 'update',
            self::TYPE_INSERTION => 'insertion',
        ];

        return $types[$type] ?? null;
    }


    public function getEntityClass()
    {
        return $this->EntityClass;
    }

    public function setEntityClass($EntityClass)
    {
        $this->EntityClass = $EntityClass;
    }

    public function getEntityId()
    {
        return $this->EntityId;
    }

    public function setEntityId($EntityId)
    {
        $this->EntityId = $EntityId;
    }

    public function getProperty()
    {
        return $this->Property;
    }

    public function setProperty($Property)
    {
        $this->Property = $Property;
    }

    public function getOldValue()
    {
        return $this->OldValue;
    }

    public function setOldValue($OldValue)
    {
        $this->OldValue = $OldValue;
    }

    public function getProcessed()
    {
        if (is_string($this->Processed)) {
            return new Carbon($this->Processed);
        }

        return $this->Processed;
    }

    public function setProcessed($Processed)
    {
        if ($Processed instanceof Carbon) {
            $Processed = $Processed->toIso8601String();
        }
        $this->Processed = $Processed;
    }

    public function getTimestamp()
    {
        if (is_string($this->Timestamp)) {
            return new Carbon($this->Timestamp);
        }

        return $this->Timestamp;
    }

    public function setTimestamp($Timestamp)
    {
        if ($Timestamp instanceof Carbon) {
            $Timestamp = $Timestamp->toIso8601String();
        }
        $this->Timestamp = $Timestamp;
    }

    /** @PrePersist */
    public function prePersist()
    {
        $this->setTimestamp(Carbon::now());
    }

    /** @PreUpdate */
    public function preUpdate()
    {
    }

    /** @PreRemove */
    public function preRemove()
    {
    }

}
