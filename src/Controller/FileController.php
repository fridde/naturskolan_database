<?php

namespace Fridde\Controller;

use Carbon\Carbon;
use Fridde\Annotations\SecurityLevel;
use Fridde\Utility;
use ZipStream\ZipStream;

class FileController extends BaseController
{
    private const CALENDAR_FILE = 'kalender.ics';

    public static $ActionTranslator = [
        'mail' => 'downloadAllMails'
    ];

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

    /**
     * @param string $subject
     * @throws \Fridde\Error\NException
     *
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */

    public function downloadAllMails(string $subject = 'new', string $segment = null)
    {
        $this->removeAction('mail');
        $vc = new ViewController();
        $data = $vc->compileMailData($subject, $segment);
        $data['subject'] = $subject;

        $this->setDATA($data);

        $this->setTemplate('mail/single_mail_template');
        $this->setReturnType(self::RETURN_TEXT);

        $date_string = Utility::replaceNonAlphaNumeric(Carbon::now()->toDateTimeString());
        $file_name = 'mails_'. $subject . '_' . $date_string . '.zip';

        $zip = new ZipStream($file_name);

        foreach($data['users_by_segments'] as $segment => $users){
            foreach($users as $user_id => $user){
                $this->addToDATA('user', $user, true);
                $text = $this->handleRequest();

                $zip->addFile($user['file_name'], $text);
            }
        }
        $zip->finish();
    }
}
