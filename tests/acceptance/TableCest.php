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
        $I->assertEquals($initial_event_count, $I->grabNumRecords('events'));

        $button = $I->getAddRowButton();
        $I->seeElement($button);
        $I->click($button);
        $I->assertCount($initial_event_count + 1, $I->getTableRows('Event'));



        $I->fillField($I->getFieldFromLastRow('Event', 'Title'), 'Ekorrens dag enligt FN');
        $I->clickAway();
        $I->delay(0.7);
        // as we have only entered the title and not a start date yet
        $I->assertEquals($initial_event_count, $I->grabNumRecords('events'));


        $I->fillField($I->getFieldFromLastRow('Event', 'StartDate'), '2018-08-05');
        $I->clickAway();
        $I->delay(1);

        $I->assertEquals($initial_event_count + 1, $I->grabNumRecords('events'));

        $I->runCronTask('rebuild_calendar');
        $I->seeFileFound('kalender.ics', codecept_root_dir());

        $strings = [
            'SUMMARY:Ekorrens dag',
            'DTSTART;TZID=Europe/Stockholm:20180801T103700', //custom event with custom start
            'DTEND;TZID=Europe/Stockholm:20180801T123700', // custom event with default duration
            'SUMMARY:Lösningar med 5a från S:t Pers skola (Tomas S)',
            'DESCRIPTION:Tid: 08:15-13:30',
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
        $initial_group_count = 10;

        $I->amOnPage('/table/Group');

        $I->assertCount($initial_group_count, $I->getTableRows('Group'));
        $I->assertEquals($initial_group_count, $I->grabNumRecords('groups'));

        $button = $I->getAddRowButton();
        $I->seeElement($button);
        $I->click($button);
        $I->assertCount($initial_group_count + 1, $I->getTableRows('Group'));

        $I->fillField($I->getFieldFromLastRow('Group', 'Name'), 'Herr Jönssons grupp');
        $I->clickAway();
        $I->delay(0.7);
        // as we have only entered the Name and not the Status
        $I->assertEquals($initial_group_count, $I->grabNumRecords('groups'));

        $I->selectOption($I->getFieldFromLastRow('Group', 'Status', 'select'), 'active');
        $I->clickAway();
        $I->delay(0.7);
        // now all required fields are entered
        $I->assertEquals($initial_group_count + 1, $I->grabNumRecords('groups'));
    }

    // codecept run acceptance TableCest:editSchoolTable --steps -f
    public function editSchoolTable(A $I)
    {
        $school_count = 23;

        $I->amOnPage('/table/School');
        $I->assertCount($school_count, $I->getTableRows('School'));
        $I->assertEquals($school_count, $I->grabNumRecords('schools'));

        // $I->canSee('{"2017":{"5":3},"2018":{"2":3}}');
        // $I->canSee('{"2018":{"fri":2},"2019":{"2":6}}');

        $josefina_row = $I->get('paths', 'josefina_row');
        $central_row = $I->get('paths', 'central_row');

        $first_school_row_field = $I->get('paths', 'first_school_row_field');

        $I->seeInDatabase('schools', ['id' => 'cent', 'VisitOrder' => 1]);
        $I->seeInField($first_school_row_field, 'Centralskolan');

        // Check if the reordering works
        $I->dragAndDrop($josefina_row, $central_row);
        $I->delay(0.7);
        $I->makeScreenshot('reordered');

        $I->seeInField($first_school_row_field, 'Josefinaskolan');

        $button_path = '//span[contains(text(), "besöksordning")]'; // Button for "Spara besöksordningen"
        $I->click($button_path);
        $I->delay(0.7);

        $I->seeInDatabase('schools', ['id' => 'jose', 'VisitOrder' => 1]);
        $I->seeInDatabase('schools', ['id' => 'cent', 'VisitOrder' => 2]);
    }

    // codecept run acceptance TableCest:editTopicTable --steps -f
    public function editTopicTable(A $I)
    {
        $initial_topic_count = 28;

        $I->amOnPage('/table/Topic');

        $I->assertCount($initial_topic_count, $I->getTableRows('Topic'));
        $I->assertEquals($initial_topic_count, $I->grabNumRecords('topics'));

        $I->seeInDatabase('topics', ['id' => 1, 'Segment' => '2', 'Location_id' => 2]);

        $segment_selector = $I->get('paths', 'universum_segment_select');
        $I->seeElement($segment_selector);
        $I->seeOptionIsSelected($segment_selector, 'åk 2/3');

        $I->selectOption($segment_selector, 'åk 5');
        $I->delay();
        $I->seeInDatabase('topics', ['id' => 1, 'Segment' => '5']);

        $location_selector = $I->get('paths', 'universum_location_select');
        $I->seeElement($location_selector);
        $I->seeOptionIsSelected($location_selector, 'Flottvik');

        $I->selectOption($location_selector, 'Skogen');
        $I->delay();
        $I->seeInDatabase('topics', ['id' => 1, 'Location_id' => 4]);

        $lektion_selector = $I->get('paths', 'universum_lektion_select');
        $I->selectOption($lektion_selector, '1');
        $I->delay();

        $I->seeInDatabase('topics', ['id' => 1, 'IsLektion' => 1]);

        $row_btn = $I->getAddRowButton();
        $I->canSeeElement($row_btn);
        $I->click($row_btn);
        $I->delay();

        $I->assertCount($initial_topic_count + 1, $I->getTableRows('Topic'));

        $food_field = $I->getFieldFromLastRow('Topic', 'Food');
        $food_value = 'Ärtsoppa med blodpudding';
        $I->fillField($food_field, $food_value);
        $I->clickAway();
        $I->delay();

        $I->dontSeeInDatabase('topics', ['Food' => $food_value]);
        $I->assertEquals($initial_topic_count, $I->grabNumRecords('topics'));

        $short_name_field = $I->getFieldFromLastRow('Topic', 'ShortName');
        $short_name_value = 'Plastikkirurgi';
        $I->fillField($short_name_field, $short_name_value);
        $I->clickAway();
        $I->delay();

        $I->seeInDatabase('topics', ['Food' => $food_value, 'ShortName' => $short_name_value]);
        $I->assertEquals($initial_topic_count + 1, $I->grabNumRecords('topics'));
    }


    // codecept run acceptance TableCest:editUserTable --steps -f
    public function editUserTable(A $I)
    {
        $last_name_selector = $I->get('paths', 'elena_last_name');
        $role_selector = $I->get('paths', 'elena_role');

        $I->amOnPage('/table/User');

        $I->seeInDatabase('users', ['id' => '11', 'LastName' => 'Staffansson']);

        $I->seeElement($last_name_selector);
        $I->fillField($last_name_selector, 'Isaksson');
        $I->clickAway();
        $I->delay(1.5);

        $I->pause();

        $I->seeInDatabase('users', ['id' => '11', 'LastName' => 'Isaksson']);

        $I->seeInDatabase('users', ['id' => '11', 'Role' => 4]);

        $I->seeElement($role_selector);
        $I->selectOption($role_selector, 'stakeholder');
        $I->clickAway();
        $I->delay();


        $I->seeInDatabase('users', ['id' => '11', 'Role' => 2]);

        $I->reloadPage();
        $I->delay();
        $row = '//tr[@data-id="11"]';
        $I->scrollTo($row);
        // TODO: fix this so that the row below doesn't fail
        //$I->see('2018-06-01T12:00:00+02:00', $row);

        $row_btn = $I->getAddRowButton();
        $I->canSeeElement($row_btn);
        $I->click($row_btn);
        $I->delay();

        $first_name_field = $I->getFieldFromLastRow('User', 'FirstName');

        $I->fillField($first_name_field, 'Horst-Kevin');
        $I->clickAway();
        $I->delay();
        $I->seeInDatabase('users', ['FirstName' => 'Horst-Kevin']);

        $horst_row = '//tr[@data-id="244"]';

        $I->reloadPage();
        $I->delay();
        // TODO: fix this so that the row below doesn't fail
        // $I->checkMultiple('see', ['stakeholder', 'Ingen'], $horst_row);
    }

    // codecept run acceptance TableCest:editVisitTable --steps -f
    public function editVisitTable(A $I)
    {
        $I->amOnPage('/table/Visit');
        $I->delay();

        $group_selector = '//tr[@data-id="10"]//select[@name="Group"]';
        $I->seeElement($group_selector);
        $I->selectOption($group_selector, '46');
        $I->delay();
        $I->seeInDatabase('visits', ['id' => 10, 'Group_id' => 46]);

        $date_picker = '//tr[@data-id="10"]//input[@name="Date"]';
        $I->seeElement($date_picker);
        $I->fillField($date_picker, '2019-05-06');
        $I->clickAway(0, 70);
        $I->delay();
        $I->seeInDatabase('visits', ['id' => 10, 'Date' => '2019-05-06']);

        $topic_selector = '//tr[@data-id="10"]//select[@name="Topic"]';
        $I->seeElement($topic_selector);
        $I->selectOption($topic_selector, '14');
        $I->delay();
        $I->seeInDatabase('visits', ['id' => 10, 'Topic_id' => 14]);

        $confirmed_selector = '//tr[@data-id="10"]//input[@name="Confirmed#10"]';
        $I->seeElement($confirmed_selector);
        //$I->scrollTo($confirmed_selector);
        $I->delay(2);
        //
        $I->selectOption($confirmed_selector, '1');
        $I->delay();
        $I->seeInDatabase('visits', ['id' => 10, 'Confirmed' => 1]);

        $time_field = '//tr[@data-id="10"]//input[@name="Time"]';
        $I->seeElement($time_field);
        $I->fillField($time_field, '1939-1945');
        $I->clickAway();
        $I->delay(4);
        $I->pause();
        //$I->seeInDatabase('visits', ['id' => 10, 'Time' => '19:39-19:45']);

        $I->runCronTask('rebuild_calendar');
        $I->delay(4);
        $I->seeFileFound('kalender.ics', codecept_root_dir());
        $I->pause();
        $strings = [
            'DTSTART;TZID=Europe/Stockholm:20190506T193900',
            'DTEND;TZID=Europe/Stockholm:20190506T194500'
        ];
        $I->seeStringsInThisFile($strings);

        $I->amOnPage('/table/Visit');
        $I->delay();

        $initial_visit_count = 30;

        $button = $I->getAddRowButton();
        $I->seeElement($button);
        $I->click($button);
        $I->delay();
        $I->assertCount($initial_visit_count + 1, $I->getTableRows('Visit'));

        $last_time_field = $I->getFieldFromLastRow('Visit', 'Time');
        $I->fillField($last_time_field, '1618-1648');
        $I->clickAway();
        $I->delay();
        $I->assertEquals($initial_visit_count, $I->grabNumRecords('visits'));

        $last_date_field = $I->getFieldFromLastRow('Visit', 'Date');
        $I->fillField($last_date_field, '2019-05-07');
        $I->clickAway();
        $I->delay();
        $I->assertEquals($initial_visit_count + 1, $I->grabNumRecords('visits'));

    }


}
