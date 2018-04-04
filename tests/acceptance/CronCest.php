<?php


use Carbon\Carbon;
use Codeception\Util\Locator;

use AcceptanceTester as A;

class CronCest
{
    public function _before(A $I)
    {
        $I->setTestDate();
        $I->amOnPage('/');
        $I->setCookie('Hash', $I->get('natu', 'hash'));
        $I->amOnPage('/admin');
        $I->deleteAllEmails();
    }

    public function _after(A $I)
    {
    }

    // codecept run acceptance CronCest:canSeeAllFields --steps
    public function canSeeAllFields(A $I)
    {
        $fields = $I->get('cron_items');
        foreach ($fields as $index => $label) {
            $I->canSee($label);
            $I->seeInSource($index);
        }
    }

    // codecept run acceptance CronCest:canToggleActivation --steps
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

    // codecept run acceptance CronCest:calendarGetsRebuild --steps
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


    // codecept run acceptance CronCest:calendarDoesntGetRebuild --steps
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
                'to' => 'krumpf@edu.sigtuna.se',
                'body' => ['Liv', 'Björn', '2A', '13 mars'],
            ],
            [
                'sub' => 'Bekräfta ditt besök',
                'from' => 'info@sigtunanaturskola.se',
                'to' => 'kindulaer@edu.sigtuna.se',
                'body' => ['Universum', 'Alfred', '2C', '2 mars'],
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
                'Pär Hedin',
                '085474218',
                'Obekräftade besök',
                '2018-03-02: Universum med 2C från S:t Pers skola',
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
        $I->setTestDate('2018-03-03');
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
        $heinz_welcome_mail = ['User_id' => 102, 'Subject' => 1, 'Carrier' => 0, 'Status' => 1];
        $I->dontSeeInDatabase('messages', $heinz_welcome_mail);
        $this->runTask($I, 'send_new_user_mail');
        $I->seeInDatabase('messages', $heinz_welcome_mail);
        $I->fetchEmails();
        $I->haveNumberOfUnreadEmails(1);
        $mail = [
            'sub' => 'Välkommen i Naturskolans databas',
            'from' => 'info@sigtunanaturskola.se',
            'to' => 'heinz.krumbichel@edu.sigtuna.se',
            'body' => [
                'Hej Heinz'
            ],
        ];
        $I->checkEmail($mail);
    }

}
