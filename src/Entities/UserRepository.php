<?php
namespace Fridde\Entities;

use \Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findActiveUsers()
    {
        return $this->findBy(["Status" => User::ACTIVE]);
    }
}
