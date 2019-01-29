<?php

namespace Fridde\Controller;

use Fridde\Annotations\SecurityLevel;
use Fridde\Entities\Group;
use Fridde\Entities\Topic;
use Fridde\Entities\User;
use Fridde\Entities\UserRepository;
use Fridde\Entities\Visit;
use Fridde\Entities\VisitRepository;
use Carbon\Carbon;
use Fridde\HTML;


/**
 * Class ViewController
 * @package Fridde\Controller
 *
 * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
 */
class ViewController extends BaseController
{

    public static $ActionTranslator = [
        'food_order' => 'viewFoodOrder',
        'bus_order' => 'viewBus',
        'mail' => 'viewMailTemplates'
    ];

    public function handleRequest()
    {
        $this->addAction($this->getParameter('page'));

        $this->addJsToEnd('admin', HTML::INC_ASSET);
        $this->addCss('admin', HTML::INC_ASSET);

        parent::handleRequest();
    }

    public function viewFoodOrder()
    {
        $visits = $this->getVisitsWithFood();
        $collection = $this->indexIntoWeekAndDays($visits);
        array_walk_recursive(
            $collection['calendar'],
            function (Visit &$visit) {
                $topic = $visit->getTopic();
                $group = $visit->getGroup();
                $v = ['segment_label' => $visit->getGroup()->getSegmentLabel()];
                $v['school_name'] = $group->getSchool()->getName();
                $v['group_name'] = $group->getName();
                $v['topic_name'] = $topic->getShortName();
                $v['location'] = $topic->getLocation()->getName();
                $v['students_nr'] = $group->getNumberStudents();
                $v['diet'] = $group->getFood();
                $v['food_type'] = $topic->getFood();
                $visit = $v;
            }
        );

        $collection['food_order_mail'] = SETTINGS['admin']['food_address'];

        $this->addToDATA($collection);
        $this->setTemplate('admin/food_order');
    }

    public function viewBus()
    {
        $visits = $this->getVisitsWithBus();
        $locations = [];
        foreach ($visits as $visit) {
            /* @var Visit $visit */
            $loc = $visit->getTopic()->getLocation();
            if(empty($loc)){
                throw new \Exception('Topic with id ' . $visit->getTopic()->getId() . ' has no location.');
            }
            $string = $loc->getName().' = ';
            $string .= $loc->getDescription() ?? '';
            $string .= empty($loc->getDescription()) ? '' : ', ';
            $string .= 'https://www.google.com/maps/?q='.urlencode($loc->getCoordinates());
            $locations[$loc->getId()] = $string;
        }
        $collection = $this->indexIntoWeekAndDays($visits);
        array_walk_recursive(
            $collection['calendar'],
            function (Visit &$visit) {
                $v = [];
                $g = $visit->getGroup();
                $v['school'] = $g->getSchool()->getName();
                $v['location'] = $visit->getTopic()->getLocation()->getName();
                // $v['departure'] = // TODO: Add method for departure
                // $v['return'] = // TODO: Add method for return
                $nr_students = $g->getNumberStudents();
                $v['nr_passengers'] = null === $nr_students ? '???' : $nr_students + 2;
                $visit = $v;
            }
        );

        $collection['bus_order_mail'] = SETTINGS['admin']['bus_address'];

        $this->addToDATA($collection);
        $this->addToDATA('locations', $locations);
        $this->setTemplate('admin/bus_order');
    }

    public function viewMailTemplates()
    {
        $data = $this->compileMailData();

        $this->addToDATA($data);

        $this->setTemplate('admin/mail_templates');
    }

    public function compileMailData(): array
    {
        /* @var UserRepository $u_repo */
        $u_repo = $this->N->getRepo('User');

        $users = $u_repo->findActiveUsersWithVisitingGroups();

        $users_by_segments = [];
        $groups_by_users = [];
        $topics = array_map(
            function (Topic $t) {
                $r = [];
                $r['id'] = $t->getId();
                $r[0]['url'] = $t->getUrl();
                $r[0]['name'] = $t->getLongestName();
                return $r;
            },
            $this->N->getRepo('Topic')->findAll()
        );
        $topics = array_column($topics, 0, 'id');

        foreach ($users as $u) {
            /* @var User $u */
            $u_data = [];
            $u_data['id'] = $u->getId();
            $u_data['mail'] = $u->getMail();
            $u_data['mobil'] = $u->getMobil();
            $u_data['fname'] = $u->getFirstName();
            $u_data['full_name'] = $u->getFullName();
            $u_data['next_visit'] = null;
            $u_data['file_name'] = self::createFileNameForHtmlMail($u_data['fname'], $u_data['mail']);

            $groups = $u->getGroups();
            foreach ($groups as $g) {
                /* @var Group $g */
                if ($g->isActive()) {
                    $g_data = [];
                    $g_data['name'] = $g->getName();
                    $g_data['number_students'] = $g->getNumberStudents();
                    $g_data['food'] = $g->getFood();
                    $g_data['info'] = $g->getInfo();

                    $u_data['group_names'][] = $g->getName();

                    $visits = $g->getFutureVisits();
                    foreach($visits as $v){
                        /* @var Visit $v  */
                        $v_data = [];
                        $v_id = $v->getId();
                        $v_data['id'] = $v_id;
                        $v_data['topic_id'] = $v->getTopicId();
                        $d = $v->getDate();
                        $dstring = $d->day . ' ' . $d->locale('sv')->shortMonthName . ' ' . $d->year;
                        $v_data['date'] = $dstring;

                        /* @var Carbon $next_visit_date  */
                        $next_visit_date = $u_data['next_visit']['carbon_date'];
                        if(empty($next_visit_date) || $next_visit_date->gt($v->getDate())){
                            $nv = $v_data;
                            $nv['carbon_date'] = $v->getDate();
                            $u_data['next_visit'] = $nv;
                        }

                        $g_data['visits'][$v_id] = $v_data;
                    }
                    $g_data['first_visit_id'] = reset($g_data['visits'])['id'];

                    $users_by_segments[$g->getSegment()][$u->getId()] = $u_data;
                    $groups_by_users[$u->getId()][] = $g_data;
                }
            }
        }

        array_walk($users_by_segments, function(&$users){
            usort($users, function($u1, $u2){return strcmp($u1['full_name'], $u2['full_name']);});
        });
        $current_year = Carbon::today()->year;

        return compact('users_by_segments', 'groups_by_users', 'topics', 'current_year');
    }

    private static function createFileNameForHtmlMail(string $name = null, string $id = null): string
    {
        $name = $name ?? '???';
        $id = $id ?? '???';

        $regex = '/[^a-zA-Z0-9]/';

        $file = $name . '_' . $id;
        $file = preg_replace($regex, '_', $file);
        $file .= '.html';

        return $file;
    }

    private function getVisitsWithBus()
    {
        $visits = $this->getVisitRepo()->findFutureVisits();

        return array_filter(
            $visits,
            function (Visit $v) {
                return $v->needsBus();
            }
        );
    }

    private function getVisitsWithFood()
    {
        $visits = $this->getVisitRepo()->findFutureVisits();

        return array_filter(
            $visits,
            function (Visit $v) {
                return $v->needsFoodOrder();
            }
        );
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
        foreach ($visits as $visit) {
            /* @var Visit $visit */
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
        /* @var VisitRepository $repo */
        $repo = $this->N->ORM->getRepository('Visit');

        return $repo;
    }
}
