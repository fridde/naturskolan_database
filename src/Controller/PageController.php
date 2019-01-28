<?php

namespace Fridde\Controller;

use Fridde\Annotations\SecurityLevel;
use Fridde\Entities\Visit;

class PageController extends BaseController
{
    protected static $ActionTranslator = ['visit_confirmed' => 'VisitConfirmed'];

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

        $visitor = $this->Authorizer->getVisitor();
        if($visitor->hasSchool()){
            $this->addToDATA('school_id', $visitor->getSchool()->getId());
        }

        $this->setTemplate('help/'.$section);
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function showContact()
    {
        $visitor = $this->Authorizer->getVisitor();
        if($visitor->hasSchool()){
            $this->addToDATA('school_id', $visitor->getSchool()->getId());
        }

        $this->addJs('captcha');
        $this->setTemplate('contact');
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function showError()
    {
        http_response_code(404);
        echo 'The url '.implode('/', $this->getParameter()).' could not be resolved';
        die();
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function showConfirmedVisit(Visit $visit, string $school_id)
    {
        $this->addToDATA('school_id', $school_id);
        $this->setTemplate('visit_confirmation');
        $this->addToDATA('visit_date', $visit->getDateString());
        $this->addToDATA('visit_label', $visit->getLabel('TGSU'));
    }


}
