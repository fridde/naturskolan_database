<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class ChangeRepository extends CustomRepository
{
    public function findNewChanges($criteria = [])
    {
        $criteria[] = ["isNull", "Processed"];
        return $this->select($criteria);
    }

    /**
     * @param \Carbon\Carbon $date
     * @return array
     */
    public function findChangesOlderThan($date)
    {
        $criteria = ["lt", "Timestamp", $date->toIso8601String()];
        return $this->select($criteria);
    }

    /**
     * Checks the table *changes* for unprocessed new Users and returns their ids     *
     *
     * @return array The ids of all new unprocessed users.
     */
    public function findChangesWithNewUser()
    {
        $criteria[] = ["isNull", "Processed"];
        $criteria[] = ["eq", "EntityClass", "User"];
        $criteria[] = ["isNull", "Property"];

        return $this->selectAnd($criteria);
    }
}
