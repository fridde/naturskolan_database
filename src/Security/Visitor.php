<?php


namespace Fridde\Security;


use Fridde\Entities\Hash;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Naturskolan;
use Fridde\ORM;

class Visitor
{
    /* @var Authenticator $Auth */
    private $Auth;
    /* @var School $school */
    private $school;
    /* @var User $User */
    private $User;
    /* @var string $cookie_key */
    private $cookie_key;
    /* @var string $session_key */
    private $session_key;

    private static $key_name = Authenticator::COOKIE_KEY_NAME;

    public function __construct(Authenticator $Auth)
    {
        $this->Auth = $Auth;

        $this->setCookieKey($_COOKIE[self::$key_name] ?? null);
        $this->setSessionKey($_SESSION[self::$key_name] ?? null);

        $this->setSchoolAndUser();
    }

    public function hasCookieKey()
    {
        return !empty($this->cookie_key);
    }

    public function hasSessionKey()
    {
        return !empty($this->session_key);
    }

    public function isFromAdminSchool()
    {
        if ($this->hasSchool()) {
            return $this->getSchool()->getId() === Naturskolan::ADMIN_SCHOOL;
        }
    }

    public function isAdminUser()
    {
        if ($this->isUser()) {
            return (bool)($this->getUser()->getRole() & (User::ROLE_ADMIN | User::ROLE_SUPERUSER));
        }

        return false;

    }

    public function setSchoolAndUser()
    {
        $key = $this->getSessionKey() ?? $this->getCookieKey();
        $this->setUserFromKey($key);
        $this->setSchoolFromKey($key); // order is important!
    }

    public function setUserFromKey(string $key = null)
    {
        $criteria['category'] = Hash::CATEGORY_USER_COOKIE_KEY;

        /* @var User $user */
        $user = $this->Auth->getObjectFromCode($key, $criteria, User::class);
        $this->setUser($user ?? null);
    }

    public function setSchoolFromKey(string $key = null): void
    {
        if ($this->isUser()) {
            $this->setSchool($this->getUser()->getSchool());

            return;
        }
        $criteria['category'] = Hash::CATEGORY_SCHOOL_COOKIE_KEY;
        /* @var School $school */
        $school = $this->Auth->getObjectFromCode($key, $criteria, School::class) ?? null;
        $this->setSchool($school);

    }

    public function isFromSchool(School $school)
    {
        if ($this->hasSchool()) {
            return $this->getSchool()->getId() === $school->getId();
        }

        return false;
    }


    /**
     * @return School
     */
    public function getSchool(): ?School
    {
        return $this->school;
    }

    /**
     * @param School $school
     */
    public function setSchool(School $school = null): void
    {
        $this->school = $school;
    }

    public function hasSchool()
    {
        return ($this->getSchool() instanceof School);
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->User;
    }

    /**
     * @param User $User
     */
    public function setUser(User $User = null): void
    {
        $this->User = $User;
    }

    public function isUser(): bool
    {
        return ($this->getUser() instanceof User);
    }

    public function getUserRole(): ?int
    {
        if ($this->isUser()) {
            return $this->getUser()->getRole();
        }

        return null;

    }

    public function hasRole(int $role): bool
    {
        return $this->getUserRole() === $role;
    }

    /**
     * @return string
     */
    public function getSessionKey(): ?string
    {
        return $this->session_key;
    }

    /**
     * @param string $session_key
     */
    public function setSessionKey(string $session_key = null): void
    {
        $this->session_key = $session_key;
    }

    public function getCookieKey(): ?string
    {
        return $this->cookie_key;
    }

    /**
     * @param string $cookie_key
     */
    public function setCookieKey(string $cookie_key = null): void
    {
        $this->cookie_key = $cookie_key;
    }


}
