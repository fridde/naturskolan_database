<?php

namespace Fridde\Controller;

use Fridde\Entities\Hash;
use Fridde\Update;
use Fridde\Utility;


class LoginController extends BaseController
{

    public function renderPasswordModal()
    {
        $this->setTemplate('password_modal');
        $this->addToDATA('school_id', $this->getParameter('school'));
    }

    public function login()
    {
        $code = $this->getParameter('code');
        $user = $this->N->Auth->getUserFromUrlCode($code);
        if (empty($user)) {
            throw new \Exception('Someone tried to enter with the invalid code '.$code);
        }
        $this->N->Auth->createAndSaveCode($user->getId(), Hash::CATEGORY_USER_COOKIE_KEY);

        $params['school'] = $user->getSchoolId();
        $params['page'] = $this->getParameter('page');
        $url = $this->N->generateUrl('school', $params);

        Utility::redirect($url);

    }

    public function logout()
    {
        // TODO: Finish this method
        $school_or_user = $this->N->Auth->getUserOrSchoolFromCookie();
        $update = new Update();
    }
}
