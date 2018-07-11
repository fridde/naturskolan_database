<?php

namespace Fridde;

use Eluceo\iCal\Component\Calendar as Cal;
use Eluceo\iCal\Component\Event as IcalEvent;
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

    private function getEventsFromVisitsTable()
    {
        $cal_settings = $this->settings['calendar'];
        $visits = $this->ORM->getRepository('Visit')->findAll();

        $ics_events = [];

        foreach ($visits as $visit) {
            /* @var Visit $visit */
            $topic = $visit->getTopic();
            $date = $visit->getDate(); // is already Carbon
            $start_DT = $date->copy();
            $end_DT = $date->copy();

            $event = new IcalEvent();

            if (!$visit->hasGroup()) { // i.e. is an extra visit for backup-purposes
                $event->setNoTime(true);
                $event->setDtStart($start_DT);
                $event->setDtEnd($start_DT);
                $title = 'Reservtillfälle för '.$topic->getShortName();
                $event->setSummary($title);
                $ics_events[] = $event;
                continue;
            }

            $group = $visit->getGroup();
            $teacher = $group->getUser();
            $location = $topic->getLocation();
            $is_lektion = $topic->getIsLektion();

            if ($visit->hasTime()) {
                $time = $visit->getTimeAsArray();
                $start_DT->hour($time['start']['hh'])->minute($time['start']['mm']);
                if (empty($time['end'])) {
                    $dur = $is_lektion
                        ? $cal_settings['lektion_duration']
                        : $cal_settings['default_event_duration'];
                    $end_DT = $start_DT->copy();
                    Timing::addDuration($dur, $end_DT);
                } else {
                    $end_DT->hour($time['end']['hh'])->minute($time['end']['mm']);
                }
            } else {
                $start_DT->hour($cal_settings['default_start_time'][0]);
                $start_DT->minute($cal_settings['default_start_time'][1]);
                $end_DT->hour($cal_settings['default_end_time'][0]);
                $end_DT->minute($cal_settings['default_end_time'][1]);
            }
            $event->setDtStart($start_DT);
            $event->setDtEnd($end_DT);
            $event->setNoTime(false);

            $title = '';
            if ($visit->hasColleagues()) {
                $title .= '['.$visit->getColleaguesAsAcronymString().'] ';
            }
            $title .= $visit->getLabel('TGSU');
            //temadag med åk från skola (klass, lärare)
            $event->setSummary($title);
            $event->setLocation($location->getName());

            $desc = [];
            $desc[] = 'Tid: '.$start_DT->format('H:i').'-'.$end_DT->format('H:i');
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
            $event->setDescription(implode("\r\n", $desc));

            $ics_events[] = $event;
        }

        return $ics_events;
    }

    public function getEventsFromEventsTable(): array
    {
        /* @var EventRepository $event_repo */
        $event_repo = $this->ORM->getRepository('Event');

        return $event_repo->getEvents();
    }


    public function convertEventArrayToIcs(array $array): string
    {
        $cal = new Cal('SigtunaNaturskola');
        $cal->setName('SigtunaNaturskola Schema');

        foreach ($array as $event) {
            $event->setUseTimezone(true);
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
        $file_name = empty($dir) ? $file_name : $dir.'/'.$file_name;

        return file_put_contents($file_name, $this->render());
    }

    public function dateStringToArray($date_string)
    {
        $d = new Carbon($date_string);

        return [$d->year, $d->month, $d->day];
    }


}
