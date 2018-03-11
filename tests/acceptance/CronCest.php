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
    }

    public function _after(A $I)
    {
    }

    // codecept run acceptance CronCest:canSeeAllFields --steps
    public function canSeeAllFields(A $I)
    {
        $fields = $I->get('cron_items');
        foreach($fields as $index => $label){
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
        $cb_path = $I->get('paths','rebuild_calendar_cb');
        $I->checkOption($cb_path);
        $I->wait(2);
        $new_status = json_decode($I->grabFromDatabase(...$status_path), true);
        $I->assertSame(1, $new_status['rebuild_calendar']);
        $I->uncheckOption($cb_path);
        $I->wait(2);
        $newest_status = json_decode($I->grabFromDatabase(...$status_path), true);
        $I->assertSame(0, $newest_status['rebuild_calendar']);
    }

    // codecept run acceptance CronCest:slotCounterWorks --steps
    public function slotCounterWorks(A $I)
    {
        $I->uncheckOption($I->get('paths','rebuild_calendar_cb'));
        $I->seeInDatabase('systemstatus', ['id' => 'slot_counter', 'Value' => 97]);

        $I->amOnPage('/cron/');
        $I->wait(2);
        $I->seeInDatabase('systemstatus', ['id' => 'slot_counter', 'Value' => 98]);

    }

    // codecept run acceptance CronCest:calendarGetsRebuild --steps
    public function calendarGetsRebuild(A $I)
    {
        $I->checkOption($I->get('paths','rebuild_calendar_cb'));
        $cal_path = __DIR__ . '/../../kalender.ics';
        if(file_exists($cal_path)){
            $I->deleteFile($cal_path);
        }
        $I->amOnPage('/cron/');
        $I->wait(2);

        $I->seeFileFound('kalender.ics', __DIR__ . '/../../');
        $last_rebuild = $I->grabFromDatabase('systemstatus', 'Value', ['id' => 'last_run.rebuild_calendar']);
        var_dump($last_rebuild);
    }
}
