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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return Visit
     */
    public function getVisit(): Visit
    {
        return $this->Visit;
    }

    /**
     * @param mixed $Visit
     */
    public function setVisit($Visit): void
    {
        $this->Visit = $Visit;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->User;
    }

    /**
     * @param User $User
     */
    public function setUser(User $User): void
    {
        $this->User = $User;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->Text;
    }

    /**
     * @param string $Text
     */
    public function setText(string $Text): void
    {
        $this->Text = $Text;
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
