<?php
namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Fridde\Entities\Group;

/**
* @Entity(repositoryClass="Fridde\Entities\TopicRepository")
* @Table(name="topics")
*/
class Topic
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @Column(type="string") */
    protected $Grade;

    /** @Column(type="integer")       */
    protected $VisitOrder;

    /** @Column(type="string") */
    protected $ShortName;

    /** @Column(type="string") */
    protected $LongName;

    /** @ManyToOne(targetEntity="Location", inversedBy="Topics")     **/
    protected $Location;

    /** @Column(type="string") */
    protected $Food;

    /** @Column(type="string") */
    protected $Url;

    /** @Column(type="integer") */
    protected $IsLektion;

    /** @OneToMany(targetEntity="Visit", mappedBy="Topic")   **/
    protected $Visits;

    public function __construct()
    {
        $this->Visits = new ArrayCollection();
    }

    public function getId(){return $this->id;}
    public function getGrade(){return $this->Grade;}

    public function getGradeLabel()
    {
        return Group::GRADE_LABELS[$this->getGrade()];
    }

    public function getGradeOptions()
    {
        return Group::GRADE_LABELS;
    }

    public function setGrade($Grade){$this->Grade = $Grade;}
    public function getVisitOrder(){return $this->VisitOrder;}
    public function setVisitOrder($VisitOrder){$this->VisitOrder = $VisitOrder;}
    public function getShortName(){return $this->ShortName;}
    public function setShortName($ShortName){$this->ShortName = $ShortName;}
    public function getLongName(){return $this->LongName;}
    public function setLongName($LongName){$this->LongName = $LongName;}
    public function getLocation(){return $this->Location;}

    public function getLocationId()
    {
        return $this->getLocation()->getId();
    }

    public function setLocation($Location){$this->Location = $Location;}
    public function getFood(){return $this->Food;}
    public function setFood($Food){$this->Food = $Food;}
    public function getUrl(){return $this->Url;}
    public function setUrl($Url){$this->Url = $Url;}
    public function getIsLektion(){return (boolean) $this->IsLektion;}
    public function isLektion(){return $this->getIsLektion();}

    public function getIsLektionOptions()
    {
        return [0 => "No", 1 => "Yes"];
    }

    public function setIsLektion($IsLektion){$this->IsLektion = (int) $IsLektion;}
    public function getVisits(){return $this->Visits;}

}
