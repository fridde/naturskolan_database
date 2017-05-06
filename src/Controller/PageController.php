<?php

namespace Fridde\Controller;

use Fridde\HTML;


class PageController {

    public $N;
    private $params;
    private $page;
    private $methods_mapper = ["visit_confirmed" => "VisitConfirmed"];

    public function __construct($params)
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->params = $params;
        $this->page = $this->params["page"] ?? null;
    }

    public function show()
    {
        $method = $this->methods_mapper[$this->page] ?? null;
        if(empty($method)){
            $EC = new ErrorController(["type" => "Page not found", "page" => $this->page]);
            return $EC->render();
        }
        $method_name = "prepare" . $method;
        $this->$method_name();

        $H = new HTML();
    }

    private function prepareVisitConfirmed()
    {
        echo "Visit confirmed!";
    }
}
