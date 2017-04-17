<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class SchoolRepository extends CustomRepository
{


    public function getNaturskolansStaff()
    {
        return $this->getSchoolStaff("natu");
    }

    public function getSchoolStaff($school_id)
    {
        return $this->find($school_id)->getUsers()->toArray();
    }

    public function getStaffWithNames($school_id = "natu")
    {
        $user_names = array_map(function($u){
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
        $schools_id_name = array_map(function($s){
            return [$s->getId(), $s->getName()];
        }, $this->findAll());
        return array_column($schools_id_name, 1, 0);
    }

}
