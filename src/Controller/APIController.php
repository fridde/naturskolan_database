<?php

namespace Fridde\Controller;

use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Messenger\Mail;
use Fridde\Naturskolan;
use Fridde\Update;
use GuzzleHttp\Client;


class APIController extends BaseController
{

    public function confirmVisit(string $code)
    {
        if (empty($code)) {
            // TODO: error page, log
            return;
        }
        $visit_id = $this->N->getIntFromCode($code, 'visit');
        if (!empty($visit_id)) {
            $update = new Update();
            $return = $update->confirmVisit($visit_id)->flush()->getReturn();
        }
        if(empty($return['success'])){
            // TODO: error page, log
            return;
        }
        /* @var Visit $visit  */
        $visit = $this->N->ORM->getRepository('Visit')->find($visit_id);
        if(empty($visit)){
            // TODO: error page, log
        }


        $this->addToDATA('school_id', $visit->getGroup()->getSchoolId());
        $this->setTemplate('visit_confirmation_modal');
        $this->addToDATA('visit_label', $visit->getLabel());
    }

    public function getPassword(string $school_id)
    {
        $this->setReturnType('json');
        $cookie_school_id = $this->N->Auth->getSchooldIdFromCookie();
        if($school_id === $cookie_school_id || $this->N->Auth->getUserRole() === 'admin'){
            $this->addToDATA('password', $this->N->createPassword($school_id));
        }
    }

    public function sendPasswordRecoverMail(string $mail_adress)
    {
        $this->setReturnType('json');
        $mail_adress = trim($mail_adress);
        /* @var User $user  */
        $user = $this->N->ORM->getRepository('User')->findOneBy(['Mail' => $mail_adress]);

        if(empty($user) || !$user->isActive()){
            $this->addToDATA('errors', ['No active user with this adress could be found']);
            // TODO: Log this and return
            return;
        }

        $data = ['fname' => $user->getFirstName()];
        $data['password_link'] = $this->N->createLoginUrl($user, 'groups');
        $data['school_url'] = $this->N->generateUrl('school', ['school' => $user->getSchoolId()]);
        $params = ['purpose' => 'password_recover'];
        $params['data'] = $data;
        $params['receiver'] = $mail_adress;

        $mail = new Mail($params);
        $response = $mail->buildAndSend();

        $this->addToDATA('status', $response->getStatus());
        $this->addToDATA('errors', ($response->getErrors() ?? []));
    }
}
