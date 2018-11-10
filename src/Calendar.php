<?php

namespace Fridde;

use Eluceo\iCal\Component\Calendar as Cal;
use Eluceo\iCal\Component\Event as IcalEvent;
use Carbon\Carbon;
use Fridde\Entities\EventRepository;
use Fridde\Entities\Note;
use Fridde\Entities\Visit;
use Fridde\Entities\VisitRepository;
use Fridde\Error\Error;
use Fridde\Error\NException;

class Calendar
{
    public $settings;
    /* @var Naturskolan $N  */
    private $N;

    public $file_name = 'kalender.ics';

    public function __construct()
    {
        date_default_timezone_set('Europe/Stockholm');
        $this->setConfiguration();
        $this->N = $GLOBALS['CONTAINER']->get('Naturskolan');
    }


    public function setConfiguration($settings = null)
    {
        $this->settings = $settings ?? (defined('SETTINGS') ? SETTINGS : false);
        if ($this->settings === false) {
            throw new NException(Error::MISSING_SETTINGS);
        }
    }

    private function getEventsFromVisitsTable()
    {
        $cal_settings = $this->settings['calendar'];
        /* @var VisitRepository $visit_repo */
        $visit_repo = $this->N->ORM->getRepository('Visit');
        $visits = $visit_repo->findAllActiveVisits();

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
                    $end_DT = $start_DT->copy();
                    $dur = $is_lektion
                        ? $cal_settings['lektion_duration']
                        : $cal_settings['default_event_duration'];
                    Timing::addDuration($dur, $end_DT);
                } else {
                    $end_DT->hour($time['end']['hh'])->minute($time['end']['mm']);
                }
            } else {
                $def_times = $cal_settings['default_times'];
                $times = $def_times[$group->getSegment()] ?? $def_times['standard'];

                $start_DT->hour($times[0][0]);
                $start_DT->minute($times[0][1]);
                $end_DT->hour($times[1][0]);
                $end_DT->minute($times[1][1]);
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
            $desc[] = '';

            $notes = array_map(
                function (Visit $v) {
                    return $v->getNotes();
                },
                array_reverse($group->getSortedVisits())
            );
            $notes = array_merge(...$notes);

            if(!empty($notes)){

                $desc[] = 'Egna anteckningar:';
                foreach ($notes as $note) {
                    /* @var Note $note */
                    $desc[] = '('.$note->getUser()->getAcronym().') '.$note->getText();
                }
            }

            $link = 'Lägg till anteckning: ';
            $link .= $this->N->generateUrl('note', ['visit_id' => $visit->getId()], true);
            $desc[] = '';
            $desc[] = $link;
            $event->setDescription(implode("\r\n", $desc));

            $ics_events[] = $event;
        }

        return $ics_events;
    }

    public function getEventsFromEventsTable(): array
    {
        /* @var EventRepository $event_repo */
        $event_repo = $this->N->ORM->getRepository('Event');

        return $event_repo->getEvents();
    }


    public function convertEventArrayToIcs(array $array): string
    {
        $cal = new Cal('SigtunaNaturskola');
        $cal->setName('SigtunaNaturskola Schema nya versionen');

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

    public function save(string $file_name = null)
    {
        $dir = defined('BASE_DIR') ? BASE_DIR : '';
        $file_name = $file_name ?? ($this->file_name ?? null);
        if (!$file_name) {
            throw new NException(Error::INVALID_ARGUMENT, ['file_name']);
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
