<?php

namespace Fridde\Entities;

use function foo\func;
use Fridde\CustomRepository;

class TopicRepository extends CustomRepository
{
    public function findLabelsForTopics()
    {
        $topics_id_name_segment = array_map(
            function (Topic $t) {
                $label = '['.$t->getSegmentLabel().'] ';
                $label .= $t->getShortName();

                return [$t->getId(), $label];
            },
            $this->findAll()
        );

        return array_column($topics_id_name_segment, 1, 0);
    }

    public function findTopicsForSegment($segment_id = null)
    {
        if (!empty($segment_id)) {
            return $this->select(['Segment', $segment_id]);
        }

        return $this->findAll();
    }

    /**
     * @return Topic[]
     */
    public function sortByVisitOrder(array $topics): array
    {
        usort(
            $topics,
            function (Topic $t1, Topic $t2) {
                return (int)$t1->getVisitOrder() - (int)$t2->getVisitOrder();
            }
        );

        return $topics;

    }


    /**
     * @return Topic[]
     */
    public function findOrderableTopics()
    {
        return array_filter(
            $this->findAll(),
            function (Topic $t) {
                return $t->getOrderIsRelevant();
            }
        );

    }

    /**
     * @return Topic[]
     */
    public function sliceBySegment(array $topics): array
    {
        $sliced_topics = [];

        foreach ($topics as $topic) {
            /* @var Topic $topic */
            $segment = $topic->getSegment() ?? -1;
            $sliced_topics[$segment][] = $topic;
        }

        return $sliced_topics;
    }
}
