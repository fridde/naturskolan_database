<?php

namespace Fridde\Controller;

class FileController {

    public function renderSandbox()
    {
        echo file_get_contents("sandbox.php");

    }

    public static function include($params = [])
    {
        $path = ($GLOBALS["BASE_DIR"] ?? "") . '\\';
        $depth = 3;
        $dir_names = [];
        foreach(range(1, $depth) as $i){
            $dir_names[] = $params["file" . $i] ?? null;
        }
        $path .= implode('\\', array_filter($dir_names)) . ".php";

        include($path);
    }
}
