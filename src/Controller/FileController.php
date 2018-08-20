<?php

namespace Fridde\Controller;

class FileController extends BaseController
{
    protected $ActionTranslator = ['visit_confirmed' => 'VisitConfirmed'];

    public function handleRequest()
    {
        $this->addAction('viewPage');
        parent::handleRequest();
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
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
