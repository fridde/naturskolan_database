<?php

namespace Fridde\Entities;

use Fridde\CustomRepository;
use Eluceo\iCal\Component\Event as IcalEvent;
use Fridde\Timing as T;

class EventRepository extends CustomRepository
{
    public function getEvents()
    {
        $ics_events = [];
        $all_events = $this->findAllValid();
        /* @var Event $ev */
        foreach ($all_events as $ev) {
            $event = new IcalEvent();
            $start_date_time = $ev->getStartDate();
            $end_date_time = $ev->hasEndDate() ? $ev->getEndDate() : $start_date_time->copy();

            if (!$ev->isWholeDay()) {
                $start_date_time->setTimeFromTimeString($ev->getStartTime());
                if(!$ev->hasEndTime()){
                    $end_date_time = $start_date_time->copy();
                    T::addDuration($this->getStandardDuration(), $end_date_time);
                } else {
                    $end_date_time->setTimeFromTimeString($ev->getEndTime());
                }
            }
            $event->setNoTime($ev->isWholeDay());
            $event->setDtStart($start_date_time);
            $event->setDtEnd($end_date_time);
            $event->setSummary($ev->getTitle());
            $event->setDescription($ev->getDescription() ?? '');
            $event->setLocation($ev->getLocation() ?? '');

            $ics_events[] = $event;
        }
        return $ics_events;
    }

    public function getStandardDuration()
    {
        if(defined('SETTINGS')){
            return SETTINGS['calendar']['default_event_duration'] ?? null;
        }
        return null;
    }

    public function findAllValid()
    {
        return array_filter($this->findAll(), function (Event $event){
            return $event->isValid();
        });
    }
}
