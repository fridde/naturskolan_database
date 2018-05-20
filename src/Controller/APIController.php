<?php

namespace Fridde\Controller;

use Carbon\Carbon;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Messenger\Mail;
use Fridde\Update;
use Fridde\Security\Authorizer;


class APIController extends BaseController
{
    protected $Security_Levels = [
        'confirmVisit' =>  Authorizer::ACCESS_ALL,
        'confirmVisitUsingId' => Authorizer::ACCESS_ALL_EXCEPT_GUEST,
        'getPassword' => Authorizer::ACCESS_ALL_EXCEPT_GUEST,
        'sendPasswordRecoverMail' => Authorizer::ACCESS_ALL,
        'updateTestDate' => Authorizer::ACCESS_ADMIN_ONLY
    ];


    public function confirmVisitUsingId(string $visit_id): void
    {
        /* @var Visit $visit  */
        $visit = $this->N->ORM->find('Visit', $visit_id);
        $school = $visit->getGroup()->getSchool();
        $visitor = $this->Authorizer->getVisitor();
        if(!($visitor->isAdminUser() || $visitor->isFromAdminSchool()  || $visitor->isFromSchool($school))){
            throw new \Exception('Unauthorized trial to confirm visit');
        }
        $this->updateVisitStatus($visit, $school->getId());
    }

    public function confirmVisit(string $code, string $authentication = 'code'): void
    {
        if($authentication === 'code'){
            $this->updateVisitUsingCode($code);
        } elseif($authentication === 'simple'){
            $this->confirmVisitUsingId($code);
        } else {
            throw new \Exception('The authentication method "'. $authentication . '" is not supported.');
        }
    }

    protected function updateVisitUsingCode(string $code)
    {
        /* @var Visit $visit  */
        $visit = $this->N->Auth->getVisitFromCode($code);

        if(empty($visit)){
            return; // TODO: error page, log
        }
        $this->updateVisitStatus($visit, $visit->getGroup()->getSchoolId());
    }

    protected function updateVisitStatus(Visit $visit, string $school_id)
    {
        $update = new Update();
        $return = $update->confirmVisit($visit->getId())->flush()->getReturn();
        if(empty($return['success'])){
            // TODO: error page, log
            return;
        }

        $this->addToDATA('school_id', $school_id);
        $this->setTemplate('visit_confirmation_modal');
        $this->addToDATA('visit_label', $visit->getLabel());

    }

    public function getPassword(string $school_id): void
    {
        $this->setReturnType('json');
        $visitor = $this->Authorizer->getVisitor();
        /* @var School $request_school  */
        $request_school = $this->N->ORM->find('School', $school_id);
        if(!($request_school instanceof School)){
            throw new \Exception('The school_id in the request did not match any school.');
        }
        if($visitor->isFromSchool($request_school) || $visitor->isFromAdminSchool()){
            $this->addToDATA('password', $this->N->Auth->calculatePasswordForSchool($request_school));
        }
    }

    public function sendPasswordRecoverMail(string $mail_adress): void
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
