<?php

namespace Fridde\Controller;

use Fridde\Entities\Message;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Update;
use Fridde\ShortMessageService;

// TODO: This class contains the same methods as the class SMS. Something is wrong!
class SMSController extends MessageController
{
    protected $text;
    protected $options;
    // PREPARE=1; SEND=2; UPDATE=4;
    protected $methods = ["update_received_sms" => 4, "confirm_visit" => 3];

    public function __construct($params = [])
    {
        parent::__construct($params);
    }

    public function send()
    {
        $SMS = new ShortMessageService($this->options);

        if (!empty(DEBUG)) {
            $result = $SMS->send(SETTINGS["debug"]["mobil"]);
        } else {
            $result = $SMS->send();
        }

        // TODO: check if really necessary
        array_walk_recursive(
            $result,
            function (&$i) {
                $i = addslashes($i);
            }
        );
        echo json_encode($result);
    }

    protected function prepareConfirmVisit()
    {
        $this->options["message"] = $this->getRQ("message");
        $this->options["to"] = $this->getRQ("receiver");
    }

    protected function updateReceivedSms()
    {
        $secret = $this->getRQ("secret");
        if ($secret !== SETTINGS["sms_settings"]["smsgateway"]["callback_secret"]) {
            $this->N->log('The callback secret didn\'t match with the one in the settings');
        }
        $event = strtolower($this->getRQ("event"));
        if ($event == "update") {
            $msg_id = $this->getRQ("id");
            /* @var Message $message  */
            $message = $this->N->ORM->findBy("Message", ["ExtId" => $msg_id]);
            if (!empty($message)) {
                $e_id = $message->getId();
                $val = strtolower($this->getRQ("status"));
                return (new Update)->updateProperty("Message", $e_id, "Status", $val)->flush();
            }
        } elseif ($event == "received") {
            $check = $this->checkReceivedSmsForConfirmation();
            if ($check["about_visit"]) {
                $e_id = $check["visit_id"];
                return (new Update)->updateProperty("Visit", $e_id, "Confirmed", true)->flush();
            }
        }
        return false;
    }

    protected function checkReceivedSmsForConfirmation()
    {
        $return["about_visit"] = false;
        $contact = $this->getRQ("contact");
        $nr = $contact["number"];
        $properties["Status"] = "sent";
        $properties["Carrier"] = "sms";
        $properties["Subject"] = "confirmation";
        $users = $this->N->ORM->getRepository("User")
            ->findViaMethod("getStandardizedMobil", $nr);

        if (count($users) === 0) {
            // probably no sms related to the database
            exit("No user from the database.");
        } elseif (count($users) > 1) {
            $log_msg = 'There seem to be several users with the number '. $nr . '. Check this!';
            $this->N->log($log_msg, 'SMSController->checkReceivedSmsForConfirmation()');
        } else {
            $user = reset($users);
        }
        /* @var User $user */
        $user->sortMessagesByDate();
        $user_messages = $user->getFilteredMessages($properties);
        $n_visit = $this->N->ORM->getRepository("Group")->getNextVisitForUser($user);
        /* @var Visit $n_visit */
        if (empty($n_visit)) {
            $log_msg = 'Received sms from '.$user->getFullName().' without a next visit. Check!';
            $this->N->log($log_msg, 'SMSController->checkReceivedSmsForConfirmation()');
        }
        $message = array_filter(
            $user_messages->toArray(),
            function ($m) use ($n_visit) {
                /* @var Message $m */
                return $m->getContent("visit_id") == $n_visit->getId();
            }
        );
        if (count($message) == 0) {
            $log_msg = 'A user sent a message to you without having been sent a message first. Check this!';
            $this->N->log($log_msg, 'SMSController->checkReceivedSmsForConfirmation()');
        }

        // ready to check!
        $content = strtolower($this->getRQ("message"));
        $content = preg_replace('[^a-zA-Z]', '', $content);
        if (substr($content, 0, 2) == 'ja') {
            $return["about_visit"] = true;
            $return["visit_id"] = $n_visit->getId();
        }

        return $return;
    }
}
