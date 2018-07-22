<?php


namespace Fridde\Security;

use Fridde\Annotations\SecurityLevel;
use Fridde\Entities\User;
use Fridde\ORM;

class Authorizer
{
    /* @var ORM $ORM  */
    private $ORM;

    /* @var Visitor $Visitor */
    public $Visitor;

    private $custom_security_levels = [];

    public const ROLE_GUEST = 1;

    public const ACCESS_ALL = 63;
    public const ACCESS_ALL_EXCEPT_GUEST = 62;
    public const ACCESS_ADMIN_ONLY = 48;


    public function __construct(ORM $ORM, Authenticator $Auth)
    {
        $this->ORM = $ORM;
        $this->setVisitor(new Visitor($Auth));
    }


    public function authorize(string $controller_name, string $method_string): bool
    {
        $method_level = $this->getMethodSecurityLevel($controller_name, $method_string);

        return (bool) ($method_level & $this->getVisitorSecurityLevel());
    }

    private function getMethodSecurityLevel(string $controller_name, string $method_string): int
    {
        $custom_level = $this->custom_security_levels[$controller_name][$method_string] ?? null;

        if(null !== $custom_level){
            return $custom_level;
        }
        $reader = $this->ORM->getAnnotationReader();

        $lvl = $reader->getAnnotationForMethod($controller_name, $method_string, SecurityLevel::class);
        if(!empty($lvl)){
            return $lvl->value;
        }
        $lvl = $reader->getAnnotationForClass($controller_name, SecurityLevel::class);
        if(!empty($lvl)){
            return $lvl->value;
        }
        return User::ROLE_ADMIN;
    }

    public function getVisitorSecurityLevel(): int
    {
        $visitor = $this->getVisitor();
        if ($visitor->isUser() && $visitor->hasRole(User::ROLE_SUPERUSER)) {
            return User::ROLE_SUPERUSER;
        }

        if ($visitor->isFromAdminSchool() || $visitor->isAdminUser()) {
            return User::ROLE_ADMIN;
        }
        if ($visitor->isUser()) {
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

    public function changeSecurityLevel(string $class, string $method, int $level)
    {
        $this->custom_security_levels[$class][$method] = $level;
    }


}
