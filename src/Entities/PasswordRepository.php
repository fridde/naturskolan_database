<?php
namespace Fridde\Entities;

use \Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

class PasswordRepository extends EntityRepository
{

    public function findByHash($hash)
    {
        return $this->findOneBy(["Value" => $hash, "Type" => Password::COOKIE_HASH]);
    }
}
