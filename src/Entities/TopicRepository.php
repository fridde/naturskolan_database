<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class TopicRepository extends CustomRepository
{
    public function findLabelsForTopics()
    {
        $topics_id_name_grade = array_map(function(\Fridde\Entities\Topic $t){
            $label = '[' . $t->getGradeLabel() .'] ';
            $label .= $t->getShortName();
            return [$t->getId(), $label];
        }, $this->findAll());
        return array_column($topics_id_name_grade, 1, 0);
    }

    public function findTopicsForGrade($grade = null)
    {
        if(!empty($grade)){
            return $this->select(['Grade', $grade]);
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
}
