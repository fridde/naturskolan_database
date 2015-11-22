<?php
/* PREAMBLE */
$url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
$filename = "include.php";
//copy($url, $filename);
include $filename;
/* END OF PREAMBLE */
inc("fnc"); //remove "TRUE" in production


$form = "";
$formAtts = array("action" => "update_db.php", "method" => "post");
$form .= tag("input", "", array("type" => "submit", "value" => "Skicka"));
$form .= tag("br");
$form .= tag("textarea", "", array("rows" => 30, "cols" => 100, "name" => "postdata"));

$html = tag("form", $form, $formAtts);

echo tag("html", $html);

 ?>
