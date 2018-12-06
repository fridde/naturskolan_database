<?php

namespace Fridde\Controller;

use Carbon\Carbon;
use Fridde\Entities\Message;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\HTML;
use Fridde\Mailer;
use Fridde\Messenger\Mail;
use Fridde\Update;
use Fridde\Security\Authorizer;
use GuzzleHttp\Client;


class APIController extends BaseController
{

    public function __construct(array $params = [], bool $slim = true)
    {
        parent::__construct($params, $slim);
        if (defined('ENVIRONMENT') && in_array(ENVIRONMENT, ['dev', 'test'], true)) {
            $this->Authorizer->changeSecurityLevel(get_class($this), 'updateTestDate', Authorizer::ACCESS_ALL);
        }
    }


    /**
     * @param string $visit_id
     * @throws \Exception
     *
     * @SecurityLevel(SecurityLevel::ACCESS_ALL_EXCEPT_GUEST)
     */
    public function confirmVisitUsingId(string $visit_id): void
    {
        /* @var Visit $visit */
        $visit = $this->N->ORM->find('Visit', $visit_id);
        $school = $visit->getGroup()->getSchool();
        $visitor = $this->Authorizer->getVisitor();
        if (!(
            $visitor->isAdminUser()
            || $visitor->isFromAdminSchool()
            || $visitor->isFromSchool($school)
        )) {
            throw new NException(Error::UNAUTHORIZED_ACTION, ['Visit confirmation']);
        }
        $this->updateVisitStatus($visit, $school->getId());
    }

    /**
     * @param string $code
     * @param string $authentication
     * @throws \Exception
     *
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function confirmVisit(string $code, string $authentication = 'code'): void
    {
        if ($authentication === 'code') {
            $this->updateVisitUsingCode($code);
        } elseif ($authentication === 'simple') {
            $this->confirmVisitUsingId($code);
        } else {
            throw new NException(Error::INVALID_OPTION, [$authentication]);
        }
    }

    protected function updateVisitUsingCode(string $code)
    {
        /* @var Visit $visit */
        $visit = $this->N->Auth->getVisitFromCode($code);

        if (empty($visit)) {
            return; // TODO: error page, log
        }
        $this->updateVisitStatus($visit, $visit->getGroup()->getSchoolId());
    }

    protected function updateVisitStatus(Visit $visit, string $school_id)
    {
        $this->setReturnType(self::RETURN_JSON);
        $update = new Update();
        $return = $update->confirmVisit($visit->getId())->flush()->getReturn();
        if (empty($return['success'])) {
            // TODO: error page, log
            return;
        }

        $page_controller = new PageController();
        $page_controller->showConfirmedVisit($visit, $school_id);
        $page_controller->handleRequest();
        exit();
    }

    /**
     * @param string $school_id
     * @throws \Exception
     *
     * @SecurityLevel(SecurityLevel::ACCESS_ALL_EXCEPT_GUEST)
     */
    public function getPasswordForSchool(string $school_id): void
    {
        $this->setReturnType(self::RETURN_JSON);
        $visitor = $this->Authorizer->getVisitor();
        /* @var School $request_school */
        $request_school = $this->N->ORM->find('School', $school_id);
        if (!($request_school instanceof School)) {
            throw new NException(Error::LOGIC, [$school_id . ' not a valid school id']);
        }
        if ($visitor->isFromSchool($request_school) || $visitor->isFromAdminSchool()) {
            $this->addToDATA('password', $this->N->Auth->calculatePasswordForSchool($request_school));
        }
    }

    /**
     * @param string $mail_address
     *
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function sendPasswordRecoverMail(string $mail_address): void
    {
        $this->setReturnType(self::RETURN_JSON);
        $mail_address = strtolower(trim($mail_address));
        /* @var User $user */
        $user = $this->N->ORM->getRepository('User')->findOneBy(['Mail' => $mail_address]);

        if (empty($user) || !$user->isActive()) {
            $this->addToDATA('errors', ['No active user with this address could be found']);

            // TODO: Log this and return
            return;
        }

        $data = ['fname' => $user->getFirstName()];
        $data['password_link'] = $this->N->createLoginUrl($user, 'groups');
        $data['school_url'] = $this->N->generateUrl('school', ['school' => $user->getSchoolId()], true);
        $params = ['subject_int' => Message::SUBJECT_PASSWORD_RECOVERY];
        $params['data'] = $data;
        $params['receiver'] = $mail_address;

        $mail = new Mail($params);
        $response = $mail->buildAndSend();

        $this->addToDATA('status', $response->getStatus());
        $this->addToDATA('errors', $response->getErrors() ?? []);
    }

    /**
     * @param string $date_time
     *
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function updateTestDate(string $date_time)
    {
        $date_time = html_entity_decode($date_time);
        $this->N->setStatus('test.datetime', $date_time);
        Carbon::setTestNow($date_time);
        $this->setReturnType(self::RETURN_JSON);
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function sendContactMail()
    {
        $this->setReturnType(self::RETURN_JSON);

        $client = new Client();

        $params = ['secret' => SETTINGS['captcha']['secret']];
        $params['response'] = $this->getFromRequest('captcha');
        $params['remoteip'] = $_SERVER['REMOTE_ADDR'];

        $uri = 'https://www.google.com/recaptcha/api/siteverify';
        $response = $client->post($uri, ['form_params' => $params]);

        $resp_array = json_decode($response->getBody(), true);
        $captcha_success = $resp_array['success'] ?? false;

        $mail_address = $this->getFromRequest('input_email');
        $address_success = !empty(trim($mail_address));

        $mail_status = false;
        if ($captcha_success && $address_success) {
            $m['receiver'] = SETTINGS['smtp_settings']['from'];
            $m['Subject'] = 'Nytt meddelande från webbformuläret på NDB';

            $body = '<p>Ett nytt meddelande har skickats från webbformuläret på ';
            $body .= 'sigtunanaturskola.se/ndb/contact <br>'.PHP_EOL;
            $body .= 'Avsändaradress: '.$mail_address;
            $body .= '</p>'.PHP_EOL.PHP_EOL.'<p><pr>';
            $body .= $this->getFromRequest('input_message').'</pr></p>';

            $m['Body'] = $body;

            $mailer = new Mailer($m);
            $mail_status = $mailer->sendAway();
        }
        $this->addToDATA('mail_success', $mail_status);
        $this->addToDATA('address_success', $address_success);
        $this->addToDATA('captcha_success', $captcha_success);
    }

    public function updateReceivedSMS()
    {
        // TODO: Implement this function using SMS::updateReceivedSms
    }

}
