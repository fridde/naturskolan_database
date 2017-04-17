<?php

namespace Fridde\Controller;

use Fridde\{Update};

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
        $return = $update->execute()->getReturn();
        echo json_encode($return);
    }

}
