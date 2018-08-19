<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class GroupCountRepository extends CustomRepository
{
    public function findGroupCount(School $school, int $start_year, string $segment)
    {
        $criteria[] = ['eq', 'School_id', $school->getId()];
        $criteria[] = ['eq', 'StartYear', $start_year];
        $criteria[] = ['eq', 'Segment', $segment];

        $group_counts = $this->select(...$criteria);

        if(count($group_counts) > 1){
            $msg = 'The group count for ' . $school->getName();
            $msg .= ' , year ' . $start_year . ', segment ' . $segment;
            $msg .= ' has duplicate entries. This should never happen!';
            throw new \Exception($msg);
        }

        if(empty($group_counts)){
            return null;
        }
        return array_shift($group_counts);
    }


}
