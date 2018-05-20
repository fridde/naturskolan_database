<?php

namespace Fridde;

use Eluceo\iCal\Component\Calendar as Cal;
use Eluceo\iCal\Component\Event;
use Carbon\Carbon;
use Fridde\Entities\User;
use Fridde\Entities\Visit;

class Calendar
{
    public $settings;
    private $ORM;

    public $formatted_array;
    public $file_name = 'kalender.ics';

    public function __construct()
    {
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

    public function rebuild()
    {
        $cal_settings = $this->settings['calendar'];
        $visits = $this->ORM->getRepository('Visit')->findAll();

        $cal = [];

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
                $cal[] = $row;
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
            $desc[] = 'Årskurs: '.$group->getGradeLabel();
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

            $cal[] = $row;
        }
        $this->formatted_array = $cal;

        return $cal;
    }


    public function convertToIcs(array $array)
    {
        date_default_timezone_set('Europe/Stockholm');

        $cal = new Cal('SigtunaNaturskola');
        $cal->setName('SigtunaNaturskola Schema');

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
            $cal->addComponent($event);
        }

        foreach ($this->ORM->getRepository('Event')->getEvents() as $ievent) {
            $cal->addComponent($ievent);
        }

        return $cal->render();
    }

    public function render()
    {
        $calendar_array = $this->rebuild();
        return $this->convertToIcs($calendar_array);
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
