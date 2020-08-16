<?php

namespace Fridde\Controller;

use Carbon\Carbon;
use Fridde\Annotations\SecurityLevel;
use Fridde\Entities\Message;
use Fridde\Utility;
use ZipStream\ZipStream;
use Fridde\Controller\ViewController as VC;

class FileController extends BaseController
{
    private const CALENDAR_FILE = 'kalender.ics';

    private const SUBJECT_TEMPLATE_TRANSLATOR = [
        Message::SUBJECT_VISIT_CONFIRMATION => 'confirm_visit',
        Message::SUBJECT_INCOMPLETE_PROFILE => 'incomplete_profile',
        Message::SUBJECT_NEW_GROUP => 'dates_new_groups',
        Message::SUBJECT_CONTINUED_GROUP => 'dates_continuing_groups'
    ];

    public static $ActionTranslator = [
        'mail' => 'downloadAllMails',
    ];

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function getCalendar()
    {
        $file = BASE_DIR.'/'.self::CALENDAR_FILE;

        header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename='.self::CALENDAR_FILE);

        echo file_get_contents($file);

        exit;
    }

    /**
     * @param string $subject
     * @throws \Fridde\Error\NException
     *
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */

    public function downloadAllMails(int $subject = Message::SUBJECT_NEW_GROUP, string $segment = null)
    {
        $this->removeAction('mail');
        $vc = new VC();
        $data = $vc->compileMailData($segment, $subject);
        $data['subject'] = $subject;

        $this->setDATA($data);

        $template = self::SUBJECT_TEMPLATE_TRANSLATOR[$subject];
        $this->setTemplate('mail/'.$template);
        $this->setReturnType(self::RETURN_TEXT);

        $date_string = Utility::replaceNonAlphaNumeric(Carbon::now()->toDateTimeString());
        $file_name = 'mails_'.$subject.'_'.$date_string.'.zip';

        $zip = new ZipStream($file_name);

        foreach ($data['users'] as $user_id => $user) {
            $this->addToDATA('user', $user, true);
            $text = $this->handleRequest();

            $zip->addFile($user['file_name'], $text);
        }
        $zip->finish();
    }
}
