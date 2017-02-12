<?php
namespace Fridde\Entities;

use Doctrine\ORM\EntityRepository;
use Fridde\Entities\Group;

class GroupRepository extends EntityRepository
{

    public function findActiveGroups()
    {
        return $this->findBy(["Status" => 1]);
    }

    public function findAllGroupsWithNameAndSchool()
    {
        $groups_id_name_school = array_map(function($g){
            $label = "[" . $g->getGradeLabel() . "] " . $g->getName();
            $label .= ", " . mb_strtoupper($g->getSchoolId());
            return [$g->getId(), $label];
        }, $this->findActiveGroups());
        return array_column($groups_id_name_school, 1, 0);
    }

}
