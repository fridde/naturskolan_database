<?php


use Carbon\Carbon;

use AcceptanceTester as A;

// codecept run acceptance CronCest --steps -f
class CronCest
{
    public function _before(A $I)
    {
        // if run manually, don't forget to first run
        // codecept run acceptance HelperTestsCest:resetDatabase
        $I->amOnPage('/');
        $I->setCookie('AuthKey', $I->get('natu', 'AuthKey'));
        $I->setCookie('XDEBUG_SESSION', 'PHPSTORM');
        $I->amOnPage('/admin');
        $I->deleteAllEmails();

    }

    public function _after(A $I)
    {
    }

    // codecept run acceptance CronCest:canSeeAllFields --steps -f
    public function canSeeAllFields(A $I)
    {
        $fields = $I->get('cron_items');
        foreach ($fields as $index => $label) {
            $I->canSee($label);
            $I->seeInSource($index);
        }
    }

    // codecept run acceptance CronCest:canToggleActivation --steps -f
    public function canToggleActivation(A $I)
    {
        $status_path = ['systemstatus', 'Value', ['id' => 'cron_tasks.activation']];
        $status = json_decode($I->grabFromDatabase(...$status_path), true);
        $I->assertTrue(empty($status['rebuild_calendar']));
        $cb_path = $I->get('paths', 'rebuild_calendar_cb');
        $I->checkOption($cb_path);
        $I->pause(1.5);
        $new_status = json_decode($I->grabFromDatabase(...$status_path), true);
        $I->assertEquals(1, $new_status['rebuild_calendar']);
        $I->uncheckOption($cb_path);
        $I->pause(1.5);
        $newest_status = json_decode($I->grabFromDatabase(...$status_path), true);
        $I->assertEquals(0, $newest_status['rebuild_calendar']);
    }


    // codecept run acceptance CronCest:calendarGetsRebuild --steps -f
    public function calendarGetsRebuild(A $I)
    {
        $cal_path = codecept_root_dir() .'/kalender.ics';
        if (file_exists($cal_path)) {
            $I->deleteFile($cal_path);
        }
        $I->runCronTask('rebuild_calendar');
        $I->seeFileFound('kalender.ics', codecept_root_dir());
        $I->seeInDatabase('systemstatus', ['id' => 'last_run.rebuild_calendar']);
    }


    // codecept run acceptance CronCest:calendarDoesntGetRebuild --steps -f
    public function calendarDoesntGetRebuild(A $I)
    {
        $cal_path = codecept_root_dir() .'/kalender.ics';
        $path_args = ['kalender.ics', codecept_root_dir()];
        $last_run = ['systemstatus', ['Value' => null], ['id' => 'last_run.rebuild_calendar']];
        $I->runCronTask('rebuild_calendar');
        if (file_exists($cal_path)) {
            $I->deleteFile($cal_path);
        }
        $I->runActivatedCronTasks();
        $I->dontSeeFileFound(...$path_args);

        $last_rebuild = $I->grabFromDatabase('systemstatus', 'Value', ['id' => 'last_run.rebuild_calendar']);

        $last_run[1]['Value'] = Carbon::parse($last_rebuild)->subMinutes(10)->toIso8601String();
        $I->updateInDatabase(...$last_run);
        $I->runActivatedCronTasks();
        $I->dontSeeFileFound(...$path_args);

        $last_run[1]['Value'] = Carbon::parse($last_rebuild)->subMinutes(20)->toIso8601String();
        $I->updateInDatabase(...$last_run);
        $I->runActivatedCronTasks();
        $I->seeFileFound(...$path_args);
    }

    // codecept run acceptance CronCest:visitConfirmationMessage --steps -f
    public function visitConfirmationMessage(A $I)
    {
        $I->runCronTask('send_visit_confirmation_message');
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails(2);
        $mails = [
            [
                'sub' => 'Bekräfta ditt besök',
                'from' => 'info@sigtunanaturskola.se',
                'to' => 'kindulaer@edu.sigtuna.se',
                'body' => ['Liv', 'Alfred', '2C', '7 juni'],
            ],
            [
                'sub' => 'Bekräfta ditt besök',
                'from' => 'info@sigtunanaturskola.se',
                'to' => 'krumpf@edu.sigtuna.se',
                'body' => ['Universum', 'Björn', '2A', '4 juni'],
            ],
        ];

        $I->checkMultipleEmails($mails);
    }

