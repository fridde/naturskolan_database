<?php

namespace Fridde\Controller;

//use Fridde\{Naturskolan, Utility as U, Task};
//use Carbon\Carbon;

class MailController {

    private $RQ;
    private $SETTINGS;
    private $mail_type;
    private $template = "default_mail";
    private $is_legit_request = false;
    private $type_mapper = ["admin_summary" => "AdminSummary", "default" => "DefaultMail"];

    public function __construct()
    {
        $this->RQ = $_REQUEST;
        $this->SETTINGS = $GLOBALS["SETTINGS"];
    }

    public function send($params = [])
    {
        $this->checkRequestApiKey();
        if(! $this->is_legit_request){
            return;
        }
        $this->mail_type = $params["type"] ?? "default";
        $method_name = "prepare" . $this->type_mapper[$this->mail_type];
        $DATA = $this->$method_name();

        $H = new H();
        $H->setTitle();
        $H->addDefaultJs("index")->addDefaultCss("index")
        ->setTemplate($this->template)->setBase();

        $H->addVariable("DATA", $DATA);
        $H->render();
    }

    private function prepareAdminSummary()
    {
        $this->template = "admin_summary";

        // TODO: continue here!   
        return $DATA;
    }

    private function prepareDefaultMail()
    {
    }

    private function checkRequestApiKey()
    {
        $request_api_key = $this->getRQ("api_key");
        $settings_api_key = $this->SETTINGS["smtp_settings"]["api_key"];
        $this->is_legit_request = $request_api_key === $settings_api_key;
        return $this->is_legit_request;
    }

    private function getRQ($key)
    {
        return $this->RQ[$key] ?? null;
    }
}
