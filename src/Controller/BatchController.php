<?php

namespace Fridde\Controller;

use Carbon\Carbon;
use Fridde\Annotations\SecurityLevel;  # don't remove
use Fridde\Entities\Group;
use Fridde\Entities\GroupRepository;
use Fridde\Entities\Location;
use Fridde\Entities\LocationRepository;
use Fridde\Entities\Message;
use Fridde\Entities\School;
use Fridde\Entities\SchoolRepository;
use Fridde\Entities\Topic;
use Fridde\Entities\TopicRepository;
use Fridde\Entities\User;
use Fridde\Entities\UserRepository;
use Fridde\Entities\Visit;
use Fridde\Entities\VisitRepository;
use Fridde\HTML;
use Fridde\Messenger\Mail;
use Fridde\Task;
use Fridde\Utility;


/**
 * Class BatchController
 * @package Fridde\Controller
 *
 * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
 */
class BatchController extends BaseController
{
    public function handleRequest(): ?string
    {
        $this->addToDATA('school_id', 'natu');
        $this->setParameter('school', 'natu');
        $this->addJsToEnd('admin', HTML::INC_ASSET);
        $this->addCss('admin', HTML::INC_ASSET);
        return parent::handleRequest();
    }

    public function addDates(): void
    {
        /* @var \Fridde\Entities\TopicRepository $topic_repo */
        $topic_repo = $this->N->getRepo('Topic');
        $topics = $topic_repo->findLabelsForTopics();
        $topic_array = array_map(
            function ($key, $value) {
                return ['id' => $key, 'label' => $value];
            },
            array_keys($topics),
            $topics
        );

        $this->setTemplate('admin/add_dates');
        $this->addToDATA('topics', $topic_array);
    }

    /**
     * @example distributeVisitsExample.php
     * @param string|null $segment_id
     * @param int|null $start_year
     * @return void
     */
    public function distributeVisits(string $segment_id = null, int $start_year = null): void
    {
        /* @var GroupRepository $group_repo */
        $group_repo = $this->N->ORM->getRepository('Group');
        /* @var TopicRepository $topic_repo */
        $topic_repo = $this->N->ORM->getRepository('Topic');
        /* @var VisitRepository $visit_repo */
        $visit_repo = $this->N->ORM->getRepository('Visit');

        $segment_labels = Group::getSegmentLabels();
        $segment_id = $segment_id ?? (string) array_keys($segment_labels)[0];
        $start_year = $start_year ?? Carbon::today()->year;
        $criteria = [['Segment', $segment_id], ['StartYear', $start_year]];
        $criteria[] = ['Segment', $segment_id], ['Status', Group::ACTIVE];

        $groups = $group_repo->selectAnd($criteria);
        $groups = array_values($group_repo->sortByVisitOrder($groups));

        $this->addToDATA(
            'groups',
            array_map(
                function (Group $g) {
                    return [
                        'id' => $g->getId(),
                        'name' => $g->getName(),
                        'school' => $g->getSchoolId(),
                    ];
                },
                $groups
            )
        );

        $this->addToDATA(
            'segments',
            array_map(
                function ($val, $label) {
                    $r = ['label' => $label];
                    $url_params = ['action' => 'distribute_visits', 'parameters' => $val];
                    $r['url'] = $this->N->generateUrl('batch', $url_params);

                    return $r;
                },
                array_keys($segment_labels),
                $segment_labels
            )
        );

        // rows: groups, columns: topics
        $group_visits = [];
        $topic_visit_count = [];
        $row_to_group_translator = [];
        /* @var Group $group */
        /* @var Visit $visit */
        foreach ($groups as $key => $group) {
            $group_id = $group->getId();
            $visits = $group->getVisits();
            $row_to_group_translator[$key] = $group_id;
            foreach ($visits as $visit) {
                $topic_id = $visit->getTopic()->getId();
                $group_visits[$group_id][$topic_id] = $visit;
                $topic_visit_count[$topic_id] = 1 + ($topic_visit_count[$topic_id] ?? 0);
            }
        }

        $future_visits = $visit_repo->findFutureVisits();

        $relevant_topics = [];
        foreach($future_visits as $visit){
            $topic = $visit->getTopic();
            $topic_id = $topic->getId();
            $topic_segment = $topic->getSegment();
            if($topic_segment === $segment_id && empty($relevant_topics[$topic_id])){
                $relevant_topics[$topic_id] = $topic;
            }
        }
        $relevant_topics = $topic_repo->sortByVisitOrder($relevant_topics);

        // will contain all visits without a group in the form
        // [[topic_id] => [visit_id => visit_object, ...], ...]
        $topic_orphaned_visits = [];
        foreach ($future_visits as $v) {
            /* @var Visit $v */
            $topic_id = $v->getTopic()->getId();
            if (!$v->hasGroup()) {
                $topic_orphaned_visits[$topic_id][$v->getId()] = $v;
                $topic_visit_count[$topic_id] = 1 + ($topic_visit_count[$topic_id] ?? 0);
            }
        }

        $max_height = empty($topic_visit_count) ? 0 : max($topic_visit_count);

        $date_columns = [];

        /* @var Topic $topic */
        foreach ($relevant_topics as $topic) {
            $topic_id = $topic->getId();

            $column = [
                'id' => $topic_id,
                'name' => $topic->getShortName(),
                'serial' => $topic->getSegment().'.'.$topic->getVisitOrder(),
            ];

            $visit_rows = [];
            foreach (range(0, $max_height) as $row_index) {
                $group_id = $row_to_group_translator[$row_index] ?? null;
                $row_for_group_with_visit = !empty($group_visits[$group_id][$topic_id]);
                $still_orphans_left = !empty($topic_orphaned_visits[$topic_id]);
                if ($row_for_group_with_visit) {
                    $visit = $group_visits[$group_id][$topic_id];
                    $visit_rows[$row_index] = [
                        'id' => $visit->getId(),
                        'date' => $visit->getDateString(),
                        'has_group' => true,
                        'position' => $row_index,
                    ];
                } elseif ($still_orphans_left) {
                    $visit = array_shift($topic_orphaned_visits[$topic_id]);
                    $visit_rows[$row_index] = [
                        'id' => $visit->getId(),
                        'date' => $visit->getDateString(),
                        'has_group' => false,
                        'position' => $row_index,
                    ];
                } else {
                    $visit_rows[$row_index] = null;
                }
            }
            $column['visits'] = $visit_rows;
            $date_columns[] = $column;
        }


        $this->addToDATA('date_columns', $date_columns);
        $this->setTemplate('admin/distribute_visits');
    }

