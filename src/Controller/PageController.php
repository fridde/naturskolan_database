<?php

namespace Fridde\Controller;

class PageController extends BaseController
{
    protected $ActionTranslator = ['visit_confirmed' => 'VisitConfirmed'];

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function viewIndex()
    {
        if (empty($this->getParameter('url'))) {
            $this->setTemplate('index');
        }
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function showSupport()
    {
       $section = $this->getParameter('section') ?? 'summary';

       $this->setTemplate('help/' . $section);
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function showContact()
    {
        $this->setTemplate('contact');
    }

//showContact


    public function showError()
    {
        http_response_code(404);
        echo 'The url '.implode('/', $this->getParameter()).' could not be resolved';
        die();
    }
}
