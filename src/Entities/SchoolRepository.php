<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class SchoolRepository extends CustomRepository
{
    public function getNaturskolansStaff()
    {
        return $this->getSchoolStaff('natu');
    }

    public function getSchoolStaff(string $school_id)
    {
        return $this->find($school_id)->getUsers();
    }

    public function getStaffWithNames($school_id = 'natu')
    {
        $user_names = array_map(function(User $u){
            return [$u->getId(), $u->getFullName()];
        }, $this->getSchoolStaff($school_id));
        return array_column($user_names, 1, 0);
    }

    /**
     * [findAllSchoolLabels description]
     * @return [array] An array with id-values as keys and labels as values
     */
    public function findAllSchoolLabels()
    {
        $schools = $this->findAll();
        usort($schools, function(School $s1, School $s2){
           return strcasecmp($s1->getName(), $s2->getName());
        });
        $schools_id_name = array_map(function(School $s){
            return [$s->getId(), $s->getName()];
        }, $schools);

        return array_column($schools_id_name, 1, 0);
    }

    public function findIdsAndLabels()
    {
        $school_labels = $this->findAllSchoolLabels();
        return array_map(function($id, $label){
            return ['value' => $id, 'label' => $label];
        }, array_keys($school_labels), $school_labels);
    }

}
