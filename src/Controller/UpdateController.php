<?php

namespace Fridde\Controller;

use Fridde\Update;
use Fridde\Utility as U;

class UpdateController {

    private $params;
    private $RQ;

    public function __construct($params)
    {
        $this->params = $params;
        $encoding = $this->params["encoding"] ?? null;
        $this->RQ = getRequest($encoding);
    }

    public function handleRequest()
    {
        $update = new Update($this->RQ);
        $update_method = $this->RQ["updateMethod"] ?? $this->RQ["update_method"] ?? null;
        if(empty($update_method)){
            throw new \InvalidArgumentException('Missing updateMethod in $_REQUEST');
        }
        $args = U::pluck($this->RQ, Update::getMethodArgs($update_method));
        call_user_func_array([$update, $update_method], $args);
        $update->flush();
        echo json_encode($update->getReturn());
    }

}
