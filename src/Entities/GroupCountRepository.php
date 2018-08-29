<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;
use Fridde\Error\Error;
use Fridde\Error\NException;

class GroupCountRepository extends CustomRepository
{
    public function findGroupCount(School $school, int $start_year, string $segment)
    {
        $criteria[] = ['eq', 'School_id', $school->getId()];
        $criteria[] = ['eq', 'StartYear', $start_year];
        $criteria[] = ['eq', 'Segment', $segment];

        $group_counts = $this->select(...$criteria);

        if(count($group_counts) > 1){
            $args = ['group count', $school->getName().', '.$start_year.', '.$segment];
            throw new NException(Error::DATABASE_INCONSISTENT,  $args);
        }

        if(empty($group_counts)){
            return null;
        }
        return array_shift($group_counts);
    }


}
