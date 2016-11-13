<?php
namespace Fridde\Entities;

class School extends Entity
{
    public $grade_column_map = ["2" => "GroupsAk2", "5" => "GroupsAk5", "fbk" => "GroupsFbk"];

    public function getAllGrades()
    {
        return array_keys($this->grade_column_map);
    }

    public function getVisitOrder()
    {
        $this->setInformation();
        return $this->get("VisitOrder");
    }

    public function getName()
    {
        $this->setInformation();
        return $this->get("Name");

    }

    public function countActiveGroups($grade = null)
    {
        $this->setInformation();
        $grades = $grade ?? $this->getAllGrades();
        $grades = (array) $grades;
        $groups = $this->getTable("groups");

        $group_count = 0;
        foreach($grades as $grade){
            $criteria = [["IsActive", "true"], ["Grade", $grade]];
            $group_count += count(U::filterFor($groups, $criteria));
        }
        return $group_count;
    }

    public function countExpectedGroups($grade = null)
    {
         $this->setInformation();
         $grades = $grade ?? $this->getAllGrades();
         $grades = (array) $grades;

         $group_count = 0;
         foreach($grades as $grade){
             $col_name = $this->grade_column_map[$grade];
             $group_count += $this->get($col_name);
         }
         return $group_count;
    }


}
