<?php
namespace Fridde\Entities;

use Doctrine\ORM\EntityRepository;

class LocationRepository extends EntityRepository
{

    public function findAllLocationLabels()
    {
        $locations_id_name = array_map(function($l){
            return [$l->getId(), $l->getName()];
        }, $this->findAll());
        return array_column($locations_id_name, 1, 0);
    }


}
