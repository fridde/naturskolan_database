<?php

namespace Fridde\Controller;

use Fridde\{Update};

class UpdateController {

    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handleRequest()
    {
        $update = new Update($_REQUEST);
        $return = $update->execute()->getReturn();
        echo json_encode($return);
    }

}
