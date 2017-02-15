<?php
namespace Fridde\Entities;

use \Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

class PasswordRepository extends EntityRepository
{

    public function findByHash($hash)
    {
        $criteria = ["Value" => $hash, "Type" => Password::translate("cookie_hash", "TYPES")];
        $return = $this->findOneBy($criteria);
        return $this->findOneBy(["Value" => $hash, "Type" => Password::translate("cookie_hash", "TYPES")]);
    }

    public function findByPassword($password)
    {
        return $this->findOneBy(["Value" => $password, "Type" => Password::translate("password", "TYPES")]);
    }

}
