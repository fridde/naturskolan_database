<?php

namespace Fridde\Controller;


class ErrorController extends BaseController {

    protected $ActionTranslator = ['page_not_found' => 'prepareFourOFour'];

    private function prepareFourOFour()
    {
        echo 'Visit NOT confirmed!';
    }

}
