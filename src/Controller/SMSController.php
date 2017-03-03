<?php

namespace Fridde\Controller;

use Fridde\SMS;

class SMSController extends MessageController
{
    protected $text;
    private $params;
    protected $options;
    protected $type_mapper = ["confirm_visit" => "VisitConfirmationSMS"];

    public function __construct($params)
    {
        parent::__construct();
        $this->params = $params;
    }

    public function send()
    {
        $this->prepare($this->params);
        $SMS = new SMS($this->options);
        echo json_encode($SMS->send());
    }

    protected function prepareVisitConfirmationSMS()
    {
        $this->options["message"] = $this->getRQ("message");
        $this->options["to"] = $this->getRQ("receiver");
    }
}
