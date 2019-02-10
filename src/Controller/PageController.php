<?php

namespace Fridde\Controller;

use Fridde\Annotations\SecurityLevel;
use Fridde\Entities\Visit;
use Fridde\HTML;

class PageController extends BaseController
{
    public static $ActionTranslator = ['visit_confirmed' => 'VisitConfirmed'];

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
    public function showSupport(): void
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
    public function showContact(): void
    {
        $visitor = $this->Authorizer->getVisitor();
        if($visitor->hasSchool()){
            $this->addToDATA('school_id', $visitor->getSchool()->getId());
        }

        $this->addJsToEnd('captcha', HTML::INC_ASSET);
        $this->setTemplate('contact');
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function showError(): void
    {
        http_response_code(404);
        echo 'The url '.implode('/', $this->getParameter()).' could not be resolved';
        die();
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function showConfirmedVisit(Visit $visit, string $school_id): void
    {
        $this->addToDATA('school_id', $school_id);
        $this->setTemplate('visit_confirmation');
        $this->addToDATA('visit_date', $visit->getDateString());
        $this->addToDATA('visit_label', $visit->getLabel('TGSU'));
    }


}
