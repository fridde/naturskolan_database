<?php
namespace Fridde\Entities;

use \Doctrine\ORM\EntityRepository;

class TopicRepository extends EntityRepository
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
}
