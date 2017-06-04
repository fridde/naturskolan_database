<?php

namespace Fridde\Controller;

use Fridde\HTML;
use Carbon\Carbon;

class ViewController {

    private $N;
    private $params;
    private $H;
    private $page_translations = ["food_order" => "viewFoodOrder", "bus_order" => "viewBus"];

    public function __construct($params)
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->H = new HTML();
        $this->params = $params;
    }

    public function handleRequest()
    {
        $page = $this->params["page"];
        $method = $this->page_translations[$page] ?? "nothingFound";
        $this->$method();
    }

    private function viewFoodOrder()
    {
        $visits = $this->getVisitsWithFood();
        $collection = $this->indexIntoWeekAndDays($visits);
        array_walk_recursive($collection["calendar"], function(&$visit){
            $topic = $visit->getTopic();
            $group = $visit->getGroup();            
            $v = ["grade_label" => $visit->getGroup()->getGradeLabel()];
            $v["group_name"] = $group->getName();
            $v["topic_name"] = $topic->getShortName();
            $v["location"] = $topic->getLocation()->getName();
            $v["students_nr"] = $group->getNumberStudents();
            $v["diet"] = $group->getFood();
            $v["food_type"] = $topic->getFood();
            $visit = $v;
        });

        $DATA = $collection;

        $this->H->setTitle()->addNav();
        $this->H->setTemplate("food_order")->setBase();

        $this->H->addVariable("DATA", $DATA);
        $this->H->render();
    }

    private function viewBus()
    {
        $visits = $this->getVisitsWithBus();
        $locations = [];
        foreach($visits as $visit){
            $loc = $visit->getTopic()->getLocation();
            $string = $loc->getName() . " = ";
            $string .= $loc->getDescription() ?? "";
            $string .= empty($loc->getDescription()) ? "" : ", ";
            $string .= 'https://www.google.com/maps/?q=' . $loc->getCoordinates();
            $locations[$loc->getId()] = $string;
        }
        $collection = $this->indexIntoWeekAndDays($visits);
        array_walk_recursive($collection["calendar"], function(&$visit){
            $v = [];
            $g = $visit->getGroup();
            $v["school"] = $g->getSchool()->getName();
            $v["location"] = $visit->getTopic()->getLocation()->getName();
            // $v["departure"] = // TODO: Add method for departure
            // $v["return"] = // TODO: Add method for return
            $v["nr_passengers"] = $g->getNumberStudents() + 2 ;
            $visit = $v;
        });

        $DATA = $collection;
        $DATA["locations"] = $locations;

        $this->H->setTitle()->addNav();
        $this->H->setTemplate("bus_order")->setBase();

        $this->H->addVariable("DATA", $DATA);
        $this->H->render();
    }


    private function getVisitsWithBus()
    {
        $visits = $this->N->ORM->getRepository("Visit")->findFutureVisits();
        return array_filter($visits, function($v){
                return $v->needsBus();
        });
    }

    private function getVisitsWithFood()
    {
        $visits = $this->N->ORM->getRepository("Visit")->findFutureVisits();
        return array_filter($visits, function($v){
                return $v->needsFood();
        });
    }

    private function indexIntoWeekAndDays($visits)
    {
        $calendar = [];
        $date_strings = [];
        $index_day = Carbon::today()->subYears(2);
        foreach($visits as $visit){
            $date = $visit->getDate();
            $index = $index_day->diffInDays($date);
            $date_str = utf8_encode($date->formatLocalized('%a, %e %b'));
            $date_strings[$index] = $date_str;
            $w_nr = $date->weekOfYear;
            $calendar[$w_nr][$index][] = $visit;
        }
        return ["date_strings" => $date_strings, "calendar" => $calendar];

    }
}
