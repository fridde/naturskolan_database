<?php

define('BEFORE', microtime(true));
function toConsole($txt){
    echo '<script>console.log(\'' . $txt .'\')</script>';
}
function showDebugTime(){
    $txt = round(microtime(true) - BEFORE, 2) . ' seconds';
    toConsole($txt);
}
