<?php

namespace Fridde;

use Eluceo\iCal\Component\Calendar as Cal;
use Eluceo\iCal\Component\Event;
use Carbon\Carbon;
use Fridde\Entities\EventRepository;
use Fridde\Entities\Visit;

class Calendar
{
    public $settings;
    private $ORM;

    public $file_name = 'kalender.ics';

    public function __construct()
    {
        date_default_timezone_set('Europe/Stockholm');
        $this->setConfiguration();
        $this->ORM = new ORM();
    }


    public function setConfiguration($settings = null)
    {
        $this->settings = $settings ?? (defined('SETTINGS') ? SETTINGS : false);
        if ($this->settings === false) {
            throw new \Exception('No settings given or found in the global scope');
        }
    }

    private function createFormattedEventsFromVisits()
    {
        $cal_settings = $this->settings['calendar'];
        $visits = $this->ORM->getRepository('Visit')->findAll();

        $formatted_events = [];

        foreach ($visits as $visit) {
            /* @var Visit $visit */
            $topic = $visit->getTopic();
            $date = $visit->getDate(); // is already Carbon
            $start_DT = $date->copy();
            $end_DT = $date->copy();

            if (!$visit->hasGroup()) { // i.e. is an extra visit for backup-purposes
                $row['whole_day'] = true;
                $row['start_date_time'] = $start_DT->toIso8601String();
                $row['end_date_time'] = $end_DT->toIso8601String();
                $title = 'Reservtillfälle för '.$topic->getShortName();
                $row['title'] = $title;
                $formatted_events[] = $row;
                continue;
            }

            $group = $visit->getGroup();
            $teacher = $group->getUser();
            $location = $topic->getLocation();
            $is_lektion = $topic->getIsLektion();

            if ($is_lektion && $visit->hasTime()) {
                $time = $visit->getTimeAsArray();
                $start_DT->hour($time['start']['hh'])->minute($time['start']['mm']);
                if (empty($time['end'])) {
                    $dur = $cal_settings['lektion_duration'];
                    $end_DT = $start_DT->copy()->addMinutes($dur);
                } else {
                    $end_DT->hour($time['end']['hh'])->minute($time['end']['mm']);
                }
            } else {
                $start_DT->hour($cal_settings['default_start_time'][0]);
                $start_DT->minute($cal_settings['default_start_time'][1]);
                $end_DT->hour($cal_settings['default_end_time'][0]);
                $end_DT->minute($cal_settings['default_end_time'][1]);
            }

            $row['start_date_time'] = $start_DT->toIso8601String();
            $row['end_date_time'] = $end_DT->toIso8601String();

            $row['whole_day'] = false;

            $title = '';
            if ($visit->hasColleagues()) {
                $title .= '['.$visit->getColleaguesAsAcronymString().'] ';
            }
            $title .= $visit->getLabel('TGSU');
            //temadag med åk från skola (klass, lärare)
            $row['title'] = $title;
            $row['location'] = $location->getName();

            $desc = [];
            $desc[] = 'Tid: '.$start_DT->toTimeString().'-'.$end_DT->toTimeString();
            $desc[] = 'Lärare: '.$teacher->getFullName();
            $desc[] = 'Årskurs: '.$group->getSegmentLabel();
            $desc[] = 'Mobil: '.$teacher->getMobil();
            $desc[] = 'Mejl: '.$teacher->getMail();
            $desc[] = 'Klass '.$group->getName().' med '.$group->getNumberStudents().' elever';
            $desc[] = 'Matpreferenser: '.$group->getFood();
            if ($group->hasInfo()) {
                $desc[] = 'Annat: '.$group->getInfo();
            }
            if ($group->hasNotes()) {
                $desc[] = 'Interna anteckningar: '.$group->getNotes();
            }
            $row['description'] = $desc;

            $formatted_events[] = $row;
        }

        return $formatted_events;
    }

    private function convertToComponents(array $array): array
    {
        $component_array = [];
        foreach ($array as $row) {
            $event = new Event();
            $event->setDtStart(new \DateTime($row['start_date_time']));
            $event->setDtEnd(new \DateTime($row['end_date_time']));
            if ($row['whole_day']) {
                $event->setNoTime(true);
            }
            $event->setSummary($row['title']);
            if (!empty($row['description'])) {
                $event->setDescription(implode("\r\n", $row['description']));
            }
            if (!empty($row['location'])) {
                $event->setLocation($row['location']);
            }

            $event->setUseTimezone(true);
            $component_array[] = $event;
        }
        return $component_array;
    }

    public function getEventsFromVisitsTable(): array
    {
        $formatted_events = $this->createFormattedEventsFromVisits();
        return $this->convertToComponents($formatted_events);
    }

    public function getEventsFromEventsTable(): array
    {
        /* @var EventRepository $event_repo  */
        $event_repo = $this->ORM->getRepository('Event');
        return $event_repo->getEvents();
    }


    public function convertEventArrayToIcs(array $array): string
    {
        $cal = new Cal('SigtunaNaturskola');
        $cal->setName('SigtunaNaturskola Schema');

        foreach ($array as $event) {
            $cal->addComponent($event);
        }

        return $cal->render();
    }

    public function getAllEvents()
    {
        return array_merge(
            $this->getEventsFromVisitsTable(),
            $this->getEventsFromEventsTable()
        );
    }

    public function render()
    {
        return $this->convertEventArrayToIcs($this->getAllEvents());
    }

    public function save($file_name = null)
    {
        $dir = defined('BASE_DIR') ? BASE_DIR : '';
        $file_name = $file_name ?? ($this->file_name ?? false);
        if (!$file_name) {
            throw new \Exception('Tried to save the Calendar without a file name.');
        }
        $file_name = empty($dir) ? $file_name : $dir. '/' . $file_name;

        return file_put_contents($file_name, $this->render());
    }

    public function dateStringToArray($date_string)
    {
        $d = new Carbon($date_string);
        return [$d->year, $d->month, $d->day];
    }


}
