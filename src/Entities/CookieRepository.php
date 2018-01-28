<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class CookieRepository extends CustomRepository
{
    public function findByHash($hash)
    {
        return $this->findOneBy(['Value' => $hash, 'Name' => 'Hash']);
    }

    public function findCookiesOlderThan($date)
    {
        $criteria = ['lt', 'CreatedAt', $date->toIso8601String()];
        return $this->select($criteria);
    }

}
