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
        $I->wait(2);
        $new_status = json_decode($I->grabFromDatabase(...$status_path), true);
        $I->assertSame(1, $new_status['rebuild_calendar']);
        $I->uncheckOption($cb_path);
        $I->wait(2);
        $newest_status = json_decode($I->grabFromDatabase(...$status_path), true);
        $I->assertSame(0, $newest_status['rebuild_calendar']);
    }

    private function runTask(A $I, $task)
    {
        $I->amOnPage('/admin');
        $cron_tasks = array_keys($I->get('cron_items'));
        if(!in_array($task, $cron_tasks, true)){
            throw new \Exception('The task "'. $task . '" was not defined in the test settings');
        }

        foreach ($cron_tasks as $cron_task) {
            $path = '//input[@name="'.$cron_task.'"]';
            if ($cron_task === $task) {
                $I->checkOption($path);
            } else {
                $I->uncheckOption($path);
            }

        }
        $I->amOnPage('/cron/');
        $I->wait(2);
    }

    private function runTaskAgain(A $I, $task)
    {
        $I->amOnPage('/cron/');
        $I->wait(2);
    }

    // codecept run acceptance CronCest:calendarGetsRebuild --steps -f
    public function calendarGetsRebuild(A $I)
    {
        $cal_path = __DIR__.'/../../kalender.ics';
        if (file_exists($cal_path)) {
            $I->deleteFile($cal_path);
        }
        $this->runTask($I, 'rebuild_calendar');
        $I->seeFileFound('kalender.ics', __DIR__.'/../../');
        $I->seeInDatabase('systemstatus', ['id' => 'last_run.rebuild_calendar']);
    }


    // codecept run acceptance CronCest:calendarDoesntGetRebuild --steps -f
    public function calendarDoesntGetRebuild(A $I)
    {
        $cal_path = __DIR__.'/../../kalender.ics';
        $path_args = ['kalender.ics', __DIR__.'/../../'];
        $last_run = ['systemstatus', ['Value' => null], ['id' => 'last_run.rebuild_calendar']];
        $this->runTask($I, 'rebuild_calendar');
        if (file_exists($cal_path)) {
            $I->deleteFile($cal_path);
        }
        $this->runTask($I, 'rebuild_calendar');
        $I->dontSeeFileFound(...$path_args);
        $last_rebuild = $I->grabFromDatabase('systemstatus', 'Value', ['id' => 'last_run.rebuild_calendar']);
        $last_run[1]['Value'] = Carbon::parse($last_rebuild)->subMinutes(10)->toIso8601String();
        $I->updateInDatabase(...$last_run);
        $this->runTask($I, 'rebuild_calendar');
        $I->dontSeeFileFound(...$path_args);
        $last_run[1]['Value'] = Carbon::parse($last_rebuild)->subMinutes(20)->toIso8601String();
        $I->updateInDatabase(...$last_run);
        $this->runTask($I, 'rebuild_calendar');
        $I->seeFileFound(...$path_args);
    }

    // codecept run acceptance CronCest:visitConfirmationMessage --steps -f
    public function visitConfirmationMessage(A $I)
    {
        $this->runTask($I, 'send_visit_confirmation_message');
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
        $this->runTask($I, 'send_admin_summary_mail');
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

    }

    // codecept run acceptance CronCest:sendChangedGroupleaderMail --steps -f
    public function sendChangedGroupleaderMail(A $I)
    {
        $this->runTask($I, 'send_changed_groupleader_mail');
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails(2);
        //checking that no new mails are sent
        $I->amOnPage('/cron/');
        $I->wait(2);
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails(2);
        // checking that this is due to the mails already being sent and not just because of the systemstatus
        $I->changeTestDate('+3 days');
        $I->amOnPage('/cron/');
        $I->wait(2);
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

        $heinz_welcome_mail = ['User_id' => 102, 'Subject' => 1, 'Carrier' => 0, 'Status' => 1];
        $I->dontSeeInDatabase('messages', $heinz_welcome_mail);
        $this->runTask($I, 'send_new_user_mail');
        $I->seeInDatabase('messages', $heinz_welcome_mail);
        $I->fetchEmails();
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
        $this->runTaskAgain($I, 'send_new_user_mail');
        $I->fetchEmails();
        // expect no new mail
        $I->haveNumberOfUnreadEmails($expected_mail_nr);

        // run task again much later
        $I->changeTestDate('+3 days');
        $this->runTaskAgain($I, 'send_new_user_mail');
        $I->fetchEmails();
        // expect one new mail
        $I->haveNumberOfUnreadEmails($expected_mail_nr + 1);
    }

    // codecept run acceptance CronCest:sendUpdateProfileReminder --steps -f
    public function sendUpdateProfileReminder(A $I)
    {
        $expected_mail_nr = 1 ;

        $this->runTask($I, 'send_update_profile_reminder');
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails($expected_mail_nr);

        $mail = [
            'sub' => 'Vi behöver mer information från dig',
            'from' => 'info@sigtunanaturskola.se',
            'to' => 'nbrealey0@sphinn.com',
            'body' => [
                'Hej Maja',
                'Vi behöver ditt mobilnummer',
                'Uppdatera ditt profil'
            ],
        ];
        $I->checkEmail($mail);

        $this->runTaskAgain($I, 'send_update_profile_reminder');
        // no new mail as there is no change
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails($expected_mail_nr);

        $I->changeTestDate('+5 days'); // more than the annoyance interval, so the user will be contacted again
        $this->runTaskAgain($I, 'send_update_profile_reminder');
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails($expected_mail_nr + 1);
    }

    // codecept run acceptance CronCest:createNewPasswords --steps -f
    public function createNewPasswords(A $I)
    {
        $I->emptyTempFolder();

        $initial_pw_count = 23;
        $I->seeNumRecords($initial_pw_count, 'hashes', ['Category' => 3]);

        $this->runTask($I,'create_new_passwords');
        // the there are no passwords that expire before "today + 1/2 year"
        $I->seeNumRecords($initial_pw_count, 'hashes', ['Category' => 3]);

        $I->changeTestDate('+6 weeks'); // now the task is due, but still no old passwords
        $this->runTaskAgain($I, 'create_new_passwords');
        $I->seeNumRecords($initial_pw_count, 'hashes', ['Category' => 3]);


        $I->changeTestDate('+8 months'); // = 2019-02-01
        // now all passwords should be renewed
        $this->runTaskAgain($I, 'create_new_passwords');
        $I->seeNumRecords($initial_pw_count * 2, 'hashes', ['Category' => 3]);

        $I->assertNotEmpty($I->getFileNamesFromTempFolder());

     }



}
