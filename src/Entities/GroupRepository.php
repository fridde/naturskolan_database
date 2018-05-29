<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class GroupRepository extends CustomRepository
{

    public function findActiveGroups($segment = null)
    {
        $criteria = ['Status', Group::ACTIVE];
        if(!empty($segment_id)){
            $criteria[] = ['Segment', $segment_id];
        }
        return $this->select($criteria);
    }

    public function findGroupsInSegment(string $segment_id = null)
    {
        if(!empty($segment_id)){
            return $this->select(['Segment', $segment_id]);
        }
        return $this->findAll();
    }

    public function findAllGroupsWithNameAndSchool()
    {
        $groups_id_name_school = array_map(function($g){
            $label = '[' . $g->getSegmentLabel() . '] ' . $g->getName();
            $label .= ', ' . mb_strtoupper($g->getSchoolId());
            return [$g->getId(), $label];
        }, $this->findActiveGroups());
        return array_column($groups_id_name_school, 1, 0);
    }

    public function getGroupsWithUser($user)
    {
        return array_filter($this->findAll(), function($g) use ($user) {
            return $g->getUserId() === $user->getId();
        });
    }

    public function getNextVisitForUser($user)
    {
        $groups = $this->getGroupsWithUser($user);
        if(empty($groups)){
            return null;
        }
        uasort($groups, function($a, $b){
            $v1 = $a->getNextVisit();
            $v2 = $b->getNextVisit();
            if(empty($v1) && empty($v2)){
                return 0;
            } elseif(empty($v1) || empty($v2)) {
                return empty($v1) ? 1 : -1 ;
            }
            return $v1->getDate()->lt($v2->getDate()) ? -1 : 1 ;
        });
        $first_group = reset($groups);
        return $first_group->getNextVisit();
    }

    public function findGroupsOlderThan($date)
    {
        $criteria = ['lt', 'CreatedAt', $date->toIso8601String()];
        return $this->select($criteria);
    }

    public function findGroupsWithoutName()
    {
        $criteria = [['isNull', 'Name'], ['eq', 'Name', '']];
        return $this->selectOr($criteria);
    }

    public function sortByVisitOrder(array $groups)
    {
        usort(
            $groups,
            function (Group $g1, Group $g2) {
                return $g1->compareVisitOrder($g2);
            }
        );
        return $groups;
    }

}
