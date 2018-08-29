<?php


namespace Fridde\Messenger;


use Fridde\Controller\BaseController;
use Fridde\Utility;

abstract class AbstractMessageController extends BaseController
{
    protected $carrier_type;
    protected $status;
    protected $errors;

    abstract protected function getMethods();

    public const PREPARE = 1;
    public const SEND = 2;
    public const UPDATE = 4;

    public function buildAndSend()
    {
        $this->setActionsFromSubject();
        foreach($this->getActions() as $action){
            call_user_func([$this, $action]);
        }
        return $this;
    }

    protected function setActionsFromSubject()
    {
        $subject_int = $this->getParameter('subject_int');

        $method_mask = $this->getMethodMaskForSubject($subject_int);

        if($method_mask & self::PREPARE){
            $this->getMethodNameForSubject($subject_int, 'prepare');
            $this->addAction($this->getMethodNameForSubject($subject_int, 'prepare'));
        }
        if($method_mask & self::SEND){
            $this->addAction('send');
        }
        if($method_mask & self::UPDATE){
            $this->addAction($this->getMethodNameForSubject($subject_int, 'update'));
        }
    }

    protected function getMethodNameForSubject(int $subject, string $prefix = '')
    {
        $suffix = $this->getMethods()[$subject][1] ?? '';

        return $prefix . $suffix;
    }

    protected function getMethodMaskForSubject(int $subject): int
    {
        $methods = $this->getMethods();

        return $methods[$subject][0] ?? 0;
    }

    /**
     * @return mixed
     */
    public function getCarrierType()
    {
        return $this->carrier_type;
    }

    /**
     * @param mixed $type
     */
    public function setCarrierType(int $carrier_type)
    {
        $this->carrier_type = $carrier_type;
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
