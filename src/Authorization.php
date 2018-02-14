<?php


namespace Fridde;


class Authorization
{
    /* @var ORM $ORM */
    private $ORM;
    /* @var PasswordHandler $PW */
    private $PW;

    /**
     * Authorization constructor.
     * @param ORM $ORM
     */
    public function __construct(ORM $ORM, PasswordHandler $PW)
    {
        $this->ORM = $ORM;
        $this->PW = $PW;
    }


    public function getSchoolFromCookie()
    {
        $hash_string = $_COOKIE['Hash'] ?? null;
        if (!empty($hash_string)) {
            $cookie = $this->ORM->getRepository('Cookie')->findByHash($hash_string);

            return empty($cookie) ? null : $cookie->getSchool();
        }

        return null;
    }

    /**
     * Returns the school-id for the logged in user
     *
     * @param bool $ignore_empty_results
     * @return \Fridde\Entities\School|null
     */
    public function getSchooldIdFromCookie()
    {
        $school = $this->getSchoolFromCookie();

        return empty($school) ? null : $school->getId();
    }

    /**
     * Returns the user that matches a given code
     *
     * @param string $code
     * @return \Fridde\Entities\User
     */
    public function getUserFromCode(string $code = null)
    {
        if (empty($code)) {
            return null;
        }
        $user_id = $this->PW->getIntFromCode($code);
        if (!empty($user_id)) {
            return $this->ORM->getRepository('User')->find($user_id);
        }

        return null;
    }

    public function getUserRole()
    {
        $school_id = $this->getSchooldIdFromCookie();
        if ($school_id === 'natu') {
            return 'admin';
        } elseif (!empty($school_id)) {
            return 'user';
        }
        return 'guest';
    }

}
