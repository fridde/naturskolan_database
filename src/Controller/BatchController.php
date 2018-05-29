<?php

namespace Fridde\Controller;

use Fridde\Entities\Group;
use Fridde\Entities\GroupRepository;
use Fridde\Entities\Topic;
use Fridde\Entities\TopicRepository;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Entities\VisitRepository;
use Fridde\Security\Authorizer;
use Fridde\Utility as U;
use Fridde\Utility;

class BatchController extends BaseController
{
    protected $Security_Levels = ['*' => Authorizer::ACCESS_ADMIN_ONLY];


    public function handleRequest()
    {
        $this->addToDATA('school_id', 'natu');
        parent::handleRequest();
    }

    /**
     * @route admin/batch/
     */
    public function addDates()
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

        $this->setTemplate('add_dates');
        $this->addToDATA('topics', $topic_array);
    }

    /**
     * @example setVisitsExample.php
     * @return void
     */
    public function setVisits($segment_id = null, $start_year = null)
    {
        $segment_labels = Group::getSegmentLabels();
        $segment_id = $segment_id ?? array_keys($segment_labels)[0];
        $criteria = [['Segment', $segment_id]];
        if (!empty($start_year)) {
            $criteria[] = ['StartYear', $start_year];
        }
        /* @var GroupRepository $group_repo */
        $group_repo = $this->N->ORM->getRepository('Group');
        /* @var TopicRepository $topic_repo */
        $topic_repo = $this->N->ORM->getRepository('Topic');
        /* @var VisitRepository $visit_repo */
        $visit_repo = $this->N->ORM->getRepository('Visit');


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
                    $url_params = ['action' => 'set_visits', 'parameters' => $val];
                    $r['url'] = $this->N->generateUrl('batch', $url_params);

                    return $r;
                },
                array_keys($segment_labels),
                $segment_labels
            )
        );

        // rows: groups, columns: topics
        $group_visits = [];
        $relevant_topics = [];
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
                $relevant_topics[$topic_id] = $visit->getTopic();
                $topic_visit_count[$topic_id] = 1 + ($topic_visit_count[$topic_id] ?? 0);
            }
        }

        $relevant_topics = $topic_repo->sortByVisitOrder($relevant_topics);

        $future_visits = $visit_repo->findFutureVisits();
        // will contain all visits without a group in the form
        // [[topic_id] => [visit_id => visit_object, ...], ...]
        $topic_orphaned_visits = [];
        foreach ($future_visits as $v) {
            /* @var Visit $v */
            $topic_id = $v->getTopic()->getId();
            if (!$v->hasGroup() && !empty($relevant_topics[$topic_id])) {
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
        $this->setTemplate('set_visits');
    }

    public function setColleagues()
    {
        $future_visits = $this->N->ORM->getRepository('Visit')->findFutureVisits();
        $colleagues = $this->N->ORM->getRepository('User')->getActiveColleagues();

        $DATA['colleagues'] = array_map(
            function (User $c) {
                return ['id' => $c->getId(), 'label' => $c->getAcronym()];
            },
            $colleagues
        );
        $row_colors = array_values(Utility::getGoodBGColors());
        $DATA['visits'] = array_map(
            function (Visit $visit) use ($row_colors) {
                $r = ['id' => $visit->getId()];
                $r['colleagues'] = $visit->getColleaguesIdArray();
                $date = $visit->getDate();
                $r['date'] = $visit->getDateString();
                $r['weekday'] = $date->formatLocalized('%a');
                $r['weeknum'] = $date->weekOfYear;
                $col_index = (($date->weekOfYear * 5) + ($date->dayOfWeek - 1)) % count($row_colors);
                $r['row_color'] = $row_colors[$col_index];
                $r['label'] = $visit->getLabel('TGSU');

                return $r;
            },
            $future_visits
        );
        $this->addToDATA($DATA);
        $this->setTemplate('set_colleagues');
    }


    public function setGroupCount()
    {
        $this->setTemplate('set_group_count');
    }

    public function setBookings()
    {
        $this->setTemplate('set_bookings');
        $visits = $this->N->ORM->getRepository('Visit')->findFutureVisits();

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

                    return [
                        'id' => $visit->getId(),
                        'date' => $visit->getDateString(),
                        'label' => $label,
                        'needs_bus' => $visit->needsBus(),
                        'needs_food' => $visit->needsFoodOrder(),
                        'has_food' => $visit->getFoodIsBooked(),
                        'has_bus' => $visit->getBusIsBooked(),
                    ];
                },
                $visits
            )
        );
    }
}
