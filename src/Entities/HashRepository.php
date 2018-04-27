<?php
namespace Fridde\Entities;

use Carbon\Carbon;
use Fridde\CustomRepository;

class HashRepository extends CustomRepository
{
    public function findByPassword(string $password, int $cat, string $version = null)
    {
        $criteria['Category'] = $cat;
        if(!empty($version)){
            $criteria['Version'] = $version;
        }
        $criteria[] = ['gt', 'ExpiresAt', Carbon::now()->toIso8601String()];

        $possible_hashes = $this->select($criteria);

        $valid_hashes = array_filter($possible_hashes, function(Hash $hash) use($password){
            return password_verify($password, $hash->getValue());
        });
        if (count($valid_hashes) > 1) {
            throw new \Exception('The result of the search for a matching password entry was ambigous. This should never happen!');
        }
        if(count($valid_hashes) === 0) {
            return null;
        }

        return array_shift($valid_hashes);
    }

    public function findHashesThatExpireAfter($date, int $category = null, $owner_id = null)
    {
        if($date instanceof Carbon){
            $date = $date->toIso8601String();
        }
        $crit[] = ['gt', 'ExpiresAt', $date];
        $crit['Category'] = $category;
        $crit['Owner_id'] = $owner_id;
        $crit = array_filter($crit);

        return $this->select($crit);
    }


}
