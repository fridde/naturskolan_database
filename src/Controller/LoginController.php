<?php

namespace Fridde\Controller;

use Carbon\Carbon;
use Fridde\Annotations\SecurityLevel;
use Fridde\Entities\Hash;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\Security\Authorizer;
use Fridde\Timing;
use Fridde\Update;
use Fridde\Utility;


class LoginController extends BaseController
{

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function renderPasswordModal()
    {
        $this->setTemplate('password_modal');
        $this->addToDATA('school_id', $this->getParameter('school'));
    }

    /**
     * @throws \Exception
     *
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function loginWithCode(): void
    {
        $code = $this->getParameter('code');
        $user = $this->N->Auth->getUserFromCode($code);
        if (empty($user)) {
            throw new NException(Error::UNAUTHORIZED_ACTION, ['login with code ' . $code]);
        }

        $auth_key = $this->N->Auth->createCookieKeyForUser($user);
        $this->N->Auth->setCookieKeyInBrowser($auth_key);

        $params['school'] = $user->getSchoolId();
        $url = $this->N->generateUrl('school', $params);

        Utility::redirect($url);

    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function logout()
    {
        $this->N->Auth->removeCookieKeyFromBrowser();
        $this->N->Auth->emptySession();

        Utility::redirect(APP_URL);
    }
}
