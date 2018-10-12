<?php

namespace Fridde\Entities;

use Carbon\Carbon;

/**
 * @Entity(repositoryClass="Fridde\Entities\NoteRepository")
 * @Table(name="notes")
 * @HasLifecycleCallbacks
 */
class Note
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @ManyToOne(targetEntity="Visit", inversedBy="Notes") */
    protected $Visit;

    /** @ManyToOne(targetEntity="User", inversedBy="Notes") */
    protected $User;

    /** @Column(type="string") */
    protected $Text;

    /** @Column(type="string") */
    protected $Timestamp;

    public function getTimestamp(): Carbon
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
