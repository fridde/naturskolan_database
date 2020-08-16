<?php

namespace Fridde\Controller;

use Carbon\Carbon;
use Fridde\Annotations\SecurityLevel;
use Fridde\Entities\Group;
use Fridde\Entities\Hash;
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
        $this->setReturnType(self::RETURN_JSON);
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

    protected function updateVisitUsingCode(string $code): void
    {
        /* @var Visit $visit */
        $visit = $this->N->Auth->getVisitFromCode($code);

        if (empty($visit)) {
            return; // TODO: error page, log
        }
        $this->updateVisitStatus($visit, $visit->getGroup()->getSchoolId());
    }

    protected function updateVisitStatus(Visit $visit, string $school_id): void
    {

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

        $visitor = $this->Authorizer->getVisitor();
        /* @var School $request_school */
        $request_school = $this->N->ORM->find('School', $school_id);
        if (!($request_school instanceof School)) {
            throw new NException(Error::LOGIC, [$school_id.' not a valid school id']);
        }
        if ($visitor->isFromSchool($request_school) || $visitor->isFromAdminSchool()) {
            $pw = $this->getPWFromCacheOrAuth($request_school);
            $this->addToDATA('password', $pw);
        }
    }

    private function getPWFromCacheOrAuth(School $school): string
    {
        $school_id = $school->getId();

        $pw_key = 'passwords';
        $cache = $this->N->cache;

        $passwords = $cache->contains($pw_key) ? $cache->fetch($pw_key) : [];
        $school_pw = $passwords[$school_id] ?? null;

        if(!empty($school_pw)){
            return $school_pw;
        }

        $passwords[$school_id] = $this->N->Auth->calculatePasswordForSchool($school);

        $cache->save($pw_key, $passwords);

        return $passwords[$school_id];

    }

    /**
     * @param string $mail_address
     *
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function sendPasswordRecoverMail(string $mail_address): void
    {

        $mail_address = strtolower(trim($mail_address));
        /* @var User $user */
        $user = $this->N->ORM->getRepository('User')->findOneBy(['Mail' => $mail_address]);

        if (empty($user) || !$user->isActive()) {
            $this->addToDATA('errors', ['No active user with this address could be found']);

            // TODO: Log this and return
            return;
        }

        $data = ['user' => ['fname' => $user->getFirstName()]];
        $data['user']['groups'] = []; // to satisfy base_mail template
        $data['password_link'] = $this->N->createLoginUrl($user);
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

    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function sendContactMail()
    {
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

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL_EXCEPT_GUEST)
     */
    public function sendRemoveUserMail()
    {
        $school_name = null;
        // user[], reason, text
        foreach ($this->getFromRequest('users') as $user_id) {
            /* @var User $user */
            $user = $this->N->getRepo(User::class)->find($user_id);
            $u = [];
            $u['id'] = $user_id;
            $u['name'] = $user->getFullName();
            $school_name = $school_name ?? $user->getSchool()->getName();

            $params = ['action' => 'remove_user'];
            $params['parameters'] = $this->N->Auth->createUserUrlCode($user);
            $u['removal_link'] = $this->N->generateUrl('api', $params);

            $u['groups'] = array_map(
                function (Group $g) {
                    $r = ['id' => $g->getId()];
                    $r['name'] = $g->getName();
                    $r['active'] = $g->isActive();

                    return $r;
                },
                $user->getGroups()
            );

            $mail_params['data']['users'][] = $u;
        }
        $mail_params['data']['reason'] = $this->getFromRequest('reason');
        $mail_params['data']['text'] = $this->getFromRequest('reason_text');
        $mail_params['data']['school_name'] = $school_name;
        $mail_params['subject_int'] = Message::SUBJECT_USER_REMOVAL_REQUEST;

        $mail = new Mail($mail_params);
        $response = $mail->buildAndSend();

        $this->addToDATA('status', $response->getStatus());
        $this->addToDATA('errors', $response->getErrors() ?? []);
    }

    public function removeUser(string $user_code)
    {
        $status = 'success';
        try {
            $user = $this->N->Auth->getUserFromCode($user_code);
            if (empty($user)) {
                $status = 'failure';
            } else {
                $user->setStatus(User::ARCHIVED);
                $rm_user = ['id' => $user->getId(), 'name' => $user->getFullName()];
                $this->addToDATA('removed_user', $rm_user);

                $this->N->ORM->EM->persist($user);
                $this->N->ORM->EM->flush();
            }
        } catch (\Exception $e) {
            $status = 'failure';
            $this->addToDATA('errors', $e->getMessage() ?? []);
        }
        $this->addToDATA('status', $status);
    }

}
