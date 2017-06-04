<?php

namespace Fridde\Controller;

use Fridde\HTML as H;


class LoginController {

    /** @var \Fridde\Naturskolan */
    public $N;
    private $params;

    public function __construct($params = [])
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->params = $params;
    }

    public function checkCode()
    {
        $code = $this->params["code"] ?? null;
        if(!empty($code)){
            $user_id = $this->N->getIntFromCode($code);
            if(!empty($user_id)){
                return $this->N->ORM->getRepository("User")->find($user_id);
            }
        }
        return null;
    }

    public function getSchoolFromCookie()
    {
        $hash = $_COOKIE["Hash"] ?? null;
        if(!empty($hash)){
        	$hash = $this->N->ORM->getRepository("Cookie")->findByHash($hash);
        	return empty($hash) ? null : $hash->getSchool();
        }
        return null;
    }

    /**
     * Returns the school-id for the logged in user
     *
     * @param bool $ignore_empty_results
     * @return \Fridde\Entities\School|null
     * @throws \Exception
     */
    public function getSchooldIdFromCookie(bool $ignore_empty_results = false)
    {
        /* @var \Fridde\Entities\School $school  */
        $school = $this->getSchoolFromCookie();
        if(!empty($school)){
            return $school->getId();
        } elseif (!$ignore_empty_results) {
            throw new \Exception("The school of the user could not be identified although the user is logged in. Weird!");
        }
        return null;
    }

    public function checkPassword()
    {
        $H = new H();
        $H->setTemplate("password_modal")->setBase();
        $H->addDefaultJs("index")->addDefaultCss("index");

        $pw = $this->params["password"] ?? null;
        if(!empty($pw)){
            $H->addVariable("password", 'user$' . $pw);
        }

        $H->render();
    }
}
