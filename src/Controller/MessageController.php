<?php

namespace Fridde\Controller;

abstract class MessageController
{
    /** @var \Fridde\Naturskolan shortcut for the Naturskolan object in the global container */
    protected $N;
    protected $RQ;
    protected $params;
    protected $is_legit_request = false;
    const PREPARE = 1;
    const SEND = 2;
    const UPDATE = 4;

    public function __construct(array $params = [])
    {
        $this->params = $params;
        $encoding = $this->params["encoding"] ?? "json";
        $this->RQ = getRequest($encoding);        
        $this->N = $GLOBALS["CONTAINER"]->get('Naturskolan');
    }

    /**
     *
     */
    public function handleRequest()
    {
        $purpose = $this->params["purpose"];
        $method_value = $this->methods[$purpose] ?? 0 ;

        if($method_value & self::PREPARE){
            $this->checkRequestApiKey();
            $prepare_method_name = "prepare" . $this->toCamelCase($purpose);
            $this->$prepare_method_name();
        }
        if($method_value & self::SEND){
            $response = $this->send();
        }
        if($method_value & self::UPDATE){
            $method_name = $this->toCamelCase($purpose);
            $response = $this->$method_name();
        }
        $response = json_encode($response ?? "");
        echo $response;
    }


    protected function checkRequestApiKey()
    {
        $request_api_key = $this->getRQ("api_key");
        $settings_api_key = SETTINGS["values"]["api_key"];
        $this->is_legit_request = $request_api_key === $settings_api_key;
        if(! $this->is_legit_request){
            throw new \Exception("No valid api_key found!");
        }
        return $this->is_legit_request;
    }

    protected function getRQ($key)
    {
        return $this->RQ[$key] ?? null;
    }


}
