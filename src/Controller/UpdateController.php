<?php

namespace Fridde\Controller;

use Fridde\Update;
use Fridde\Utility as U;

class UpdateController extends BaseController {

    public function __construct($params)
    {
        parent::__construct($params, true);
    }

    public function handleRequest()
    {
        $update = new Update($this->getFromRequest());
        $update_method = $this->getFromRequest('updateMethod') ?? $this->getFromRequest('update_method');
        if(empty($update_method)){
            throw new \InvalidArgumentException('Missing updateMethod in $_REQUEST');
        }
        $args = U::pluck($this->getFromRequest(), Update::getMethodArgs($update_method));
        call_user_func_array([$update, $update_method], $args);
        $update->flush();
        echo json_encode($update->getReturn());
    }

}
