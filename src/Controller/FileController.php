<?php

namespace Fridde\Controller;

class FileController extends BaseController
{
    private const CALENDAR_FILE = 'aventyr_kalender.ics';

    protected $ActionTranslator = ['visit_confirmed' => 'VisitConfirmed'];

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function getCalendar()
    {
        $file = BASE_DIR . self::CALENDAR_FILE;

        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename=aventyr_kalender.ics');

        echo file_get_contents($file);

        exit;
    }
}
