<?php

namespace Fridde\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Fridde\Entities\Group;

/**
 * @Entity(repositoryClass="Fridde\Entities\TopicRepository")
 * @Table(name="topics")
 * @HasLifecycleCallbacks
 */
class Topic
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(type="string") */
    protected $Grade;

    /** @Column(type="integer") */
    protected $VisitOrder;

    /** @Column(type="string") */
    protected $ShortName;

    /** @Column(type="string", nullable=true) */
    protected $LongName;

    /** @ManyToOne(targetEntity="Location", inversedBy="Topics")     * */
    protected $Location;

    /** @Column(type="string", nullable=true) */
    protected $Food;

    /** @Column(type="integer", nullable=true) */
    protected $FoodOrder;

    /** @Column(type="string", nullable=true) */
    protected $Url;

    /** @Column(type="integer", nullable=true) */
    protected $IsLektion;

    /** @Column(type="string", nullable=true) */
    protected $LastChange;

    /** @OneToMany(targetEntity="Visit", mappedBy="Topic")   * */
    protected $Visits;

    // byo means "bring your own" and means in this context that the group
    // provides for their own food
    public const FOOD_ORDER_TYPES = [
        0 => 'no_order_needed',
        1 => 'order_from_kitchen',
        2 => 'notify_kitchen',
    ];

    public function __construct()
    {
        $this->Visits = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getGrade()
    {
        return $this->Grade;
    }

    public function getGradeLabel()
    {
        $labels = Group::getGradeLabels();

        return $labels[$this->getGrade()];
    }

    public function getGradeLabels()
    {
        return Group::getGradeLabels();
    }

    public function setGrade($Grade)
    {
        $this->Grade = $Grade;
    }

    public function getVisitOrder()
    {
        return $this->VisitOrder;
    }

    public function setVisitOrder($VisitOrder)
    {
        $this->VisitOrder = $VisitOrder;
    }

    public function getShortName()
    {
        return $this->ShortName;
    }

    public function setShortName($ShortName)
    {
        $this->ShortName = $ShortName;
    }

    public function getLongName()
    {
        return $this->LongName;
    }

    public function setLongName($LongName)
    {
        $this->LongName = $LongName;
    }

    public function getLongestName()
    {
        $long = $this->getLongName();
        if (empty($long)) {
            return $this->getShortName();
        }

        return $long;
    }

    public function getLocation(): Location
    {
        return $this->Location;
    }

    public function getLocationId()
    {
        return $this->getLocation()->getId();
    }

    public function setLocation($Location)
    {
        $this->Location = $Location;
    }

    public function getFood()
    {
        return $this->Food;
    }

    public function setFood($Food)
    {
        $this->Food = $Food;
    }

    public function getFoodOrder($as_string = false)
    {
        if ($as_string) {
            return self::FOOD_ORDER_TYPES[$this->FoodOrder] ?? null;
        }

        return $this->FoodOrder;
    }

    public function setFoodOrder($FoodOrder)
    {
        if (is_string($FoodOrder)) {
            $FoodOrder = array_search($FoodOrder, self::FOOD_ORDER_TYPES);
        }
        $this->FoodOrder = $FoodOrder;
    }


    public function getUrl()
    {
        return $this->Url;
    }

    public function setUrl($Url)
    {
        $this->Url = $Url;
    }

    public function getIsLektion()
    {
        return (boolean)$this->IsLektion;
    }

    public function isLektion()
    {
        return $this->getIsLektion();
    }

    public function getIsLektionOptions()
    {
        return [0 => 'No', 1 => 'Yes'];
    }

    public function setIsLektion($IsLektion)
    {
        $this->IsLektion = (int)$IsLektion;
    }

    public function getLastChange()
    {
        return $this->LastChange;
    }

    public function setLastChange($LastChange)
    {
        $this->LastChange = $LastChange;
    }

    public function getVisits()
    {
        return $this->Visits;
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
