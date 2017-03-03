<?php
namespace Fridde\Entities;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Carbon\Carbon;

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
        $visits = $this->findFutureVisits();
        return $this->sortVisitsByDate($visits)->last();
    }

    /**
     * [findUnconfirmedVisitsWithin description]
     *
     * @param  int $days nr of days to go forward. Implicitly converts to integer via (int)
     * @return [ArrayCollection]       [description]
     */
    public function findUnconfirmedVisitsWithin($days)
    {
        $visits = $this->findFutureVisits(Carbon::today()->addDays($days));
        return $visits->filter(function($v){
            return !$v->isConfirmed();
        });
    }

    public function sortVisitsByDate($visit_collection)
    {
        if(!is_array($visit_collection)){
            $visit_collection = $visit_collection->toArray();
        }
        usort($visit_collection, function($v1, $v2){
            return $v1->getDate()->lte($v2->getDate()) ? -1 : 1;
        });
        return new ArrayCollection($visit_collection);
    }

}
