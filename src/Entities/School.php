<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Fridde\Entities\Group;
use Doctrine\Common\Collections\ArrayCollection;
use Fridde\Utility;
use Monolog\Handler\Curl\Util;

/**
 * @Entity(repositoryClass="Fridde\Entities\SchoolRepository")
 * @Table(name="schools")
 * @HasLifecycleCallbacks
 */
class School
{
    /** @Id @Column(type="string") */
    protected $id;

    /** @Column(type="string") */
    protected $Name;

    /** @Column(type="json", nullable=true) */
    protected $GroupNumbers;

    /** @Column(type="string", nullable=true) */
    protected $Coordinates;

    /** @Column(type="integer", nullable=true) */
    protected $VisitOrder;

    /** @Column(type="integer", options={"default" : 0}) */
    protected $BusRule = 0;

    /** @OneToMany(targetEntity="Group", mappedBy="School") */
    protected $Groups;

    /** @OneToMany(targetEntity="User", mappedBy="School") */
    protected $Users;

    public function __construct()
    {
        $this->Groups = new ArrayCollection();
        $this->Users = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->Name;
    }

    public function setName($Name)
    {
        $this->Name = $Name;
    }

    public function getGroupNumbersAsString()
    {
        return json_encode($this->getGroupNumbers() ?? []);
    }

    /**
     * @return array|null
     */
    public function getGroupNumbers()
    {
        return $this->GroupNumbers;
    }

    public function getGroupNumber(string $segment, int $start_year = null): int
    {
        $start_year = $start_year ?? Carbon::today()->year;
        $groupNumbers = $this->getGroupNumbers();

        return $groupNumbers[$start_year][$segment] ?? 0;
    }

    public function setGroupNumbers(array $GroupNumbers)
    {
        $this->GroupNumbers = $GroupNumbers;
    }

    /**
     * @param string $segment
     * @param int $value
     * @param int|null $start_year
     */
    public function setGroupNumber(string $segment, int $value = 0, int $start_year = null)
    {
        $start_year = $start_year ?? Carbon::today()->year;

        $current_values = $this->getGroupNumbers() ?? [];
        $current_values[$start_year][$segment] = $value;
        $this->setGroupNumbers($current_values);
    }

    public function getCoordinates()
    {
        return $this->Coordinates;
    }

    public function setCoordinates($Coordinates)
    {
        $this->Coordinates = $Coordinates;
    }

    public function getVisitOrder()
    {
        return $this->VisitOrder;
    }

    public function setVisitOrder($VisitOrder)
    {
        $this->VisitOrder = $VisitOrder;
    }

    public function getBusRule(): int
    {
        return $this->BusRule;
    }

    public function setBusRule(int $BusRule): void
    {
        $this->BusRule = $BusRule;
    }

    public function addLocationToBusRule(Location $location): void
    {
        $bus_rule = $this->getBusRule();
        $location_bus_value = 1 << $location->getBusId();

        $new_bus_rule = $bus_rule | $location_bus_value; // bitwise
        $this->setBusRule($new_bus_rule);
    }

    public function removeLocationFromBusRule(Location $location): void
    {
        $bus_rule = $this->getBusRule();
        $location_bus_value = 1 << $location->getBusId();

        if($bus_rule & $location_bus_value){  // i.e. needs bus
            $bus_rule ^= $location_bus_value; // removes the corresponding bit
        }
        $this->setBusRule($bus_rule);
    }

    public function updateBusRule(Location $location, bool $needs_bus): void
    {
        if($needs_bus){
            $this->addLocationToBusRule($location);
        } else {
            $this->removeLocationFromBusRule($location);
        }
    }

    public function needsBus(Location $location): bool
    {
        $location_bus_value = 1 << $location->getBusId();

        return $this->getBusRule() & $location_bus_value; // bitwise
    }

    public function getGroups(): array
    {
        return $this->Groups->toArray();
    }


    public function getUsers()
    {
        return $this->Users;
    }

    public function addUser($User)
    {
        $this->Users->add($User);
    }

    /**
     * @param $segment
     * @param mixed $start_year If null, the current year is assumed. If false, all years are included
     * @return array
     */
    public function getActiveGroupsBySegmentAndYear($segment_id, $start_year = null)
    {
        $start_year = $start_year ?? Carbon::today()->year;

        return array_filter(
            $this->getGroups(),
            function (Group $g) use ($segment_id, $start_year) {
                $cond1 = $start_year !== false ? $g->getStartYear() === $start_year : true;
                $cond2 = $g->getSegment() === (string) $segment_id;
                $cond3 = $g->isActive();

                return $cond1 && $cond2 && $cond3;
            }
        );
    }

    public function hasSegment(string $segment_id)
    {
        return ! empty($this->getActiveGroupsBySegmentAndYear($segment_id, false));
    }

    public function getSegmentsAvailable($withLabels = false)
    {


        $available_segments = array_filter(
            Group::getSegmentLabels(),
            function ($k) {
                return $this->hasSegment($k);
            },
            ARRAY_FILTER_USE_KEY
        );

        return $withLabels ? $available_segments : array_keys($available_segments);
    }

    public function getNrActiveGroupsBySegmentAndYear($segment_id, $start_year = null)
    {
        return count($this->getActiveGroupsBySegmentAndYear($segment_id, $start_year));
    }

    public function isNaturskolan()
    {
        return $this->getId() === 'natu';
    }

    /** @PrePersist */
    public function prePersist()
    {
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
