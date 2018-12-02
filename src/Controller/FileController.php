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

        header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', FALSE);
        header('Pragma: no-cache');
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . self::CALENDAR_FILE);

        echo file_get_contents($file);

        exit;
    }
}
