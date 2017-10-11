<?php


namespace Fridde\Messenger;


class AbstractMessage
{
    /** @var \Fridde\Naturskolan shortcut for the Naturskolan object in the global container */
    protected $N;
    protected $params;

    const PREPARE = 1;
    const SEND = 2;
    const UPDATE = 4;

    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->N = $GLOBALS["CONTAINER"]->get('Naturskolan');
    }

    public function buildAndSend()
    {
        $returns = [];
        foreach($this->getFunctionsFromPurpose() as $function_name){
            $returns[] = $this->$function_name();
        }
        return $returns;
    }

    protected function getFunctionsFromPurpose()
    {
        $purpose = $this->params["purpose"] ?? null;
        $method_value = $this->methods[$purpose] ?? 0 ;

        $function_names = [];
        if($method_value & self::PREPARE){
            $function_names[] = "prepare" . $this->toCamelCase($purpose);
        }
        if($method_value & self::SEND){
            $function_names[] = 'send';
        }
        if($method_value & self::UPDATE){
            $function_names[] = $this->toCamelCase($purpose);
        }
        return $function_names;
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

    protected function getParam(string $key = null){
        if(empty($key)){
            return $this->params;
        }
        return $this->params[$key];
    }


}