<?php
namespace Fridde\Entities;

use \Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

class CookieRepository extends EntityRepository
{

    public function findByHash($hash)
    {
        return $this->findOneBy(["Value" => $hash, "Name" => "Hash"]);
    }

}
