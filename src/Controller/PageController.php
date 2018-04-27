<?php

namespace Fridde\Controller;

class PageController extends BaseController
{
    protected $ActionTranslator = ['visit_confirmed' => 'VisitConfirmed'];

    public function handleRequest()
    {
        if(empty($this->getParameter('url'))){
            $this->setTemplate('index');
        }
        parent::handleRequest();
    }


    public function showError()
    {
        http_response_code(404);
        echo 'The url ' . implode('/', $this->getParameter()) . ' could not be resolved';
        die();
    }
}
