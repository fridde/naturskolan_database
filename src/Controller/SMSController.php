<?php

namespace Fridde\Controller;

use Fridde\Update;
use Fridde\SMS;

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
        $SMS = new SMS($this->options);
        $result = $SMS->send();
        array_walk_recursive($result, function(&$i){
            $i = addslashes($i);
        });
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
        if($secret !== SETTINGS["sms_settings"]["smsgateway"]["callback_secret"]){
            // log error and exit
        }
        $event = strtolower($this->getRQ("event"));
        if($event == "update"){
            $msg_id = $this->getRQ("id");
            $message = $this->N->findBy("Message", ["ExtId" => $msg_id]);
            if(!empty($message)){
                $request["updateMethod"] = "updateProperty";
                $request["entity_class"] = "Message";
                $request["entity_id"] = $message->getId();
                $request["property"] = "Status";
                $request["value"] = strtolower($this->getRQ("status"));
                $update_result = Update::create($request);
            }
        } elseif($event == "received"){
            $check = $this->checkReceivedSmsForConfirmation();
            if($check["about_visit"]){
                $request["updateMethod"] = "updateProperty";
                $request["entity_class"] = "Visit";
                $request["entity_id"] = $check["visit_id"];
                $request["property"] = "Confirmed";
                $request["value"] = "confirmed";
                $update_result = Update::create($request);
            }
        }
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

        if(count($users) === 0){
            // probably no sms related to the database
            exit("No user from the database.");
        } elseif(count($users) > 1){
            // there seem to be several users with the same mobile number. weird!
        } else {
            $user = reset($users);
        }

        $user->sortMessagesByDate();
        $user_messages = $user->getFilteredMessages($properties);
        $n_visit = $this->N->ORM->getRepository("Group")
        ->getNextVisitForUser($user);
        if(empty($n_visit)){
            // probably no sms related to the database
            // but still worth logging maybe?
        }
        $message = array_filter($user_messages->toArray(), function($m) use ($n_visit){
            return $m->getContent("visit_id") == $n_visit->getId();
        });
        if(count($message) == 0){
            // have not asked for message, but still get an sms from user
        }

        // ready to check!
        $content = strtolower($this->getRQ("message"));
        $content = preg_replace('[^a-zA-Z]', '', $content);
        if(substr($content, 0, 2) == 'ja'){
            $return["about_visit"] = true;
            $return["visit_id"] = $n_visit->getId();
        }
        return $return;
    }
}
