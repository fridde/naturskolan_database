<?php

namespace Fridde;

use \Fridde\Utility as U;
use \Eluceo\iCal\Component\Calendar as Cal;
use \Eluceo\iCal\Component\Event;


class Calendar
{
    public $settings;
    public $tables;
    public $formatted_array;
    public $file_name = "aventyr_kalender.ics";

    function __construct ($tables = [])
    {
        $this->setConfiguration();
        $this->tables = $tables;
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
        extract($this->tables);
        $cal_settings = $this->settings["calendar"];
        $coll = U::filterFor($users, ["School", "natu"]);
        $colleagues = array_combine(array_column($coll, "id"),  array_column($coll, "FirstName"));
        array_walk($colleagues, function(&$v) use ($cal_settings){
            $v = $cal_settings["colleagues"][$v] ?? $v;
        });

        $cal = [];

        foreach($visits as $visit){

            $group = U::filterFor($groups, ["id", $visit["Group"]]);
            $topic = U::filterFor($topics, ["id", $visit["Topic"]]);
            $school = U::filterFor($schools, ["id", $group["School"]]);
            $teacher = U::filterFor($users, ["id", $group["User"]]);
            $location = U::filterFor($locations, ["id", $topic["Location"]]);
            $is_lektion = trim($topic["IsLektion"]) == "true";

            $date = $visit["Date"];
            if(trim($visit["Time"]) != "" || $is_lektion){

                $dur = $cal_settings["lektion_duration"];
                $start_date_time = new \DateTime($date . " " . $visit["Time"]);
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
            $colleague_ids = array_map("trim", explode(",", $visit["Colleague"]));
            if(count($colleague_ids) > 0 && $colleague_ids[0] != ""){
                array_walk($colleague_ids, function(&$v) use ($colleagues) {
                    $v = $colleagues[$v] ?? "Not a colleague from Naturskolan!";
                });
                $title .= "[" . implode('+', $colleague_ids) . "]";
            }

            $grades_labels = ["2" => "åk 2/3", "5" => "åk 5", "fbk16" => "FBK F-6", "fbk79" => "FBK 7-9"];
            $title .= $topic["ShortName"] . " med ";
            $title .= $grades_labels[$group["Grade"]] . " från ";
            $title .= $school["Name"] . " ";
            $title .= "(" . $group["Name"] . ", " . $teacher["FirstName"] . " ";
            $title .= substr($teacher["LastName"], 0, 1) . ")";
            //temadag med åk från skola (klass, lärare)
            $row["title"] = $title;
            $row["location"] = $location["Name"];

            $description = [];
            $description[] = 'Tid: '.$start_date_time->format('H:i') . '-'.$end_date_time->format('H:i');
            $description[] = 'Lärare: '.$teacher['FirstName'].' '.$teacher['LastName'];
            $description[] = 'Årskurs: '.$grades_labels[$group["Grade"]];
            $description[] = 'Mobil: '.$teacher['Mobil'];
            $description[] = 'Mejl: '.$teacher['Mail'];
            $description[] = 'Klass ' . $group['Name'] . ' med '. $group['NumberStudents'] . ' elever';
            $description[] = 'Matpreferenser: ' . $group['Food'];
            $info = trim($group["Info"]);
            if($info != ""){
                $description[] = 'Annat: ' . $info;
            }
            $notes = trim($group["Notes"]);
            if($notes != ""){
                $description[] = 'Interna anteckningar: ' . $notes;
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
