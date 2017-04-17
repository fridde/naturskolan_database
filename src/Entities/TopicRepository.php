<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class TopicRepository extends CustomRepository
{
    public function findAllTopicsWithGrade()
    {
        $topics_id_name_grade = array_map(function($t){
            $label = "[" . $t->getGradeLabel() ."] ";
            $label .= $t->getShortName();
            return [$t->getId(), $label];
        }, $this->findAll());
        return array_column($topics_id_name_grade, 1, 0);
    }

    public function findAllTopics($grade = null)
    {
        if(!empty($grade)){
            return $this->select(["Grade", $grade]);
        }
        return $this->findAll();
    }
}
