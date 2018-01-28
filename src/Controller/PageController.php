<?php

namespace Fridde\Controller;

use Fridde\HTML;


class PageController extends BaseController
{
    private $page;
    private $methods_mapper = ['visit_confirmed' => 'VisitConfirmed'];

    public function __construct($params)
    {
        parent::__construct($params);
        $this->page = $this->params['page'] ?? null;
    }

    public function show()
    {
        $method = $this->methods_mapper[$this->page] ?? null;
        if (empty($method)) {
            $EC = new ErrorController(['type' => 'Page not found', 'page' => $this->page]);

            return $EC->render();
        }
        $method_name = 'prepare'.$method;
        $this->$method_name();
    }

    public function showIndex()
    {
        $this->setTemplate('index');
        parent::handleRequest();
    }
}
