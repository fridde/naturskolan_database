<?php


use Carbon\Carbon;

use AcceptanceTester as A;
use Codeception\Util\Locator;

// codecept run acceptance TableCest --steps -f
class TableCest
{
    public function _before(A $I)
    {
        // if run manually, don't forget to first run
        // codecept run acceptance HelperTestsCest:resetDatabase
        $I->amOnPage('/');
        $I->setCookie('AuthKey', $I->get('natu', 'AuthKey'));
        $I->setCookie('XDEBUG_SESSION', 'PHPSTORM');
    }

    public function _after(A $I)
    {
    }

    // codecept run acceptance TableCest:editEventTable --steps -f
    public function editEventTable(A $I)
    {
        $initial_event_count = 2;

        $I->amOnPage('/table/Event');
        $I->assertCount($initial_event_count, $I->getTableRows('Event'));
        $I->assertSame($initial_event_count, $I->grabNumRecords('events'));

        $button = $I->getAddRowButton();
        $I->seeElement($button);
        $I->click($button);
        $I->assertCount($initial_event_count + 1, $I->getTableRows('Event'));



        $I->fillField($I->getFieldFromLastRow('Event', 'Title'), 'Ekorrens dag enligt FN');
        $I->clickWithLeftButton(null, 0, -50);
        $I->wait(2);
        // as we have only entered the title and not a start date yet
        $I->assertSame($initial_event_count, $I->grabNumRecords('events'));

        $I->fillField($I->getFieldFromLastRow('Event', 'StartDate'), '2018-08-05');
        $I->clickWithLeftButton(null, 0, -50);
        $I->wait(2);
        $I->assertSame($initial_event_count + 1, $I->grabNumRecords('events'));

        $I->runCronTask('rebuild_calendar');
        $I->seeFileFound('kalender.ics', codecept_root_dir());

        $strings = [
            'SUMMARY:Ekorrens dag',
            'DTSTART;TZID=Europe/Stockholm:20180801T103700',
            'DTEND;TZID=Europe/Stockholm:20180801T113700',
            'SUMMARY:Lösningar med 5a från S:t Pers skola (Tomas S)',
            'DESCRIPTION:Tid: 08:15-13:30',
            'preferenser: Halal\, fisk-allergi'
        ];

        $I->seeStringsInThisFile($strings);
    }

    // codecept run acceptance TableCest:editGroupTable --steps -f
    public function editGroupTable(A $I)
    {
        $initial_group_count = 9;

        $I->amOnPage('/table/Group');
        $I->assertCount($initial_group_count, $I->getTableRows('Group'));
        $I->assertSame($initial_group_count, $I->grabNumRecords('groups'));

        $button = $I->getAddRowButton();
        $I->seeElement($button);
        $I->click($button);
        $I->assertCount($initial_group_count + 1, $I->getTableRows('Group'));

        $I->fillField($I->getFieldFromLastRow('Group', 'Name'), 'Herr Jönssons grupp');
        $I->clickWithLeftButton(null, 0, -50);
        $I->wait(2);
        // as we have only entered the Name and not the Status
        $I->assertSame($initial_group_count, $I->grabNumRecords('groups'));

        $I->selectOption($I->getFieldFromLastRow('Group', 'Status', 'select'), 'active');
        $I->clickWithLeftButton(null, 0, -50);
        $I->wait(2);
        // now all required fields are entered
        $I->assertSame($initial_group_count + 1, $I->grabNumRecords('groups'));
        /*
                $I->fillField($I->getFieldFromLastRow('Event', 'StartDate'), '2018-08-05');
                $I->clickWithLeftButton(null, 0, -50);
                $I->wait(2);
                $I->assertSame($initial_event_count + 1, $I->grabNumRecords('events'));

                $I->runCronTask('rebuild_calendar');
                $I->seeFileFound('kalender.ics', codecept_root_dir());

                $strings = [
                    'SUMMARY:Ekorrens dag',
                    'DTSTART;TZID=Europe/Stockholm:20180801T103700',
                    'DTEND;TZID=Europe/Stockholm:20180801T113700',
                    'SUMMARY:Lösningar med 5a från S:t Pers skola (Tomas S)',
                    'DESCRIPTION:Tid: 08:15-13:30',
                    'preferenser: Halal\, fisk-allergi'
                ];

                $I->seeStringsInThisFile($strings);
                */
    }






}
