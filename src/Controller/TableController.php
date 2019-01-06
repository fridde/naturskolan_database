<?php

namespace Fridde\Controller;

use Carbon\Carbon;
use Fridde\Annotations\SecurityLevel;
use Fridde\HTML;
use Fridde\Security\Authorizer;
use Fridde\Table;

class TableController extends BaseController
{

    /* @var Table $table  */
    private $table;

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function view()
    {
        $this->table = new Table($this->params['entity'], $this->N->ORM);
        $rows = $this->table->build();

        $this->addToDATA('rows', $rows);
        $this->addToDATA('headers', $this->table->getColumnHeaders());
        $this->addToDATA('entity_class', $this->table->getEntityClass());
        $this->addToDATA('sortable', $this->table->isSortable());
        $this->addToDATA('school_id', $this->Authorizer->getVisitor()->getSchool()->getId());
        $this->addToDATA('today', Carbon::today()->toDateString());


        $this->setTemplate('table');

        $this->addJs('DT', HTML::INC_ABBREVIATION);
        $this->addJs('js/DT_config', HTML::INC_ADDRESS);

        $this->addCss('DT');
    }

}
