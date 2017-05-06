<?php

namespace Fridde\Controller;

abstract class MessageController {

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

    /**
     * Converts as string written in *snake_case* to *CamelCase* or *camelCase*
     * @param  string  $snake_case_string
     * @param  boolean $ignore_first_letter Should the first letter be converted, too?
     * @return string The CamelCase string
     */
    protected function toCamelCase(string $snake_case_string, bool $ignore_first_letter = false)
    {
        $words = explode("_", $snake_case_string);
        array_walk($words, function(&$word, $i) use ($ignore_first_letter){
            if($i !== 0 || !$ignore_first_letter){
                $word = ucfirst($word);
            }
        });
        return implode("", $words);
    }
}