    // codecept run acceptance CronCest:adminSummaryMail --steps -f
    public function adminSummaryMail(A $I)
    {
        $I->runCronTask('send_admin_summary_mail');
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails(1);

        $mail = [
            'sub' => 'Dagliga sammanfattningen av databasen',
            'from' => 'info@sigtunanaturskola.se',
            'to' => 'info@sigtunanaturskola.se',
            'body' => [
                'Status av databasen',
                'Felaktiga mobilnummer',
                'Peter Samuelsson',
                '071-9638300',
                'Per Hedin',
                '085474218',
                'För många elever',
                'Obekräftade besök',
                '2018-06-04: Universum med 2A från S:t Pers skola',
            ],
        ];
        $I->checkEmail($mail);
        // TODO: Add more test cases
    }

    // codecept run acceptance CronCest:sendChangedGroupleaderMail --steps -f
    public function sendChangedGroupleaderMail(A $I)
    {
        $I->runCronTask('send_changed_groupleader_mail');
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails(2);
        //checking that no new mails are sent
        $I->runActivatedCronTasks();
        $I->pause(0.7);
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails(2);
        // checking that this is due to the mails already being sent and not just because of the systemstatus
        $I->changeTestDate('+3 days');
        $I->runActivatedCronTasks();
        $I->pause(0.7);
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails(2);
        // checking the content
        $mail = [
            'sub' => 'Antal grupper du förvaltar har ökat',
            'from' => 'info@sigtunanaturskola.se',
            'to' => 'ipsum.leo@edu.sigtuna.se',
            'body' => [
                'Ny grupp som du ansvarar för',
                '5b, åk 5',
                'Hej Anna',
                'gjort dig ansvarig för en eller flera grupper',
                'ändrat ansvaret för nån'
            ],
        ];
        $I->checkEmail($mail);
        $mail = [
            'sub' => 'Antal grupper du förvaltar har minskat',
            'from' => 'info@sigtunanaturskola.se',
            'to' => 'Nulla@edu.sigtuna.se',
            'body' => [
                'Borttagen grupp som du ej längre ansvarar för',
                '5b, åk 5',
                'Gruppen som du fortsätter att ansvara för',
                '5a, åk 5',
                'Hej Tomas'
            ],
        ];
        $I->checkEmail($mail);
    }

    // codecept run acceptance CronCest:sendNewUserMail --steps -f
    public function sendNewUserMail(A $I)
    {
        $expected_mail_nr = 1;

        $heinz_welcome_mail = ['User_id' => 102, 'Subject' => 2, 'Carrier' => 0, 'Status' => 1];
        $I->dontSeeInDatabase('messages', $heinz_welcome_mail);
        $I->runCronTask('send_new_user_mail');
        //$I->pauseExecution();
        $I->seeInDatabase('messages', $heinz_welcome_mail);
        $I->fetchEmails();
        //$I->pauseExecution();
        $I->haveNumberOfUnreadEmails($expected_mail_nr);
        $mail = [
            'sub' => 'Välkommen i Naturskolans databas',
            'from' => 'info@sigtunanaturskola.se',
            'to' => 'heinz.krumbichel@edu.sigtuna.se',
            'body' => [
                'Hej Heinz'
            ],
        ];
        $I->checkEmail($mail);

        $user_data = [
            'id' => 103,
            'FirstName' => 'Ban Ki',
            'LastName' => 'Moon',
            'Mail' => 'slindholm0@jiathis.com',
            'Status' => 0,
            'School_id' => 'jose'
        ];

        $I->seeInDatabase('users', $user_data);
        $I->updateInDatabase('users', ['Status' => 1], ['id' => 103]);
        // run task again, but only a few hours after
        $I->changeTestDate('+2 hours');
        $I->runActivatedCronTasks();
        $I->fetchEmails();
        // expect no new mail
        $I->haveNumberOfUnreadEmails($expected_mail_nr);

        // run task again much later
        $I->changeTestDate('+3 days');
        $I->runActivatedCronTasks();
        $I->fetchEmails();
        // expect one new mail
        $I->haveNumberOfUnreadEmails($expected_mail_nr + 1);
    }

