<?php

namespace Fridde\Controller;

use Fridde\Entities\Hash;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\Security\Authorizer;
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
    public function loginWithCode()
    {
        $code = $this->getParameter('code');
        $user = $this->N->Auth->getUserFromUrlCode($code);
        if (empty($user)) {
            throw new NException(Error::UNAUTHORIZED_ACTION, ['login with code ' . $code]);
        }

        $auth_key = $this->N->Auth->createAndSaveCode($user->getId(), Hash::CATEGORY_USER_COOKIE_KEY);
        $exp_date = $this->N->Auth->getExpirationDate(Hash::CATEGORY_USER_COOKIE_KEY);
        $this->N->Auth->setCookieKeyInBrowser($auth_key, $exp_date);

        $params['school'] = $user->getSchoolId();
        $params['page'] = $this->getParameter('destination') ?? 'staff';
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
