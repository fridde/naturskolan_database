<?php

namespace Fridde\Controller;

use Carbon\Carbon;
use Fridde\Annotations\SecurityLevel;
use Fridde\HTML;
use Fridde\Security\Authorizer;
use Fridde\Table;

class TableController extends BaseController
{

    private Table $table;

    public function handleRequest(): ?string
    {
        $this->addAction('view');
        return parent::handleRequest();
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function view(string $subgroup = 'all')
    {
        $this->table = new Table($this->params['entity'], $this->N->ORM);
        $rows = $this->table->build($subgroup);

        $this->addToDATA('rows', $rows);
        $this->addToDATA('headers', $this->table->getColumnHeaders());
        $this->addToDATA('entity_class', $this->table->getEntityClass());
        $this->addToDATA('sortable', $this->table->isSortable());
        $this->addToDATA('school_id', $this->Authorizer->getVisitor()->getSchool()->getId());
        $this->addToDATA('today', Carbon::today()->toDateString());


        $this->setTemplate('table');

        $this->addJsToEnd('admin', HTML::INC_ASSET);
        $this->addCss('admin', HTML::INC_ASSET);
    }

}
