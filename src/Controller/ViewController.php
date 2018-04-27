<?php

namespace Fridde\Controller;

use Fridde\Entities\Visit;
use Fridde\Entities\VisitRepository;
use Fridde\HTML;
use Carbon\Carbon;

class ViewController extends BaseController {

    protected $ActionTranslator = ['food_order' => 'viewFoodOrder', 'bus_order' => 'viewBus'];

    public function handleRequest()
    {
        $this->addAction($this->getParameter('page'));
        parent::handleRequest();
    }

    public function viewFoodOrder()
    {
        $visits = $this->getVisitsWithFood();
        $collection = $this->indexIntoWeekAndDays($visits);
        array_walk_recursive($collection['calendar'], function(Visit &$visit){
            $topic = $visit->getTopic();
            $group = $visit->getGroup();            
            $v = ['grade_label' => $visit->getGroup()->getGradeLabel()];
            $v['group_name'] = $group->getName();
            $v['topic_name'] = $topic->getShortName();
            $v['location'] = $topic->getLocation()->getName();
            $v['students_nr'] = $group->getNumberStudents();
            $v['diet'] = $group->getFood();
            $v['food_type'] = $topic->getFood();
            $visit = $v;
        });

        $this->addToDATA($collection);
        $this->setTemplate('food_order');
    }

    public function viewBus()
    {
        $visits = $this->getVisitsWithBus();
        $locations = [];
        foreach($visits as $visit){
            /* @var Visit $visit  */
            $loc = $visit->getTopic()->getLocation();
            $string = $loc->getName() . ' = ';
            $string .= $loc->getDescription() ?? '';
            $string .= empty($loc->getDescription()) ? '' : ', ';
            $string .= 'https://www.google.com/maps/?q='.urlencode($loc->getCoordinates());
            $locations[$loc->getId()] = $string;
        }
        $collection = $this->indexIntoWeekAndDays($visits);
        array_walk_recursive($collection['calendar'], function(Visit &$visit){
            $v = [];
            $g = $visit->getGroup();
            $v['school'] = $g->getSchool()->getName();
            $v['location'] = $visit->getTopic()->getLocation()->getName();
            // $v['departure'] = // TODO: Add method for departure
            // $v['return'] = // TODO: Add method for return
            $nr_students = $g->getNumberStudents();
            $v['nr_passengers'] = null === $nr_students ? '???' : $nr_students + 2;
            $visit = $v;
        });

        $this->addToDATA($collection);
        $this->addToDATA('locations', $locations);
        $this->setTemplate('bus_order');
    }


    private function getVisitsWithBus()
    {
        $visits = $this->getVisitRepo()->findFutureVisits();
        return array_filter($visits, function(Visit $v){
                return $v->needsBus();
        });
    }

    private function getVisitsWithFood()
    {
        $visits = $this->getVisitRepo()->findFutureVisits();
        return array_filter($visits, function(Visit $v){
                return $v->needsFoodOrder();
        });
    }

    /**
     * @param array Visit[] $visits
     * @return array
     */
    private function indexIntoWeekAndDays(array $visits): array
    {
        $calendar = [];
        $date_strings = [];
        $index_day = Carbon::today()->subYears(2);
        foreach($visits as $visit){
            /* @var Visit $visit  */
            $date = $visit->getDate();
            $index = $index_day->diffInDays($date);
            $date_str = $date->formatLocalized('%a, %e %b');
            $date_strings[$index] = $date_str;
            $w_nr = $date->weekOfYear;
            $calendar[$w_nr][$index][] = $visit;
        }
        return ['date_strings' => $date_strings, 'calendar' => $calendar];

    }

    private function getVisitRepo()
    {
        /* @var VisitRepository $repo  */
        $repo = $this->N->ORM->getRepository('Visit');
        return $repo;
    }
}
