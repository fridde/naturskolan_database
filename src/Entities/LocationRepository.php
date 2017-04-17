<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class LocationRepository extends CustomRepository
{

    public function findAllLocationLabels()
    {
        $locations_id_name = array_map(function($l){
            return [$l->getId(), $l->getName()];
        }, $this->findAll());
        return array_column($locations_id_name, 1, 0);
    }


}
