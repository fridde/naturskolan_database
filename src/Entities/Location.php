<?php
namespace Fridde\Entities;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;

/**
* @Entity(repositoryClass="Fridde\Entities\LocationRepository")
* @Table(name="locations")
 * @HasLifecycleCallbacks
*/
class Location
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @Column(type="string") */
    protected $Name;

    /** @Column(type="string") */
    protected $Coordinates;

    /** @Column(type="string", nullable=true) */
    protected $Description;

    /** @Column(type="integer", nullable=true) */
    protected $BusId;

    /** @Column(type="string", nullable=true) */
    protected $LastChange;

    /** @OneToMany(targetEntity="Topic", mappedBy="Location") */
    protected $Topics;

    public function __construct() {
        $this->Topics = new ArrayCollection();
    }

    public function getId(){return $this->id;}
    public function getName(){return $this->Name;}
    public function setName($Name){$this->Name = $Name;}
    public function getCoordinates(){return $this->Coordinates;}
    public function setCoordinates($Coordinates){$this->Coordinates = $Coordinates;}
    public function getDescription(){return $this->Description;}
    public function setDescription($Description){$this->Description = $Description;}
    public function getBusId(){return $this->BusId;}
    public function setBusId($BusId){$this->BusId = $BusId;}
    public function getLastChange(){return $this->LastChange;}
    public function setLastChange($LastChange){$this->LastChange = $LastChange;}


    public function getTopics(){return $this->Topics;}
    public function addTopic($topic){$this->Topics[] = $topic;}

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
    public function preRemove(){}

}
