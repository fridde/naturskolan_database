<?php
namespace Fridde\Entities;

use Doctrine\ORM\EntityRepository;
use Carbon\Carbon;

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

    public function findIncompleteUsers($created_before = null)
    {
        $users = array_filter($this->findActiveUsers(), function($u){
            return !($u->hasMobil() && $u->hasMail());
        });
        if(!empty($created_before)){
            $users = array_filter($users, function($u) use ($created_before){
                return !$u->wasCreatedAfter($created_before);
            });
        }
        return $users;
    }


}
