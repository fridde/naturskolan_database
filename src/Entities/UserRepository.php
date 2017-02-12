<?php
namespace Fridde\Entities;

use \Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findActiveUsers()
    {
        return $this->findBy(["Status" => 1]);
    }

    public function findAllUsersWithSchools()
    {
        $users_id_name_school = array_map(function($u){
            return [$u->getId(), $u->getFullName() . ", " . mb_strtoupper($u->getSchoolId())];
        }, $this->findAll());
        return array_column($users_id_name_school, 1, 0);
    }


}
