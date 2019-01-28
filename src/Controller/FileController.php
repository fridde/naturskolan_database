<?php

namespace Fridde\Controller;

use Fridde\Annotations\SecurityLevel;
use ZipStream\ZipStream;

class FileController extends BaseController
{
    private const CALENDAR_FILE = 'kalender.ics';
    private const MAIL_ZIP_FILE = 'mails.zip';

    public static $ActionTranslator = [
        'mail' => 'getAllMails'
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

    public function getAllMails()
    {
        $this->removeAction('mail');
        $vc = new ViewController();
        $data = $vc->compileMailData();

        $this->setDATA($data);

        $this->setTemplate('admin/single_mail_template');
        $this->setReturnType(self::RETURN_TEXT);

        $zip = new ZipStream(self::MAIL_ZIP_FILE);

        foreach($data['users_by_segments'] as $segment => $users){
            foreach($users as $user_id => $user){
                $this->addToDATA('user', $user);
                $text = $this->handleRequest();

                $zip->addFile($user['file_name'], $text);
            }
        }
        $zip->finish();
    }
}
