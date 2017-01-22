<?php
namespace Fridde\Entities;

use \Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

class ChangeRepository extends EntityRepository
{

     public function findGroupChanges()
     {
         $all_changes = new ArrayCollection($this->findAll());
         $group_changes = $all_changes->filter(function($c){
            return $c->getEntity() == "Group";
         });
         return $group_changes;
     }
}
