<?php

namespace Fridde\Controller;


class ErrorController extends BaseController {

    public static $ActionTranslator = [
        'page_not_found' => 'prepareFourOFour'
    ];

    private function prepareFourOFour()
    {
        echo 'Visit NOT confirmed!';
    }

    protected function displayErrorMessage()
    {
        $this->setTemplate('error');

        $this->addToDATA('error_message', $this->getParameter('message'));
    }

}
