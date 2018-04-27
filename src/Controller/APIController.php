<?php

namespace Fridde\Controller;

use Carbon\Carbon;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Messenger\Mail;
use Fridde\Update;



class APIController extends BaseController
{

    public function confirmVisit(string $code)
    {
        /* @var Visit $visit  */
        $visit = $this->N->Auth->getVisitFromCode($code);
        if(empty($visit)){
            // TODO: error page, log
            return;
        }
        $update = new Update();
        $return = $update->confirmVisit($visit->getId())->flush()->getReturn();

        if(empty($return['success'])){
            // TODO: error page, log
            return;
        }


        $this->addToDATA('school_id', $visit->getGroup()->getSchoolId());
        $this->setTemplate('visit_confirmation_modal');
        $this->addToDATA('visit_label', $visit->getLabel());
    }

    public function getPassword(string $school_id)
    {
        $this->setReturnType('json');
        $user_or_school = $this->N->Auth->getUserOrSchoolFromCookie();
        /* @var School $cookie_school  */
        if($this->N->Auth::isUser($user_or_school)){
            $cookie_school = $user_or_school->getSchool();
        } else {
            $cookie_school = $user_or_school;
        }

        if($school_id === $cookie_school->getId() || $this->N->isAdminSchool($cookie_school)){
            $this->addToDATA('password', $this->N->Auth->calculatePasswordForSchool($cookie_school));
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
        $this->addToDATA('errors', $response->getErrors() ?? []);
    }

    public function updateTestDate(string $date_time)
    {
        $date_time = html_entity_decode($date_time);
        $this->N->setStatus('test.datetime', $date_time);
        Carbon::setTestNow($date_time);
    }
}
