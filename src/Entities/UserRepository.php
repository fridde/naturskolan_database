<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class UserRepository extends CustomRepository
{
    public function findActiveUsers()
    {
        return $this->findBy(["Status" => 1]);
    }

    public function findAllUsersWithSchools()
    {
        /* @var $u \Fridde\Entities\User */
        $users_id_name_school = array_map(function(\Fridde\Entities\User $u){
            return [$u->getId(), $u->getFullName() . ", " . mb_strtoupper($u->getSchoolId())];
        }, $this->findAll());
        return array_column($users_id_name_school, 1, 0);
    }

    public function findIncompleteUsers($created_before = null)
    {
        $users = array_filter($this->findActiveUsers(), function(\Fridde\Entities\User $u){
            return !($u->hasMobil() && $u->hasMail());
        });
        $this->removeImmune($users, $created_before);
        return $users;
    }

    public function findUsersWithBadMobil($created_before = null)
    {
        $users = array_filter($this->findActiveUsers(), function(\Fridde\Entities\User $u){
            return $u->hasMobil() && !$u->hasStandardizedMob();
        });
        $this->removeImmune($users, $created_before);
        return $users;
    }

    private function removeImmune($users, $created_before = null)
    {
        if(empty($created_before)){
            return $users;
        }
        return array_filter($users, function(\Fridde\Entities\User $u) use ($created_before){
            return !$u->wasCreatedAfter($created_before);
        });
    }

}
