<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="Fridde\Entities\LocationRepository")
 * @ORM\Table(name="locations")
 * @ORM\HasLifecycleCallbacks
 */
class Location
{
    /** @ORM\Id
     * @ORM\Column(type="smallint")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $Name;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Coordinates;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Description;

    /** @ORM\Column(type="smallint", unique=true)
     * @ORM\GeneratedValue
     */
    protected $BusId;

    /** @ORM\Column(type="string", nullable=true) */
    protected $LastChange;

    /** @ORM\OneToMany(targetEntity="Topic", mappedBy="Location") */
    protected $Topics;

    public function __construct()
    {
        $this->Topics = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->Name;
    }

    public function setName($Name)
    {
        $this->Name = $Name;
    }

    public function getCoordinates()
    {
        return $this->Coordinates;
    }

    public function setCoordinates($Coordinates)
    {
        $this->Coordinates = $Coordinates;
    }

    public function getDescription()
    {
        return $this->Description;
    }

    public function setDescription($Description)
    {
        $this->Description = $Description;
    }

    public function getBusId()
    {
        return $this->BusId;
    }

    public function setBusId($BusId)
    {
        $this->BusId = $BusId;
    }

    public function getLastChange()
    {
        return $this->LastChange;
    }

    public function setLastChange($LastChange)
    {
        $this->LastChange = $LastChange;
    }


    public function getTopics()
    {
        return $this->Topics;
    }

    public function addTopic($topic)
    {
        $this->Topics[] = $topic;
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
