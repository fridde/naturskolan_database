<?php
namespace Fridde\Entities;

use \Doctrine\ORM\EntityRepository;

class SchoolRepository extends EntityRepository
{
    public function getNaturskolansStaff()
    {
        return $this->find("natu")->getUsers();
    }
}
