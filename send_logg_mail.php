<?php
  ob_start();
  require("check_logg.php");
  $message = ob_get_clean();

  $to = "akobllue@sharklasers.com";
  $subject = "Veckans logg från Naturskolan";
  $txt = $message;
  $headers = "From: info@sigtunanaturskola.se";

  $successful = mail($to,$subject,$txt,$headers);

  echo var_export($successful);


 ?>
