<?php

namespace Fridde;

use \Fridde\{Utility as U, ORM};
use \Eluceo\iCal\Component\{Calendar as Cal, Event};
use \Carbon\Carbon;

class Calendar
{
    public $settings;
    private $ORM;

    public $formatted_array;
    public $file_name = "aventyr_kalender.ics";

    function __construct ()
    {
        $this->setConfiguration();
        $this->ORM = new ORM();
    }


    public function setConfiguration($settings = null)
    {
        $settings = $settings ?? (defined(SETTINGS) ? SETTINGS : false);
        if($settings === false){
            throw new \Exception("No settings given or found in the global scope");
        }
        $this->settings = $settings;
    }

    public function rebuild(){

        $cal_settings = $this->settings["calendar"];
        $visits = $this->ORM->getRepository("Visit")->findAll();

        $cal = [];

        foreach($visits as $visit){


            $group = $visit->getGroup();
            $topic = $visit->getTopic();
            $school = $group->getSchool();
            $teacher = $group->getUser();
            $location = $topic->getLocation();
            $is_lektion = $topic->getIsLektion();

            $date = $visit->getDate(); // is already Carbon
            $start_DT = $date->copy();
            $end_DT = $date->copy();

            if($visit->hasTime() && $is_lektion){
                $time = $visit->getTimeAsArray();
                $start_DT->hour($time["start"]["hh"])->minute($time["start"]["mm"]);
                if(empty($time["end"])){
                    $dur = $cal_settings["lektion_duration"];
                    $end_DT = $start_DT->copy()->addMinutes($dur);
                } else {
                    $end_DT->hour($time["end"]["hh"])->minute($time["end"]["mm"]);
                }
            } else {
                $start_DT->hour($cal_settings["default_start_time"][0]);
                $start_DT->minute($cal_settings["default_start_time"][1]);
                $end_DT->hour($cal_settings["default_end_time"][0]);
                $end_DT->minute($cal_settings["default_end_time"][1]);
            }

            $row["start_date_time"] = $start_DT->toIso8601String();
            $row["end_date_time"] = $end_DT->toIso8601String();

            $row["whole_day"] = false;

            $title = "";
            $colleagues = $visit->getColleagues()->toArray();
            if(!empty($colleagues)){
                $acronyms = array_map(function($u){return $u->getAcronym();}, $colleagues);
                $title .= "[" . implode('+', $acronyms) . "]";
            }

            $title .= $topic->getShortName() . " med ";
            $title .= $group->getGradeLabel() . " från ";
            $title .= $school->getName() . " ";
            $title .= "(" . $group->getName() . ", " . $teacher->getShortName() . ")";
            //temadag med åk från skola (klass, lärare)
            $row["title"] = $title;
            $row["location"] = $location->getName();

            $description = [];
            $description[] = 'Tid: '.$start_DT->toTimeString() . '-'.$end_DT->toTimeString();
            $description[] = 'Lärare: '.$teacher->getFullName();
            $description[] = 'Årskurs: '. $group->getGradeLabel();
            $description[] = 'Mobil: '.$teacher->getMobil();
            $description[] = 'Mejl: '.$teacher->getMail();
            $description[] = 'Klass ' . $group->getName() . ' med '. $group->getNumberStudents() . ' elever';
            $description[] = 'Matpreferenser: ' . $group->getFood();
            if($group->hasInfo()){
                $description[] = 'Annat: ' . $group->getInfo();
            }
            if($group->hasNotes()){
                $description[] = 'Interna anteckningar: ' . $group->getNotes();
            }
            $row["description"] = $description;

            $cal[] = $row;
        }
        $this->formatted_array = $cal;
        return $cal;
    }



    public function convertToIcs($array)
    {
        date_default_timezone_set('Europe/Stockholm');

        $cal = new Cal('SigtunaNaturskola');
        $cal->setName("SigtunaNaturskola Schema");

        foreach($array as $row){
            $event = new Event();
            $event->setDtStart(new \DateTime($row["start_date_time"]));
            $event->setDtEnd(new \DateTime($row["end_date_time"]));
            if($row["whole_day"]){
                $event->setNoTime(true);
            }
            $event->setSummary($row["title"]);
            $event->setDescription(implode("\r\n", $row["description"]));
            $event->setLocation($row["location"]);

            $event->setUseTimezone(true);
            $cal->addComponent($event);
        }

        return $cal->render();
    }

    public function render()
    {
        $calendar_array = $this->rebuild();
        $ics_string = $this->convertToIcs($calendar_array);
        return $ics_string;
    }

    public function save($file_name = null)
    {
        $dir = $GLOBALS["BASE_DIR"] ?? "";
        $file_name = $file_name ?? ($this->file_name ?? false);
        if(!$file_name){
            throw new \Exception("Tried to save the Calendar without a file name.");
        }
        $file_name = empty($dir) ? $file_name : $dir . $file_name;

        return file_put_contents($file_name, $this->render());
    }

    public function dateStringToArray($date_string)
    {
        $date = new Carbon($date_string);
        $array[0] = $date->year;
        $array[1] = $date->month;
        $array[2] = $date->day;

        return $array;
    }


}
