<?php

namespace Fridde\Controller;

use Fridde\HTML as H;
use Fridde\Update;


class LoginController extends BaseController
{

    public function renderPasswordModal()
    {
        $H = $this->H;
        $this->setTemplate('password_modal');
        $this->addToDATA('school_id', $this->getParameter('school'));
        $pw = $this->getParameter('password');
        if(!empty($pw)){
            $this->addToDATA('password', 'user$' . $pw);
        }
    }

    public function logout()
    {
        // TODO: Finish this method
        $school_id = $this->N->Auth->getSchooldIdFromCookie();
        $update = new Update();
    }
}
