<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;
use Carbon\Carbon;

class VisitRepository extends CustomRepository
{
    public function findFutureVisits()
    {
        return $this->findFutureVisitsUntil(null);
    }

    /**
     *
     * @param  Carbon\Carbon|null $until The date after which visits are not included.
     *                                   If omitted, all future visits are returned.
     * @return Fridde\Entities\Visit[]  An array consisting of all visits from today until
     *                                  the specified date.
     */
    public function findFutureVisitsUntil(Carbon $until = null)
    {
        if(!empty($until) && is_string($until)){
            $until = new Carbon($until);
        }
        $all_visits = $this->findAll();
        $methods[] = ["isAfter", true, [Carbon::today()]];
        if(!empty($until)){
            $methods[] = ["isBefore", true, [$until]];
        }

        $filtered_visits = $this->findViaMultipleMethods($methods);

        return $this->sortVisits($filtered_visits);
    }

    public function findLastVisit()
    {
        return array_pop($this->findSortedVisits());
    }

    /**
    * [findUnconfirmedVisitsUntil description]
    *
    * @param  Carbon\Carbon|null $until The date after which visits are not included.
    *                                   If omitted, all future visits are returned.
    * @return [ArrayCollection]       [description]
    */
    public function findUnconfirmedVisitsUntil(Carbon\Carbon $until = null)
    {
        return array_filter($this->findFutureVisits($until), function($v){
            return !$v->isConfirmed();
        });
    }

    public function findSortedVisits($topic = null)
    {
        if(!empty($topic)){
            $visits = $this->select(["Topic", $topic]);
        } else {
            $visits = $this->findAll();
        }
        return $this->sortVisits($visits);
    }

    public function sortVisits($visits)
    {
        usort($visits, function($v1, $v2){
            return $v1->getDate()->lte($v2->getDate()) ? -1 : 1;
        });
        return $visits;
    }

}
