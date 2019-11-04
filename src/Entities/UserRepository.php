<?php

namespace Fridde\Entities;

use Fridde\CustomRepository;

class UserRepository extends CustomRepository
{
    public function findActiveUsers()
    {
        return $this->findBy(['Status' => User::ACTIVE]);
    }

    public function findAllUsersWithSchools(): array
    {
        $users_id_name_school = array_map(
            function (User $u) {
                return [$u->getId(), $u->getFullName().', '.mb_strtoupper($u->getSchoolId())];
            },
            $this->findAll()
        );

        return array_column($users_id_name_school, 1, 0);
    }

    public function findIncompleteUsers($created_before = null): array
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

    public function findActiveUsersWithVisitingGroups(array $users = null): array
    {
        $users = $users ?? $this->findActiveUsers();

        $users = array_filter(
            $users,
            function (User $u) {
                return $u->hasActiveGroupsVisitingInTheFuture();
            }
        );

        return $users;

    }

    public function findIncompleteUsersWithVisitingGroups($created_before = null): array
    {
        return array_filter(
            $this->findIncompleteUsers($created_before),
            function (User $u) {
                return $u->hasActiveGroupsVisitingInTheFuture();
            }
        );

    }

    public function findUsersWithBadMobil($created_before = null): array
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

    private function removeImmune($users, $created_before = null): array
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

    public function findActiveUsersHavingGroupInSegment(string $segment = null)
    {
        return $this->all()->active()->hasGroupInSegment($segment)->fetch();
    }


    public function active(): self
    {
        return $this->filterByFunction('active');
    }

    public function incomplete(): self
    {
        return $this->filterByFunction('incomplete');
    }

    public function createdBefore($created_before = null): self
    {
        return $this->filterByFunction('createdbefore', $created_before);
    }

    public function hasGroupInSegment(string $segment = null): self
    {
        return $this->filterByFunction('hasgroupinsegment', $segment);
    }

    public function isColleague(): self
    {
        return $this->filterByFunction('iscolleague');
    }

    public function hasVisitingGroup(): self
    {
        return $this->filterByFunction('hasvisitinggroup');
    }

    public function hasBadMobileNumber(): self
    {
        return $this->filterByFunction('hasbadmobilenumber');
    }

    public function isManager(): self
    {
        return $this->filterByFunction('isManager');
    }

    private function filterByFunction(string $function_name, ...$args): self
    {
        $this->selection = array_filter(
            $this->selection,
            function (User $u) use ($function_name, $args){
                switch(strtolower($function_name)){
                    case 'active':
                        return $u->isActive();
                        break;
                    case 'incomplete':
                        return !($u->hasMobil() && $u->hasMail());
                        break;
                    case 'createdbefore':
                        return !$u->wasCreatedAfter($args[0]);
                        break;
                    case 'hasgroupinsegment':
                        return $u->hasGroupsWithCriteria(['in_segment' => $args[0]]);
                        break;
                    case 'iscolleague':
                        return $u->isFromSchool('natu');
                        break;
                    case 'hasvisitinggroup':
                        return $u->hasActiveGroupsVisitingInTheFuture();
                        break;
                    case 'hasbadmobilenumber':
                        return $u->hasMobil() && !$u->hasStandardizedMob();
                        break;
                    case 'ismanager':
                        return $u->hasRole(User::ROLE_SCHOOL_MANAGER);
                        break;
                    default:
                        throw new \Exception('The function "'. $function_name .'"  has no defined behaviour.');
                }
            }
        );

        return $this;

    }



}
