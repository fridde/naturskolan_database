<?php

namespace Fridde\Controller;

abstract class MessageController {

    protected $N;
    protected $RQ;
    protected $params;
    protected $SETTINGS;
    protected $is_legit_request = false;
    protected $type;
    const PREPARE = 1;
    const SEND = 2;
    const UPDATE = 4;

    public function __construct($params = [])
    {
        $this->params = $params;
        //$GLOBALS['CONTAINER']->get('Logger')->info("PARAMS: ". json_encode($params));
        $encoding = $this->params["encoding"] ?? "json";
        $this->RQ = getRequest($encoding);

        $this->N = $GLOBALS["CONTAINER"]->get('Naturskolan');
    }

    public function handleRequest()
    {
        $type = $this->params["type"];
        $method_value = $this->methods[$type] ?? 0 ;

        if($method_value & self::PREPARE){
            $this->checkRequestApiKey();
            $prepare_method_name = "prepare" . $this->camelCase($type);
            $this->$prepare_method_name();
        }
        if($method_value & self::SEND){
            $response = $this->send();
        }
        if($method_value & self::UPDATE){
            $method_name = $this->camelCase($type);
            $response = $this->$method_name();
        }
        echo json_encode($response ?? "");
    }


    protected function checkRequestApiKey()
    {
        $request_api_key = $this->getRQ("api_key");
        $settings_api_key = SETTINGS["values"]["api_key"];
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

    protected function camelCase($snake_case_string, $ignore_first_letter = false){
        $words = explode("_", $snake_case_string);
        array_walk($words, function(&$word, $i) use ($ignore_first_letter){
            if($i !== 0 || !$ignore_first_letter){
                $word = ucfirst($word);
            }
        });
        return implode("", $words);
    }
}
