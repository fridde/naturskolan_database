<?php


namespace Fridde\Security;

use Fridde\Controller\BaseController;
use Fridde\Entities\User;
use Fridde\ORM;

class Authorizer
{
   /* @var Visitor $Visitor */
    public $Visitor;

    public const ROLE_GUEST = 1;

    public const ACCESS_ALL = 63;
    public const ACCESS_ALL_EXCEPT_GUEST = 62;
    public const ACCESS_ADMIN_ONLY = 48;


    public function __construct(ORM $ORM, Authenticator $Auth)
    {

        $this->setVisitor(new Visitor($Auth));
    }


    public function authorize(BaseController $controller, string $method_string): bool
    {
        $security_levels = $controller->getSecurityLevels();

        if(isset($security_levels['*'])){
            $method_level = $security_levels['*'];
        } else {
            $method_level = $security_levels[$method_string] ?? User::ROLE_ADMIN;
        }

        return (bool) ($method_level & $this->getVisitorSecurityLevel());

    }

    public function getVisitorSecurityLevel()
    {
        $visitor = $this->getVisitor();
        if ($visitor->hasUser() && $visitor->getUserRole() === User::ROLE_SUPERUSER) {
            return User::ROLE_SUPERUSER;
        }

        if ($visitor->isFromAdminSchool() || $visitor->isAdminUser()) {
            return User::ROLE_ADMIN;
        }
        if ($visitor->hasUser()) {
            return $visitor->getUserRole();
        }
        if($visitor->hasSchool()){
            return User::ROLE_TEACHER;
        }
        return self::ROLE_GUEST;
    }

    /**
     * @return Visitor
     */
    public function getVisitor(): Visitor
    {
        return $this->Visitor;
    }

    /**
     * @param Visitor $Visitor
     */
    public function setVisitor(Visitor $Visitor): void
    {
        $this->Visitor = $Visitor;
    }


}
