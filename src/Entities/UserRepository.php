<?php

namespace Fridde\Entities;

use Fridde\CustomRepository;

class UserRepository extends CustomRepository
{
    public function findActiveUsers()
    {
        return $this->findBy(['Status' => User::ACTIVE]);
    }

    public function findAllUsersWithSchools()
    {
        $users_id_name_school = array_map(
            function (User $u) {
                return [$u->getId(), $u->getFullName().', '.mb_strtoupper($u->getSchoolId())];
            },
            $this->findAll()
        );

        return array_column($users_id_name_school, 1, 0);
    }

    public function findIncompleteUsers($created_before = null)
    {
        $users = array_filter(
            $this->findActiveUsers(),
            function (User $u) {
                return !($u->hasMobil() && $u->hasMail());
            }
        );
        $users = $this->removeImmune($users, $created_before);

        return $users;
    }

    public function findUsersWithBadMobil($created_before = null)
    {
        $users = array_filter(
            $this->findActiveUsers(),
            function (User $u) {
                return $u->hasMobil() && !$u->hasStandardizedMob();
            }
        );
        $this->removeImmune($users, $created_before);

        return $users;
    }

    private function removeImmune($users, $created_before = null)
    {
        if (empty($created_before)) {
            return $users;
        }

        return array_filter(
            $users,
            function (User $u) use ($created_before) {
                return !$u->wasCreatedAfter($created_before);
            }
        );
    }

    public function getActiveColleagues(): array
    {
        return array_filter(
            $this->findAll(),
            function (User $u) {
                return $u->isActive() && $u->isFromSchool('natu');
            }
        );

    }

    public function getActiveManagers(): array
    {
        return array_filter(
            $this->findActiveUsers(),
            function (User $u) {
                return $u->hasRole(User::ROLE_SCHOOL_MANAGER);
            }
        );
    }

}
