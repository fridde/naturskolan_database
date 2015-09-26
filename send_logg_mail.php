<?php
  ob_start();
  require("create_check_logg.php");
  $message = ob_get_clean();

  $to = "craoxoph@sharklasers.com";
  $subject = "Veckans logg från Naturskolan";
  $txt = $message;
  $headers = "From: info@sigtunanaturskola.se";

  $successful = mail($to,$subject,$txt,$headers);

  echo var_export($successful);


 ?>
