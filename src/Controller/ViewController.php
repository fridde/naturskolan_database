<?php

namespace Fridde\Controller;

use Fridde\Entities\Group;
use Fridde\Entities\Topic;
use Fridde\Entities\TopicRepository;
use Fridde\Entities\User;
use Fridde\Entities\UserRepository;
use Fridde\Entities\Visit;
use Fridde\Entities\VisitRepository;
use Carbon\Carbon;
use Fridde\HTML;
use Fridde\Utility;
use Fridde\Annotations\SecurityLevel; // don't remove! used in annotations


/**
 * Class ViewController
 * @package Fridde\Controller
 *
 * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
 */
class ViewController extends BaseController
{
    public const CONFIRMATION = "confirmation";
    public const INCOMPLETE_PROFILE = "incomplete_profile";
    public const NEW = "new";
    public const CONTINUED = "continued";

    private const MAIL_SUBJECT_LABELS = [
        self::CONFIRMATION => 'Bekräfta ditt besök!',
        self::INCOMPLETE_PROFILE => 'Vi behöver mer information från dig!',
        self::NEW => 'Året med Naturskolan börjar!',
        self::CONTINUED => 'Snart fortsätter året med Naturskolan'
    ];


    public static $ActionTranslator = [
        'food_order' => 'viewFoodOrder',
        'bus_order' => 'viewBus',
        'mail' => 'viewMailTemplates',
    ];

    public function handleRequest(): void
    {
        $this->addAction($this->getParameter('page'));

        $this->addJsToEnd('admin', HTML::INC_ASSET);
        $this->addCss('admin', HTML::INC_ASSET);

        parent::handleRequest();
    }

    public function viewFoodOrder(): void
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

    public function viewBus(): void
    {
        $visits = $this->getVisitsWithBus();
        $locations = [];
        foreach ($visits as $visit) {
            /* @var Visit $visit */
            $loc = $visit->getTopic()->getLocation();
            if (empty($loc)) {
                throw new \Exception('Topic with id '.$visit->getTopic()->getId().' has no location.');
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

    public function viewMailTemplates(string $subject = null, string $segment = null)
    {
        $data = $this->compileMailData($segment);
        $data['subjects'] = self::MAIL_SUBJECT_LABELS;
        $data['chosen_subject_id'] = $subject;
        $data['chosen_segment_id'] = $segment;
        $this->addToDATA($data);

        $this->setTemplate('admin/mail_templates');
    }

    public function compileMailData(string $segment = null): array
    {
        /* @var UserRepository $u_repo */
        $u_repo = $this->N->getRepo('User');
        /* @var TopicRepository $u_repo */
        $t_repo = $this->N->getRepo('Topic');

        $topics = array_map(
            function (Topic $t) {
                $r = [];
                $r['id'] = $t->getId();
                $r[0]['url'] = $t->getUrl();
                $r[0]['name'] = $t->getLongestName();

                return $r;
            },
            $t_repo->findAll()
        );
        $topics = array_column($topics, 0, 'id');

        $criteria = ['visiting' => true, 'in_future' => true, 'in_segment' => $segment];
        $selected_users = $u_repo->all()->active()->hasGroupsWithCriteria($criteria)->fetch();

        if(empty($selected_users)){
            return [];
        }

        $groups_by_users = [];
        foreach ($selected_users as $u) {
            /* @var User $u */
            $u_data = [];
            $u_data['id'] = $u->getId();
            $u_data['mail'] = $u->getMail();
            $u_data['mobil'] = $u->getMobil();
            $u_data['fname'] = $u->getFirstName();
            $u_data['full_name'] = $u->getFullName();
            $u_data['school_url'] = $this->N->generateUrl('school', ['school' => $u->getSchoolId()]);

            $u_data['file_name'] = self::createFileNameForHtmlMail($u_data['full_name'], $u_data['id']);
            $u_data['segments'] = [];

            $u_data['login_url'] = $this->N->createLoginUrl($u);

            $u_data['groups'] = [];

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
                    $u_data['segments'][] = $g->getSegment();

                    $visits = $g->getFutureVisits(); // is already sorted, but can be empty
                    /* @var Visit $next_visit */
                    $next_visit = null;
                    $nv_data = null;
                    foreach ($visits as $v) {
                        /* @var Visit $v */
                        $v_data = [];
                        $v_id = $v->getId();
                        $v_data['id'] = $v_id;
                        $v_data['topic_id'] = $v->getTopicId();
                        $v_data['topic_label'] = $v->getTopic()->getLongestName();
                        $v_data['topic_url'] = $v->getTopic()->getUrl();
                        $d = $v->getDate();
                        $dstring = $d->day.' '.$d->locale('sv')->shortMonthName.' '.$d->year;
                        $v_data['date_string'] = $dstring;

                        if (empty($next_visit) || $next_visit->getDate()->gt($d)) {
                            $next_visit = $v;
                            $nv_data = $v_data;
                        }

                        $g_data['visits'][$v_id] = $v_data;
                    }
                    $g_data['first_visit_id'] = null;
                    if (!empty($g_data['visits'])) {
                        $g_data['first_visit_id'] = array_values($g_data['visits'])[0]['id'];
                    }
                    $u_data['groups'][] = $g_data;
                }
            }

            if(!empty($next_visit)){
                $nv_data['confirmation_url'] = $this->N->createConfirmationUrl($next_visit);
                $nv_data['group_name'] = $next_visit->getGroup()->getName();
                $u_data['next_visit'] = $nv_data;
            }
            $u_data['segments'] = array_unique($u_data['segments']);
            $users[$u->getId()] = $u_data;
        }

        usort(
            $users,
            function ($u1, $u2) {
                return strcmp($u1['full_name'], $u2['full_name']);
            }
        );


        return compact('users', 'groups_by_users', 'topics');
    }

    private static function createFileNameForHtmlMail(string $name = null, string $id = null): string
    {
        $name = $name ?? 'XXX';
        $id = $id ?? 'XXX';

        $file = Utility::convertToAscii(mb_strtolower($name).'_'.$id);
        $file = Utility::replaceNonAlphaNumeric($file);
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
