<?php

namespace Fridde\Controller;

use Fridde\{HTML};


class ErrorController {

    public $N;
    private $params;
    private $type;
    private $methods_mapper = ['Page not found' => 'FourOFour'];

    public function __construct($params)
    {
        $this->N = $GLOBALS['CONTAINER']->get('Naturskolan');
        $this->params = $params;
        $this->type = $this->params['type'] ?? null;
    }

    public function show()
    {
        $method = $this->methods_mapper[$this->type] ?? 'Undefined';
        $method_name = 'prepare' . $method;
        $this->$method_name();

        $H = new HTML();
    }

    private function prepareFourOFour()
    {
        echo 'Visit NOT confirmed!';
    }

    private function prepareUndefined()
    {
    }
}
