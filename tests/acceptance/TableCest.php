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
            'DTSTART;TZID=Europe/Stockholm:20180801T103700', //custom event with custom start
            'DTEND;TZID=Europe/Stockholm:20180801T123700', // custom event with default duration
            'SUMMARY:Lösningar med 5a från S:t Pers skola (Tomas S)',
            'DESCRIPTION:Tid: 08:15-13:30',
            'preferenser: Halal\, fisk-allergi',
            'DTSTART;TZID=Europe/Stockholm:20190214T143600',  // custom timed start time
            'DTEND;TZID=Europe/Stockholm:20190214T153600',  // default duration for lektion
            'DTSTART;TZID=Europe/Stockholm:20190213T145300', // custom start (with defined end)
            'DTEND;TZID=Europe/Stockholm:20190213T160100', // custom end
            'DTSTART;TZID=Europe/Stockholm:20181113T062500', // custom start with 3 digits
            'DTEND;TZID=Europe/Stockholm:20181113T095500',  // custom end with 3 digits
            'DTSTART;TZID=Europe/Stockholm:20181217T090700', // just a custom start
            'DTEND;TZID=Europe/Stockholm:20181217T110700' // default duration for visit
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
    }

    // codecept run acceptance TableCest:editSchoolTable --steps -f
    public function editSchoolTable(A $I)
    {
        $school_count = 23;

        $I->amOnPage('/table/School');
        $I->assertCount($school_count, $I->getTableRows('School'));
        $I->assertSame($school_count, $I->grabNumRecords('schools'));

        $I->canSee('{"2017":{"2":0,"5":0,"fbk":0},"2018":{"2":0,"5":0,"fbk":0},"2019":{"2":0,"5":0,"fbk":0}}');


        $josefina_row = $I->get('paths', 'josefina_row');
        $central_row = $I->get('paths', 'central_row');

        $first_school_row_field = $I->get('paths', 'first_school_row_field');

        $I->seeInDatabase('schools', ['id' => 'cent', 'VisitOrder' => 1]);
        $I->seeInField($first_school_row_field, 'Centralskolan');

        // Check if the reordering works
        $I->dragAndDrop($josefina_row, $central_row);
        $I->wait(2);
        $I->makeScreenshot('reordered');

        $I->seeInField($first_school_row_field, 'Josefinaskolan');

        $button_path = '//div[@class="dt-buttons btn-group"]//button'; // Button for "Spara besöksordningen"
        $I->click($button_path);
        $I->wait(2);

        $I->seeInDatabase('schools', ['id' => 'jose', 'VisitOrder' => 1]);
        $I->seeInDatabase('schools', ['id' => 'cent', 'VisitOrder' => 2]);
    }

    // codecept run acceptance TableCest:editTopicTable --steps -f
    public function editTopicTable(A $I)
    {
        $topic_count = 19;

        $I->amOnPage('/table/Topic');

        $I->assertCount($topic_count, $I->getTableRows('Topic'));
        $I->assertSame($topic_count, $I->grabNumRecords('topics'));
    }










}