    public function setColleagues(): void
    {
        /* @var VisitRepository $visit_repo */
        /* @var UserRepository $user_repo */
        $visit_repo = $this->N->ORM->getRepository('Visit');
        $user_repo = $this->N->ORM->getRepository('User');

        $future_visits = $visit_repo->findFutureVisits();
        $colleagues = $user_repo->getActiveColleagues();

        $DATA['colleagues'] = array_map(
            function (User $c) {
                return ['id' => $c->getId(), 'label' => $c->getAcronym()];
            },
            $colleagues
        );
        $row_colors = array_values(Utility::getGoodBGColors());
        $count_row_colors = count($row_colors);
        $DATA['visits'] = array_map(
            function (Visit $visit) use ($row_colors, $count_row_colors) {
                $r = ['id' => $visit->getId()];
                $r['colleagues'] = $visit->getColleaguesIdArray();
                $date = $visit->getDate();
                $r['date'] = $visit->getDateString();
                $r['weekday'] = $date->formatLocalized('%a');
                $r['weeknum'] = $date->weekOfYear;
                $col_index = (($date->weekOfYear * 5) + ($date->dayOfWeek - 1)) % $count_row_colors;
                $r['row_color'] = $row_colors[$col_index];
                $r['label'] = $visit->getLabel('TGSU');

                return $r;
            },
            $future_visits
        );
        $this->addToDATA($DATA);
        $this->setTemplate('admin/set_colleagues');
    }


    public function setBookings(): void
    {
        /* @var VisitRepository $visit_repo */
        $visit_repo = $this->N->ORM->getRepository('Visit');

        $this->setTemplate('admin/set_bookings');
        $visits = $visit_repo->findFutureVisits();

        $visits = array_filter(
            $visits,
            function (Visit $v) {
                return $v->needsBus() || $v->needsFoodOrder();
            }
        );

        $this->addToDATA(
            'visits',
            array_map(
                function (Visit $visit) {
                    $label = $visit->getTopic()->getShortName().' med ';
                    $label .= $visit->getGroup()->getName();
                    $label .= ' från '.$visit->getGroup()->getSchool()->getName();

                    // TODO: Decide if row color is needed

                    return [
                        'id' => $visit->getId(),
                        'date' => $visit->getDateString(),
                        'label' => $label,
                        'needs_bus' => $visit->needsBus(),
                        'needs_food' => $visit->needsFoodOrder(),
                        'has_food' => $visit->getFoodIsBooked(),
                        'has_bus' => $visit->getBusIsBooked(),
                        // here goes row color if needed
                    ];
                },
                $visits
            )
        );
    }

