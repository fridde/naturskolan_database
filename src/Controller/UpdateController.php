<?php

namespace Fridde\Controller;

use Fridde\{Naturskolan, Update};

class UpdateController {

    public static function handleRequest($parameters = [])
    {
        $update = new Update($_REQUEST);
        $return = $update->execute()->getReturn();
        echo json_encode($return);
    }
    
}
