<?php

header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=aventyr_kalender.ics');
echo file_get_contents("aventyr_kalender.ics");