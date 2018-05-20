<?php

namespace Fridde\Controller;

use Fridde\Security\Authorizer;

class PageController extends BaseController
{
    protected $ActionTranslator = ['visit_confirmed' => 'VisitConfirmed'];

    protected $Security_Levels = [
        'viewPage' => Authorizer::ACCESS_ALL,
    ];

    public function handleRequest()
    {
        $this->addAction('viewPage');
        parent::handleRequest();
    }

    public function viewPage()
    {
        if (empty($this->getParameter('url'))) {
            $this->setTemplate('index');
        }
    }


    public function showError()
    {
        http_response_code(404);
        echo 'The url '.implode('/', $this->getParameter()).' could not be resolved';
        die();
    }
}
