<?php
namespace Fridde\Entities;

use \Doctrine\ORM\EntityRepository;

class ChangeRepository extends EntityRepository
{

     public function findGroupChanges()
     {
         $all_changes = new ArrayCollection($this->findAll());
         $changes_concerning_groups = $all_changes->filter(function($c){
            return $c->getEntity() == "Group";
         });
     }
}
