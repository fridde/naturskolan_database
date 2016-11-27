<?php

namespace Fridde;

use \Fridde\Utility as U;
use \Fridde\Entities\{School, Visit};
use \Eluceo\iCal\Component\Calendar as Cal;
use \Eluceo\iCal\Component\Event;
use Fridde\Naturskolan;


class Calendar
{
    public $settings;
    private $N;
    public $formatted_array;
    public $file_name = "aventyr_kalender.ics";

    function __construct ()
    {
        $this->setConfiguration();
        //$this->N = new Naturskolan;
    }


    public function setConfiguration($settings = null)
    {
        $settings = $settings ?? ($GLOBALS["SETTINGS"] ?? false);
        if($settings === false){
            throw new \Exception("No settings given or found in the global scope");
        }
        $this->settings = $settings;
    }

    public function rebuild(){

        $cal_settings = $this->settings["calendar"];
        $naturskolan = new School("natu");
        $coll = $naturskolan->getUsers();
        $colleagues = array_combine(array_column($coll, "id"),  array_column($coll, "FirstName"));
        $visits = $naturskolan->getTable("visits");

        $cal = [];

        foreach($visits as $visit_row){
            $visit = new Visit($visit_row);

            $group = $visit->getAsObject("Group");
            $topic = $visit->getAsObject("Topic");
            $school = $group->getAsObject("School");
            $teacher = $group->getAsObject("User");
            $location = $topic->getAsObject("Location");
            $is_lektion = $topic->isLektion();

            $date = $visit->pick("Date");
            if($visit->has("Time") && $is_lektion){
                $dur = $cal_settings["lektion_duration"];
                $start_date_time = new \DateTime($date . " " . $visit->pick("Time"));
                $end_date_time = clone($start_date_time);
                $end_date_time->modify("+ " . $dur);
            } else {
                $start_date_time_string = $end_date_time_string = $date . " ";
                $start_date_time_string .= $cal_settings["default_start_time"];
                $end_date_time_string .= $cal_settings["default_end_time"];
                $start_date_time = new \DateTime($start_date_time_string);
                $end_date_time =  new \DateTime($end_date_time_string);
            }

            $row["start_date_time"] = $start_date_time->format("c");
            $row["end_date_time"] = $end_date_time->format("c");

            $row["whole_day"] = "false";

            $title = "";
            $colleagues = $visit->getColleagues();
            if(!empty($colleagues)){
                $acronyms = array_map(function($u){return $u->getShortName();}, $colleagues);
                $title .= "[" . implode('+', $acronyms) . "]";
            }

            $title .= $topic->pick("ShortName") . " med ";
            $title .= $group->getGradeLabel() . " från ";
            $title .= $school->pick("Name") . " ";
            $title .= "(" . $group->pick("Name") . ", " . $teacher->getShortName() . ")";
            //temadag med åk från skola (klass, lärare)
            $row["title"] = $title;
            $row["location"] = $location->pick("Name");

            $description = [];
            $description[] = 'Tid: '.$start_date_time->format('H:i') . '-'.$end_date_time->format('H:i');
            $description[] = 'Lärare: '.$teacher->getCompleteName();
            $description[] = 'Årskurs: '. $group->getGradeLabel();
            $description[] = 'Mobil: '.$teacher->pick('Mobil');
            $description[] = 'Mejl: '.$teacher->pick('Mail');
            $description[] = 'Klass ' . $group->pick('Name') . ' med '. $group->pick('NumberStudents') . ' elever';
            $description[] = 'Matpreferenser: ' . $group->pick('Food');
            if($group->has("Info")){
                $description[] = 'Annat: ' . $group->pick("Info");
            }
            if($group->has("Notes")){
                $description[] = 'Interna anteckningar: ' . $group->pick("Notes");
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
            if($row["whole_day"] == "true"){
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
        $file_name = $file_name ?? ($this->file_name ?? false);
        if(!$file_name){
            throw new \Exception("Tried to save the Calendar without a file name.");
        }
        return file_put_contents($file_name, $this->render());

    }
}
