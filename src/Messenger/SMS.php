<?php


namespace Fridde\Messenger;

use Fridde\Entities\Message;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Update;
use Fridde\ShortMessageService;


class SMS extends AbstractMessageController
{
    protected $text;
    protected $options;
    protected $response;


    // PREPARE=1; SEND=2; UPDATE=4;
    protected $methods = [
        'update_received_sms' => 4,
        'confirm_visit' => 3,
        'update_profile_reminder' => 3,
    ];

    public function __construct()
    {
        $this->setType(Message::CARRIER_SMS);
        parent::__construct();
    }

    public function send()
    {
        $SMS = new ShortMessageService($this->options);

        if (!empty(DEBUG)) {
            $SMS->send(SETTINGS['debug']['mobil']);
        } else {
            $SMS->send();
        }
        $this->setStatus($SMS->status);
        $this->setResponse($SMS->response);

        return $this;
    }

    protected function prepareConfirmVisit()
    {
        $this->options['message'] = $this->getParameter('message');
        $this->options['to'] = $this->getParameter('receiver');
    }

    protected function prepareUpdateProfileReminder()
    {
        $this->options['message'] = $this->getParameter('message');
        $this->options['to'] = $this->getParameter('receiver');
    }

    protected function updateReceivedSms()
    {
        $secret = $this->getParameter('secret');
        if ($secret !== SETTINGS['sms_settings']['smsgateway']['callback_secret']) {
            // log error and exit
            $msg = 'The secret sent by an update request was wrong.';
            $this->N->log($msg, 'SMS->updateReceivedSms()');
        }
        $event = strtolower($this->getParameter('event'));
        if ($event === 'update') {
            $msg_id = $this->getParameter('id');
            $message = $this->N->ORM->findBy('Message', ['ExtId' => $msg_id]);
            if (!empty($message)) {
                $e_id = $message->getId();
                $val = strtolower($this->getParameter('status'));

                return (new Update)->updateProperty('Message', $e_id, 'Status', $val)->flush();
            }
        } elseif ($event === 'received') {
            $check = $this->checkReceivedSmsForConfirmation();
            if ($check['about_visit']) {
                $e_id = $check['visit_id'];

                return (new Update)->updateProperty('Visit', $e_id, 'Confirmed', true)->flush();
            }
        }
    }

    protected function checkReceivedSmsForConfirmation()
    {
        $return['about_visit'] = false;
        $contact = $this->getParameter('contact');
        $nr = $contact['number'];
        $properties['Status'] = 'sent';
        $properties['Carrier'] = 'sms';
        $properties['Subject'] = 'confirmation';
        $users = $this->N->ORM->getRepository('User')
            ->findViaMethod('getStandardizedMobil', $nr);

        if (count($users) === 0) {
            // probably no sms related to the database
            exit('No user from the database.');
        } elseif (count($users) > 1) {
            $log_msg = 'There seem to be several users with the number '.$nr.'. Check this!';
            $this->N->log($log_msg, 'SMS->checkReceivedSmsForConfirmation()');
        } else {
            $user = reset($users);
        }
        /* @var User $user */
        $user->sortMessagesByDate();
        $user_messages = $user->getFilteredMessages($properties);
        $n_visit = $this->N->ORM->getRepository('Group')->getNextVisitForUser($user);
        /* @var Visit $n_visit */
        if (empty($n_visit)) {
            $log_msg = 'Received sms from '.$user->getFullName().' without a next visit. Check!';
            $this->N->log($log_msg, 'SMS->checkReceivedSmsForConfirmation()');
        }
        $message = array_filter(
            $user_messages->toArray(),
            function ($m) use ($n_visit) {
                /* @var Message $m */
                return $m->getContent('visit_id') == $n_visit->getId();
            }
        );
        if (count($message) == 0) {
            $log_msg = 'A user sent a message to you without having been sent a message first. Check this!';
            $this->N->log($log_msg, 'SMS->checkReceivedSmsForConfirmation()');
        }

        $content = strtolower($this->getParameter('message'));
        $content = preg_replace('[^a-zA-Z]', '', $content);
        if (substr($content, 0, 2) == 'ja') {
            $return['about_visit'] = true;
            $return['visit_id'] = $n_visit->getId();
        }

        return $return;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;
    }



}
