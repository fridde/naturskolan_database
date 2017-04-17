<?php

namespace Fridde\Controller;

class FileController {

    private $params;

    public function __construct($params){
        $this->params = $params;
    }

    public function renderSandbox()
    {
        include(BASE_DIR . "sandbox.php");

    }

    public function include()
    {
        $path = BASE_DIR;
        $depth = 3;
        $dir_names = [];
        foreach(range(1, $depth) as $i){
            $dir_names[] = $this->params["file" . $i] ?? null;
        }
        $path .= implode('\\', array_filter($dir_names)) . ".php";

        include($path);
    }
}
