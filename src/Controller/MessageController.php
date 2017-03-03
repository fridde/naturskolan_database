<?php

namespace Fridde\Controller;

abstract class MessageController {

    protected $RQ;
    protected $SETTINGS;
    protected $is_legit_request = false;
    protected $type;

    public function __construct()
    {
        $this->RQ = $_REQUEST;
    }

    protected function prepare($params)
    {
        $this->checkRequestApiKey();
        $this->type = $params["type"] ?? "default";

        $method_name = "prepare" . $this->type_mapper[$this->type];
        $this->$method_name();
    }


    protected function checkRequestApiKey()
    {
        $request_api_key = $this->getRQ("api_key");
        $settings_api_key = SETTINGS["smtp_settings"]["api_key"];
        $this->is_legit_request = $request_api_key === $settings_api_key;
        if(! $this->is_legit_request){
            throw new \Exception("No valid api_key found!");
            exit();
        }
        return $this->is_legit_request;
    }

    protected function getRQ($key)
    {
        return $this->RQ[$key] ?? null;
    }
}
