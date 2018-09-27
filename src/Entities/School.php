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

    /** @Column(type="string", nullable=true) */
    protected $Coordinates;

    /** @Column(type="smallint", nullable=true) */
    protected $VisitOrder;

    /** @Column(type="integer", options={"default" : 0}) */
    protected $BusRule = 0;

    /** @OneToMany(targetEntity="Group", mappedBy="School") */
    protected $Groups;

    /** @OneToMany(targetEntity="User", mappedBy="School") */
    protected $Users;

    /** @OneToMany(targetEntity="GroupCount", mappedBy="School", cascade={"persist"}) */
    protected $GroupCounts;

    public function __construct()
    {
        $this->Groups = new ArrayCollection();
        $this->Users = new ArrayCollection();
        $this->GroupCounts = new ArrayCollection();
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

    public function getGroupCountsAsString(): string
    {
        $group_counts = $this->getGroupCounts();
        if(empty($group_counts)){
            return '';
        }

        return  json_encode($this->spliceGroupCounts($group_counts));
    }

    private function spliceGroupCounts(array $groupCounts)
    {
        $spliced = [];
        foreach($groupCounts as $gc){
            /* @var GroupCount $gc  */
            $spliced[$gc->getStartYear()][$gc->getSegment()] = $gc->getNumber();
        }

        return $spliced;
    }

    /**
     * @return GroupCount[]|null
     */
    public function getGroupCounts(): ?array
    {
        return $this->GroupCounts->toArray();
    }

    public function getGroupCount(string $segment, int $start_year = null): ?GroupCount
    {
        $start_year = $start_year ?? Carbon::today()->year;
        $groupCounts = $this->getGroupCounts() ?? [];

        $group_count = array_filter(
            $groupCounts,
            function (GroupCount $gc) use ($start_year, $segment) {
                return $gc->matches($start_year, $segment);
            }
        );
        $group_count = array_shift($group_count);

        return $group_count;

    }

    public function getGroupCountNumber(string $segment, int $start_year = null): int
    {
        $group_count = $this->getGroupCount($segment, $start_year);

        return empty($group_count) ? 0 : $group_count->getNumber();
    }

    public function setGroupCounts(array $GroupCounts): void
    {
        $this->GroupCounts = new ArrayCollection($GroupCounts);
    }

    /**
     * @param string $segment
     * @param int $number
     * @param int|null $start_year
     */
    public function setGroupCount(string $segment, int $number = 0, int $start_year)
    {
        $current_values = $this->getGroupCounts() ?? [];

        $args = [$start_year, $segment, $number];
        $updated = false;

        array_walk(
            $current_values,
            function (GroupCount &$gc) use ($args, &$updated) {
                if ($gc->matches($args[0], $args[1])) {
                    $gc->setNumber($args[2]);
                    $updated = true;
                }
            }
        );

        if (!$updated) {
            $current_values[] = new GroupCount($this, $start_year, $segment, $number);
        }

        $this->setGroupCounts($current_values);
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

        if ($bus_rule & $location_bus_value) {  // i.e. needs bus
            $bus_rule ^= $location_bus_value; // removes the corresponding bit
        }
        $this->setBusRule($bus_rule);
    }

    public function updateBusRule(Location $location, bool $needs_bus): void
    {
        if ($needs_bus) {
            $this->addLocationToBusRule($location);
        } else {
            $this->removeLocationFromBusRule($location);
        }
    }

    public function needsBus(Location $location = null): bool
    {
        if(empty($location)){
            return false;
        }
        $location_bus_value = 1 << $location->getBusId();

        return $this->getBusRule() & $location_bus_value; // bitwise
    }

    public function getGroups(): array
    {
        return $this->Groups->toArray();
    }

    public function getGroupsByName(): array
    {
        $groups = $this->getGroups();
        usort($groups, function(Group $g1, Group $g2){
            return strcasecmp($g1->getName(), $g2->getName());
        });

        return $groups;
    }


    public function getUsers(): array
    {
        return $this->Users->toArray();
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
            $this->getGroupsByName(),
            function (Group $g) use ($segment_id, $start_year) {
                $cond1 = $start_year !== false ? $g->getStartYear() === $start_year : true;
                $cond2 = $g->getSegment() === (string)$segment_id;
                $cond3 = $g->isActive();

                return $cond1 && $cond2 && $cond3;
            }
        );
    }

    public function hasSegment(string $segment_id)
    {
        return !empty($this->getActiveGroupsBySegmentAndYear($segment_id, false));
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
