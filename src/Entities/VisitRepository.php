<?php
namespace Fridde\Entities;

use \Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use \Carbon\Carbon;

class VisitRepository extends EntityRepository
{
    public function findFutureVisits($maxForward = null)
    {
        if(!empty($maxForward) && is_string($maxForward)){
            $maxForward = new Carbon($maxForward);
        }
        $all_visits = new ArrayCollection($this->findAll());
        return $all_visits->filter(function($visit) use ($maxForward){
            if(!empty($maxForward)){
                return $visit->isAfter(Carbon::now()) && $visit->isBefore($maxForward);
            } else {
                return $visit->isAfter(Carbon::now());
            }
        });
    }

    public function findLastVisit()
    {
        return $this->getFutureVisits()->last();
    }

}
