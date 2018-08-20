<?php

namespace Fridde\Controller;

class FileController extends BaseController
{
    private const CALENDAR_FILE = 'kalender.ics';

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function getCalendar()
    {
        $file = BASE_DIR . '/' . self::CALENDAR_FILE;

        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . self::CALENDAR_FILE);

        echo file_get_contents($file);

        exit;
    }
}
