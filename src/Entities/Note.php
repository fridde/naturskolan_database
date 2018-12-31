<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity(repositoryClass="Fridde\Entities\NoteRepository")
 * @ORM\Table(name="notes")
 * @ORM\HasLifecycleCallbacks
 */
class Note
{
    /** @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\ManyToOne(targetEntity="Visit", inversedBy="Notes") */
    protected $Visit;

    /** @ORM\ManyToOne(targetEntity="User", inversedBy="Notes") */
    protected $User;

    /** @ORM\Column(type="string") */
    protected $Text;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Timestamp;

    /** @ORM\Column(type="string", nullable=true) */
    protected $LastChange;

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
     * @param Visit $Visit
     */
    public function setVisit(Visit $Visit): void
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

    /**
     * @return mixed
     */
    public function getLastChange()
    {
        return $this->LastChange;
    }

    /**
     * @param mixed $LastChange
     */
    public function setLastChange($LastChange): void
    {
        if ($LastChange instanceof Carbon) {
            $LastChange = $LastChange->toIso8601String();
        }
        $this->LastChange = $LastChange;
    }

    /** @ORM\PrePersist */
    public function prePersist()
    {
        $this->setTimestamp(Carbon::now());
    }

    /** @ORM\PreUpdate */
    public function preUpdate()
    {
        $this->setLastChange(Carbon::now());
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
    }



}
