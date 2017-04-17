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

    public function findChangesOlderThan($date)
    {
        $criteria = ["lt", "Timestamp", $date->toIso8601String()];
        return $this->select($criteria);
    }
}