    // codecept run acceptance CronCest:sendUpdateProfileReminder --steps -f
    public function sendUpdateProfileReminder(A $I)
    {
        $expected_mail_nr = 1 ;

        $I->runCronTask('send_update_profile_reminder');
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails($expected_mail_nr);

        $mail = [
            'sub' => 'Vi behöver mer information från dig',
            'from' => 'info@sigtunanaturskola.se',
            'to' => 'nbrealey0@sphinn.com',
            'body' => [
                'Hej Maja',
                'behöver vi ett mobilnummer till dig',
                'skola/norr'
            ],
        ];
        $I->checkEmail($mail);

        $I->runActivatedCronTasks();
        // no new mail as there is no change
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails($expected_mail_nr);

        $I->changeTestDate('+5 days'); // more than the annoyance interval, so the user will be contacted again
        $I->runActivatedCronTasks();
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails($expected_mail_nr + 1);
    }

    // codecept run acceptance CronCest:createNewPasswords --steps -f
    public function createNewPasswords(A $I)
    {
        $I->emptyFilesInFolder('temp');

        $initial_pw_count = 23;
        $I->seeNumRecords($initial_pw_count, 'hashes', ['Category' => 3]);

        $I->runCronTask('create_new_passwords');
        // the there are no passwords that expire before "today + 1/2 year"
        $I->seeNumRecords($initial_pw_count, 'hashes', ['Category' => 3]);

        $I->changeTestDate('+6 weeks'); // now the task is due, but still no old passwords
        $I->runActivatedCronTasks();
        $I->seeNumRecords($initial_pw_count, 'hashes', ['Category' => 3]);


        $I->changeTestDate('+8 months'); // = 2019-02-01
        // now all passwords should be renewed
        $I->runActivatedCronTasks();
        $I->seeNumRecords($initial_pw_count * 2, 'hashes', ['Category' => 3]);

        $I->assertNotEmpty($I->getFileNamesFromFolder('temp'));
     }

    public function cleanSqlDatabase(A $I)
    {
        // TODO: implement this function
    }

    // codecept run acceptance CronCest:backupDatabase --steps -f
    public function backupDatabase(A $I)
    {
        $I->emptyFilesInFolder('backup');
        $I->assertEmpty($I->getFileNamesFromFolder('backup'));

        $test_fixture = [
            0 => 1, //06-01, day_nr: 151
            4 => 2, //06-05, 154
            10 => 2, //06-11, 161
            29 => 2, //06-30, 180 (should stay forever)
            30 => 3, //07-01, 181
            119 => 2, //09-28, 245
        ];

        foreach($test_fixture as $days_to_add => $files_to_expect){
            $I->changeTestDate('+' . $days_to_add . ' days');
            if($days_to_add === 0){
                $I->runCronTask('backup_database');
            } else {
                $I->runActivatedCronTasks();
            }
            $I->assertCount($files_to_expect, $I->getFileNamesFromFolder('backup'));
        }

    }

    // codecept run acceptance CronCest:backupDatabaseChecker --steps -f
    /**
     * @skip
     * @param AcceptanceTester $I
     * @throws Exception
     */
    public function backupDatabaseChecker(A $I)
    {
        $I->emptyFilesInFolder('backup');
        $I->runCronTask('backup_database');

        foreach(range(1,500) as $days_to_add){
            $I->changeTestDate('+' . $days_to_add . ' days');
            $I->runActivatedCronTasks();
            $files = $I->getFileNamesFromFolder('backup');

            $path = codecept_output_dir() . '/database_check_log.txt';
            $data = "-----\n" . $days_to_add . "\n-----\n";
            $data .= implode("\n", $files) . "\n\n";

            file_put_contents($path, $data ,FILE_APPEND);
        }
    }




}
