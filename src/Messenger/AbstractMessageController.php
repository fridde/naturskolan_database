<?php


namespace Fridde\Messenger;


use Fridde\Controller\BaseController;
use Fridde\Utility;

abstract class AbstractMessageController extends BaseController
{
    protected $type;
    protected $status;
    protected $errors;

    abstract protected function getMethods();

    public const PREPARE = 1;
    public const SEND = 2;
    public const UPDATE = 4;

    public function buildAndSend()
    {
        $this->setActionsFromPurpose();
        foreach($this->getActions() as $action){
            call_user_func([$this, $action]);
        }
        return $this;
    }

    protected function setActionsFromPurpose()
    {
        $purpose = $this->getParameter('purpose');

        $methods = $this->getMethods();
        $method_value = $methods[$purpose] ?? 0 ;


        if($method_value & self::PREPARE){
            $this->addAction(Utility::toCamelCase('prepare_' . $purpose));
        }
        if($method_value & self::SEND){
            $this->addAction('send');
        }
        if($method_value & self::UPDATE){
            $this->addAction(Utility::toCamelCase($purpose));
        }
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

     /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param mixed $errors
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
    }




}
