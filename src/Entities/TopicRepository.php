<?php
namespace Fridde\Entities;

use function foo\func;
use Fridde\CustomRepository;

class TopicRepository extends CustomRepository
{
    public function findLabelsForTopics()
    {
        $topics_id_name_segment = array_map(function(Topic $t){
            $label = '[' . $t->getSegmentLabel() .'] ';
            $label .= $t->getShortName();
            return [$t->getId(), $label];
        }, $this->findAll());
        return array_column($topics_id_name_segment, 1, 0);
    }

    public function findTopicsForSegment($segment_id = null)
    {
        if(!empty($segment_id)){
            return $this->select(['Segment', $segment_id]);
        }
        return $this->findAll();
    }

    public function findByVisitOrder()
    {
        return $this->sortByVisitOrder($this->findAll());

    }

    public function sortByVisitOrder(array $topics)
    {
        usort(
            $topics,
            function (Topic $t1, Topic $t2) {
                return $t1->getVisitOrder() - $t2->getVisitOrder();
            }
        );
        return $topics;

    }

    /**
     * @return Topic[]
     */
    public function findTopicsOrderedBySegments(): array
    {
        $topics = $this->findAll();
        $ordered_topics = [];

        foreach($topics as $topic){
            /* @var Topic $topic  */
            $ordered_topics[$topic->getSegment()][] = $topic;
        }
        array_walk($ordered_topics, function(&$segment_topics){
            usort($segment_topics, function(Topic $t1, Topic $t2){
                return (int) $t1->getVisitOrder() - (int) $t2->getVisitOrder();
            });
        });

        return $ordered_topics;

    }
}
