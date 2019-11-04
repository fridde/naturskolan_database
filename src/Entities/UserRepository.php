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
        return $this->all()->active()->incomplete()->createdBefore($created_before)->fetch();
    }

    public function findIncompleteUsersWithVisitingGroups($created_before = null): array
    {
        $criteria = ['in_future' => true];
        $criteria['visiting'] = true;

        return $this->all()->incomplete()->createdBefore($created_before)
            ->hasGroupsWithCriteria($criteria)
            ->fetch();
    }

    public function findUsersWithBadMobil($created_before = null): array
    {
        return $this->all()->active()->hasBadMobileNumber()->createdBefore($created_before)->fetch();
    }

    public function getActiveColleagues(): array
    {
        return $this->all()->active()->isColleague()->fetch();
    }

    public function getActiveManagers(): array
    {
        return $this->all()->active()->isManager()->fetch();
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

    public function hasGroupsWithCriteria(array $criteria = []): self
    {
        return $this->filterByFunction('hasGroupsWithCriteria');
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
                    case 'hasgroupswithcriteria':
                        return $u->hasGroupsWithCriteria($args[0]);
                        break;
                    default:
                        throw new \Exception('The function "'. $function_name .'"  has no defined behaviour.');
                }
            }
        );

        return $this;

    }



}