    public function setBusSettings(): void
    {
        /* @var SchoolRepository $school_repo */
        /* @var LocationRepository $location_repo */
        /* @var School $school */
        $school_repo = $this->N->ORM->getRepository('School');
        $location_repo = $this->N->ORM->getRepository('Location');

        $schools = $school_repo->findAll();
        $locations = $location_repo->findAll();

        $this->addToDATA(
            'locations',
            array_map(
                function (Location $l) {
                    return [
                        'id' => $l->getId(),
                        'label' => $l->getName(),
                    ];
                },
                $locations
            )
        );

        $school_data = [];
        foreach ($schools as $school) {
            $id = $school->getId();
            $school_data[$id]['id'] = $id;
            $school_data[$id]['label'] = $school->getName();
            $school_data[$id]['bus_needed'] = [];
            foreach ($locations as $location) {
                /* @var Location $location */
                if ($school->needsBus($location)) {
                    $school_data[$id]['bus_needed'][] = $location->getId();
                }
            }
        }

        $this->addToDATA('schools', $school_data);
        $this->setTemplate('admin/bus_settings');
    }

    public function setVisitOrderForTopics(): void
    {
        /* @var TopicRepository $topic_repo */
        $topic_repo = $this->N->ORM->getRepository('Topic');

        $topics = $topic_repo->findOrderableTopics();
        $topics = $topic_repo->sortByVisitOrder($topics);
        $topics = $topic_repo->sliceBySegment($topics);


        $topics = array_map(
            function (Topic $t) {
                return [
                    'id' => $t->getId(),
                    'label' => $t->getShortName(),
                ];
            },
            $topics
        );

        $this->addToDATA('topics', $topics);
        $this->setTemplate('admin/topic_visit_order');
    }

    public function sendManagerMobilizationMail(): void
    {
        /* @var User $manager */
        /* @var UserRepository $user_repo */
        $user_repo = $this->N->ORM->getRepository(User::class);

        $subject_int = Message::SUBJECT_MANAGER_MOBILIZATION;

        $managers = $user_repo->getActiveManagers();
        $messages = [];
        foreach ($managers as $manager) {
            if (!$manager->hasMessageSetting(Message::SUBJECT_MANAGER_MOBILIZATION)) {
                continue;
            }
            if (!$manager->hasMail()) {
                $msg = 'Manager '.$manager->getFullName().' has no mailaddress. Check this!';
                $this->N->log($msg, __METHOD__);
                continue;
            }
            $params = ['subject_int' => $subject_int];
            $params['receiver'] = $manager->getMail();
            $data['fname'] = $manager->getFirstName();
            $data['school_name'] = $manager->getSchool()->getName();
            $data['school_url'] = $this->N->generateUrl('school', ['school' => $manager->getSchoolId()], true);
            $data['user_login_url'] = $this->N->createLoginUrl($manager);

            $params['data'] = $data;

            $mail = new Mail($params);
            $response = $mail->buildAndSend();

            $messages[] = [$response, Message::CARRIER_MAIL, $manager, $subject_int];
        }

        (new Task())->logMessageArray($messages);

        $sent_mails = array_map(
            function ($m) {
                /* @var Mail $response */
                /* @var User $user */
                [$response,,$user] = $m;
                $text = 'Mejlet till ';
                $text .= $user->getMail().' ';
                if ($response->getStatus() === 'success') {
                    $text .= 'skickades framgångsrikt.';
                } else {
                    $text .= 'kunde ej skickas. Kolla felloggen';
                }

                return $text;
            },
            $messages
        );

        $this->setReturnType(self::RETURN_JSON);
        $this->addToDATA('onReturn', $this->getFromRequest('onReturn'));
        $this->addToDATA('sent_mails', $sent_mails);
        $this->addToDATA('errors', []);
    }

}
