<?php
namespace Fridde\Entities;

use Doctrine\ORM\EntityRepository;
use Fridde\Entities\Group;
//(repositoryClass="Fridde\Entities\GroupRepository")

class GroupRepository extends EntityRepository
{

    public function findActiveGroups()
    {
        return $this->findBy(["Status" => Group::ACTIVE]);
    }

}
